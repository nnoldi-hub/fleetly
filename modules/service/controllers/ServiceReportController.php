<?php
/**
 * ServiceReportController
 * Controller pentru rapoarte și analize service intern
 * 
 * @package FleetManagement
 * @subpackage Service
 * @version 1.0
 */

class ServiceReportController extends Controller {
    private $auth;
    private $serviceModel;
    
    public function __construct() {
        parent::__construct();
        Auth::getInstance()->requireAuth();
        try {
            $companyId = Auth::getInstance()->effectiveCompanyId();
            if ($companyId) {
                Database::getInstance()->setTenantDatabaseByCompanyId($companyId);
            }
        } catch (Throwable $e) { /* ignore */ }
        $this->auth = Auth::getInstance();
        $this->serviceModel = new Service();
    }
    
    protected function getModuleName() {
        return 'service';
    }
    
    /**
     * Dashboard principal rapoarte
     */
    public function index() {
        $tenantId = $this->auth->getTenantId();
        $internalService = $this->serviceModel->getInternalService($tenantId);
        
        if (!$internalService) {
            $_SESSION['error_message'] = 'Nu aveți configurat un service intern.';
            $this->redirect('/service/services');
        }
        
        // Perioadă implicită - ultima lună
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $this->render('reports/index', [
            'pageTitle' => 'Rapoarte Service',
            'service' => $internalService,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);
    }
    
    /**
     * Raport Rentabilitate Service
     */
    public function profitability() {
        $tenantId = $this->auth->getTenantId();
        $internalService = $this->serviceModel->getInternalService($tenantId);
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Venituri totale (piese + manoperă)
        $sql = "SELECT 
                    COUNT(DISTINCT wo.id) as total_orders,
                    SUM(wo.parts_cost) as parts_revenue,
                    SUM(wo.labor_cost) as labor_revenue,
                    SUM(wo.total_cost) as total_revenue,
                    AVG(wo.total_cost) as avg_order_value
                FROM work_orders wo
                WHERE wo.service_id = ?
                AND DATE(wo.entry_date) BETWEEN ? AND ?
                AND wo.status IN ('completed', 'delivered')";
        
        $revenue = $this->db->fetchOn('work_orders', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        
        // Costuri manoperă (ore lucrate * tarif orar mecanic)
        $sql = "SELECT 
                    SUM(wol.hours_worked) as total_hours,
                    SUM(wol.labor_cost) as total_labor_cost,
                    COUNT(DISTINCT wol.mechanic_id) as active_mechanics
                FROM work_order_labor wol
                JOIN work_orders wo ON wol.work_order_id = wo.id
                WHERE wo.service_id = ?
                AND DATE(wol.start_time) BETWEEN ? AND ?";
        
        $laborCosts = $this->db->fetchOn('work_order_labor', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        
        // Top mecanici după venit generat
        $sql = "SELECT 
                    sm.name as mechanic_name,
                    COUNT(DISTINCT wo.id) as orders_completed,
                    SUM(wo.total_cost) as revenue_generated,
                    SUM(wol.hours_worked) as hours_worked
                FROM work_orders wo
                JOIN service_mechanics sm ON wo.assigned_mechanic_id = sm.id
                LEFT JOIN work_order_labor wol ON wo.id = wol.work_order_id
                WHERE wo.service_id = ?
                AND DATE(wo.entry_date) BETWEEN ? AND ?
                AND wo.status IN ('completed', 'delivered')
                GROUP BY sm.id, sm.name
                ORDER BY revenue_generated DESC
                LIMIT 10";
        
        $topMechanics = $this->db->fetchAllOn('work_orders', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        
        // Evoluție lunară
        $sql = "SELECT 
                    DATE_FORMAT(wo.entry_date, '%Y-%m') as month,
                    COUNT(DISTINCT wo.id) as orders,
                    SUM(wo.total_cost) as revenue
                FROM work_orders wo
                WHERE wo.service_id = ?
                AND DATE(wo.entry_date) BETWEEN ? AND ?
                AND wo.status IN ('completed', 'delivered')
                GROUP BY DATE_FORMAT(wo.entry_date, '%Y-%m')
                ORDER BY month";
        
        $monthlyTrend = $this->db->fetchAllOn('work_orders', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        
        $this->render('reports/profitability', [
            'pageTitle' => 'Raport Rentabilitate Service',
            'service' => $internalService,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'revenue' => $revenue,
            'laborCosts' => $laborCosts,
            'topMechanics' => $topMechanics,
            'monthlyTrend' => $monthlyTrend
        ]);
    }
    
    /**
     * Raport Costuri pe Vehicul
     */
    public function vehicleCosts() {
        $tenantId = $this->auth->getTenantId();
        $internalService = $this->serviceModel->getInternalService($tenantId);
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $vehicleId = $_GET['vehicle_id'] ?? null;
        
        // Costuri totale pe vehicul
        $sql = "SELECT 
                    v.id,
                    v.registration_number,
                    v.brand,
                    v.model,
                    v.year,
                    COUNT(DISTINCT wo.id) as service_visits,
                    SUM(wo.parts_cost) as total_parts_cost,
                    SUM(wo.labor_cost) as total_labor_cost,
                    SUM(wo.total_cost) as total_service_cost,
                    AVG(wo.total_cost) as avg_cost_per_visit,
                    MAX(wo.entry_date) as last_service_date
                FROM vehicles v
                LEFT JOIN work_orders wo ON v.id = wo.vehicle_id 
                    AND wo.service_id = ?
                    AND DATE(wo.entry_date) BETWEEN ? AND ?
                WHERE v.status = 'active'
                " . ($vehicleId ? "AND v.id = ?" : "") . "
                GROUP BY v.id, v.registration_number, v.brand, v.model, v.year
                ORDER BY total_service_cost DESC";
        
        $params = [$internalService['id'], $dateFrom, $dateTo];
        if ($vehicleId) {
            $params[] = $vehicleId;
        }
        
        $vehicleCosts = $this->db->fetchAllOn('vehicles', $sql, $params);
        
        // Detalii pentru un vehicul specific
        $vehicleDetails = null;
        if ($vehicleId) {
            // Istoric service pentru vehicul
            $sql = "SELECT 
                        wo.*,
                        sm.name as mechanic_name
                    FROM work_orders wo
                    LEFT JOIN service_mechanics sm ON wo.assigned_mechanic_id = sm.id
                    WHERE wo.vehicle_id = ?
                    AND wo.service_id = ?
                    AND DATE(wo.entry_date) BETWEEN ? AND ?
                    ORDER BY wo.entry_date DESC";
            
            $vehicleDetails = $this->db->fetchAllOn('work_orders', $sql, [$vehicleId, $internalService['id'], $dateFrom, $dateTo]);
        }
        
        // Lista vehicule pentru dropdown
        $sql = "SELECT id, registration_number, brand, model 
                FROM vehicles 
                WHERE status = 'active' 
                ORDER BY registration_number";
        $vehicles = $this->db->fetchAllOn('vehicles', $sql, []);
        
        $this->render('reports/vehicle_costs', [
            'pageTitle' => 'Raport Costuri pe Vehicul',
            'service' => $internalService,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'vehicleId' => $vehicleId,
            'vehicleCosts' => $vehicleCosts,
            'vehicleDetails' => $vehicleDetails,
            'vehicles' => $vehicles
        ]);
    }
    
    /**
     * Raport Performanță Mecanici
     */
    public function mechanicPerformance() {
        $tenantId = $this->auth->getTenantId();
        $internalService = $this->serviceModel->getInternalService($tenantId);
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Performanță mecanici
        $sql = "SELECT 
                    sm.id,
                    sm.name,
                    sm.specialization,
                    sm.hourly_rate,
                    COUNT(DISTINCT wo.id) as total_orders,
                    SUM(CASE WHEN wo.status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(wol.hours_worked) as total_hours_worked,
                    SUM(wo.total_cost) as revenue_generated,
                    AVG(CASE 
                        WHEN wo.status = 'completed' AND wo.estimated_hours > 0 
                        THEN wo.actual_hours / wo.estimated_hours 
                        ELSE NULL 
                    END) as efficiency_ratio,
                    AVG(TIMESTAMPDIFF(HOUR, wo.entry_date, wo.actual_completion)) as avg_completion_time_hours
                FROM service_mechanics sm
                LEFT JOIN work_orders wo ON sm.id = wo.assigned_mechanic_id 
                    AND DATE(wo.entry_date) BETWEEN ? AND ?
                LEFT JOIN work_order_labor wol ON wo.id = wol.work_order_id 
                    AND wol.mechanic_id = sm.id
                WHERE sm.service_id = ?
                AND sm.is_active = 1
                GROUP BY sm.id, sm.name, sm.specialization, sm.hourly_rate
                ORDER BY revenue_generated DESC";
        
        $mechanicStats = $this->db->fetchAllOn('service_mechanics', $sql, [$dateFrom, $dateTo, $internalService['id']]);
        
        // Top probleme întâlnite
        $sql = "SELECT 
                    wo.work_description,
                    COUNT(*) as frequency
                FROM work_orders wo
                WHERE wo.service_id = ?
                AND DATE(wo.entry_date) BETWEEN ? AND ?
                GROUP BY wo.work_description
                ORDER BY frequency DESC
                LIMIT 10";
        
        $commonIssues = $this->db->fetchAllOn('work_orders', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        
        $this->render('reports/mechanic_performance', [
            'pageTitle' => 'Raport Performanță Mecanici',
            'service' => $internalService,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'mechanicStats' => $mechanicStats,
            'commonIssues' => $commonIssues
        ]);
    }
    
    /**
     * Raport Timpi de Lucru
     */
    public function workTimes() {
        $tenantId = $this->auth->getTenantId();
        $internalService = $this->serviceModel->getInternalService($tenantId);
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Analiza timpilor
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    AVG(wo.estimated_hours) as avg_estimated_hours,
                    AVG(wo.actual_hours) as avg_actual_hours,
                    AVG(TIMESTAMPDIFF(HOUR, wo.entry_date, wo.actual_completion)) as avg_total_time_hours,
                    SUM(CASE WHEN wo.actual_hours <= wo.estimated_hours THEN 1 ELSE 0 END) as on_time_count,
                    SUM(CASE WHEN wo.actual_hours > wo.estimated_hours THEN 1 ELSE 0 END) as delayed_count
                FROM work_orders wo
                WHERE wo.service_id = ?
                AND DATE(wo.entry_date) BETWEEN ? AND ?
                AND wo.status IN ('completed', 'delivered')
                AND wo.actual_hours IS NOT NULL";
        
        $timeStats = $this->db->fetchOn('work_orders', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        
        // Ordine cu cele mai mari întârzieri
        $sql = "SELECT 
                    wo.work_order_number,
                    v.registration_number,
                    v.brand,
                    v.model,
                    wo.estimated_hours,
                    wo.actual_hours,
                    (wo.actual_hours - wo.estimated_hours) as hours_over,
                    wo.entry_date,
                    wo.actual_completion,
                    sm.name as mechanic_name
                FROM work_orders wo
                LEFT JOIN vehicles v ON wo.vehicle_id = v.id
                LEFT JOIN service_mechanics sm ON wo.assigned_mechanic_id = sm.id
                WHERE wo.service_id = ?
                AND DATE(wo.entry_date) BETWEEN ? AND ?
                AND wo.status IN ('completed', 'delivered')
                AND wo.actual_hours > wo.estimated_hours
                ORDER BY (wo.actual_hours - wo.estimated_hours) DESC
                LIMIT 20";
        
        $delayedOrders = $this->db->fetchAllOn('work_orders', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        
        $this->render('reports/work_times', [
            'pageTitle' => 'Raport Timpi de Lucru',
            'service' => $internalService,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'timeStats' => $timeStats,
            'delayedOrders' => $delayedOrders
        ]);
    }
    
    /**
     * Raport Statistici Piese
     */
    public function partsStats() {
        $tenantId = $this->auth->getTenantId();
        $internalService = $this->serviceModel->getInternalService($tenantId);
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Top piese utilizate
        $sql = "SELECT 
                    wop.part_name,
                    wop.part_number,
                    COUNT(*) as usage_count,
                    SUM(wop.quantity) as total_quantity,
                    AVG(wop.unit_price) as avg_price,
                    SUM(wop.quantity * wop.unit_price) as total_cost,
                    GROUP_CONCAT(DISTINCT wop.supplier SEPARATOR ', ') as suppliers
                FROM work_order_parts wop
                JOIN work_orders wo ON wop.work_order_id = wo.id
                WHERE wo.service_id = ?
                AND DATE(wo.entry_date) BETWEEN ? AND ?
                GROUP BY wop.part_name, wop.part_number
                ORDER BY usage_count DESC
                LIMIT 50";
        
        $topParts = $this->db->fetchAllOn('work_order_parts', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        
        // Costuri totale piese
        $sql = "SELECT 
                    COUNT(DISTINCT wop.id) as total_parts_used,
                    SUM(wop.quantity) as total_quantity,
                    SUM(wop.quantity * wop.unit_price) as total_cost,
                    AVG(wop.quantity * wop.unit_price) as avg_part_cost
                FROM work_order_parts wop
                JOIN work_orders wo ON wop.work_order_id = wo.id
                WHERE wo.service_id = ?
                AND DATE(wo.entry_date) BETWEEN ? AND ?";
        
        $partsOverview = $this->db->fetchOn('work_order_parts', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        
        // Furnizori frecvenți
        $sql = "SELECT 
                    wop.supplier,
                    COUNT(*) as order_count,
                    SUM(wop.quantity * wop.unit_price) as total_spent
                FROM work_order_parts wop
                JOIN work_orders wo ON wop.work_order_id = wo.id
                WHERE wo.service_id = ?
                AND DATE(wo.entry_date) BETWEEN ? AND ?
                AND wop.supplier IS NOT NULL AND wop.supplier != ''
                GROUP BY wop.supplier
                ORDER BY order_count DESC
                LIMIT 10";
        
        $topSuppliers = $this->db->fetchAllOn('work_order_parts', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        
        // Evoluție lunară costuri piese
        $sql = "SELECT 
                    DATE_FORMAT(wo.entry_date, '%Y-%m') as month,
                    COUNT(DISTINCT wop.id) as parts_count,
                    SUM(wop.quantity * wop.unit_price) as total_cost
                FROM work_order_parts wop
                JOIN work_orders wo ON wop.work_order_id = wo.id
                WHERE wo.service_id = ?
                AND DATE(wo.entry_date) BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(wo.entry_date, '%Y-%m')
                ORDER BY month";
        
        $monthlyTrend = $this->db->fetchAllOn('work_order_parts', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        
        $this->render('reports/parts_stats', [
            'pageTitle' => 'Statistici Piese',
            'service' => $internalService,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'topParts' => $topParts,
            'partsOverview' => $partsOverview,
            'topSuppliers' => $topSuppliers,
            'monthlyTrend' => $monthlyTrend
        ]);
    }
    
    /**
     * Raport Activitate (Audit Log)
     */
    public function activityLog() {
        $tenantId = $this->auth->getTenantId();
        $internalService = $this->serviceModel->getInternalService($tenantId);
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $activityType = $_GET['activity_type'] ?? '';
        
        // Construim query pentru activități
        $activities = [];
        
        // 1. Work Orders create/update
        $sql = "SELECT 
                    'work_order' as type,
                    'created' as action,
                    wo.id as entity_id,
                    wo.work_order_number as entity_name,
                    wo.created_at as activity_date,
                    wo.created_by as user_id,
                    CONCAT('Ordine creată pentru vehicul ', v.registration_number) as description
                FROM work_orders wo
                LEFT JOIN vehicles v ON wo.vehicle_id = v.id
                WHERE wo.service_id = ?
                AND DATE(wo.created_at) BETWEEN ? AND ?
                " . ($activityType && $activityType == 'work_order' ? "" : "");
        
        $workOrderActivities = $this->db->fetchAllOn('work_orders', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        $activities = array_merge($activities, $workOrderActivities);
        
        // 2. Status changes (by checking updated_at vs created_at)
        $sql = "SELECT 
                    'work_order' as type,
                    'status_changed' as action,
                    wo.id as entity_id,
                    wo.work_order_number as entity_name,
                    wo.updated_at as activity_date,
                    wo.created_by as user_id,
                    CONCAT('Status schimbat în: ', wo.status) as description
                FROM work_orders wo
                WHERE wo.service_id = ?
                AND DATE(wo.updated_at) BETWEEN ? AND ?
                AND wo.updated_at != wo.created_at
                " . ($activityType && $activityType == 'status_change' ? "" : "");
        
        $statusChanges = $this->db->fetchAllOn('work_orders', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        $activities = array_merge($activities, $statusChanges);
        
        // 3. Parts added
        $sql = "SELECT 
                    'part' as type,
                    'added' as action,
                    wop.id as entity_id,
                    wop.part_name as entity_name,
                    wop.created_at as activity_date,
                    wo.created_by as user_id,
                    CONCAT('Piesă adăugată la ordinea ', wo.work_order_number, ': ', wop.quantity, 'x ', wop.part_name) as description
                FROM work_order_parts wop
                JOIN work_orders wo ON wop.work_order_id = wo.id
                WHERE wo.service_id = ?
                AND DATE(wop.created_at) BETWEEN ? AND ?
                " . ($activityType && $activityType == 'part' ? "" : "");
        
        $partActivities = $this->db->fetchAllOn('work_order_parts', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        $activities = array_merge($activities, $partActivities);
        
        // 4. Labor started/ended
        $sql = "SELECT 
                    'labor' as type,
                    'started' as action,
                    wol.id as entity_id,
                    sm.name as entity_name,
                    wol.start_time as activity_date,
                    wol.mechanic_id as user_id,
                    CONCAT('Mecanic ', sm.name, ' a început lucrul la ordinea ', wo.work_order_number) as description
                FROM work_order_labor wol
                JOIN work_orders wo ON wol.work_order_id = wo.id
                JOIN service_mechanics sm ON wol.mechanic_id = sm.id
                WHERE wo.service_id = ?
                AND DATE(wol.start_time) BETWEEN ? AND ?
                " . ($activityType && $activityType == 'labor' ? "" : "");
        
        $laborActivities = $this->db->fetchAllOn('work_order_labor', $sql, [$internalService['id'], $dateFrom, $dateTo]);
        $activities = array_merge($activities, $laborActivities);
        
        // Sort by date DESC
        usort($activities, function($a, $b) {
            return strtotime($b['activity_date']) - strtotime($a['activity_date']);
        });
        
        // Limit to 200 most recent
        $activities = array_slice($activities, 0, 200);
        
        // Statistics
        $stats = [
            'total_activities' => count($activities),
            'work_orders_created' => count(array_filter($activities, fn($a) => $a['type'] == 'work_order' && $a['action'] == 'created')),
            'status_changes' => count(array_filter($activities, fn($a) => $a['action'] == 'status_changed')),
            'parts_added' => count(array_filter($activities, fn($a) => $a['type'] == 'part')),
            'labor_sessions' => count(array_filter($activities, fn($a) => $a['type'] == 'labor'))
        ];
        
        $this->render('reports/activity_log', [
            'pageTitle' => 'Raport Activitate',
            'service' => $internalService,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'activityType' => $activityType,
            'activities' => $activities,
            'stats' => $stats
        ]);
    }
    
    /**
     * Export raport CSV
     */
    public function export() {
        $type = $_GET['type'] ?? 'profitability';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Headers pentru download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="raport_service_' . $type . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        
        $tenantId = $this->auth->getTenantId();
        $internalService = $this->serviceModel->getInternalService($tenantId);
        
        switch ($type) {
            case 'vehicle_costs':
                fputcsv($output, ['Număr Înmatriculare', 'Marcă', 'Model', 'An', 'Vizite Service', 'Cost Piese', 'Cost Manoperă', 'Cost Total']);
                
                $sql = "SELECT 
                            v.registration_number,
                            v.brand,
                            v.model,
                            v.year,
                            COUNT(DISTINCT wo.id) as visits,
                            SUM(wo.parts_cost) as parts,
                            SUM(wo.labor_cost) as labor,
                            SUM(wo.total_cost) as total
                        FROM vehicles v
                        LEFT JOIN work_orders wo ON v.id = wo.vehicle_id 
                            AND wo.service_id = ?
                            AND DATE(wo.entry_date) BETWEEN ? AND ?
                        WHERE v.status = 'active'
                        GROUP BY v.id
                        ORDER BY total DESC";
                
                $data = $this->db->fetchAllOn('vehicles', $sql, [$internalService['id'], $dateFrom, $dateTo]);
                
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row['registration_number'],
                        $row['brand'],
                        $row['model'],
                        $row['year'],
                        $row['visits'],
                        number_format($row['parts'], 2),
                        number_format($row['labor'], 2),
                        number_format($row['total'], 2)
                    ]);
                }
                break;
                
            default:
                fputcsv($output, ['Raport', 'Disponibil în curând']);
                break;
        }
        
        fclose($output);
        exit;
    }
}
