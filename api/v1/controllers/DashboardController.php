<?php
/**
 * Dashboard API Controller
 * 
 * Furnizează statistici și alerte pentru dashboard-ul mobil.
 * Compatibil cu structura existentă a bazei de date.
 */
class DashboardController {
    
    private $db;
    private $tenantDb;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Initialize tenant database
     */
    private function initTenantDb() {
        // In single-tenant mode, use core DB directly
        if (method_exists('DatabaseConfig', 'getTenancyMode') && DatabaseConfig::getTenancyMode() === 'single') {
            $this->tenantDb = $this->db;
            return;
        }
        
        $companyId = AuthMiddleware::companyId();
        if ($companyId) {
            Database::getInstance()->setTenantDatabaseByCompanyId($companyId);
            $this->tenantDb = Database::getInstance()->getTenantPdo();
        }
        if (!$this->tenantDb) {
            $this->tenantDb = $this->db;
        }
    }
    
    /**
     * Safe query - returns default on error
     */
    private function safeQuery($sql, $default = 0) {
        try {
            $stmt = $this->tenantDb->query($sql);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log('Dashboard query warning: ' . $e->getMessage());
            return (object)['total' => $default, 'count' => $default, 'total_cost' => 0];
        }
    }
    
    /**
     * GET /api/v1/dashboard/stats
     */
    public function stats() {
        $this->initTenantDb();
        
        try {
            $stats = [];
            
            // Total vehicles (using status != 'deleted' for compatibility)
            $result = $this->safeQuery("
                SELECT COUNT(*) as total FROM vehicles 
                WHERE status != 'deleted' OR status IS NULL
            ");
            $stats['total_vehicles'] = (int)$result->total;
            
            // Active vehicles
            $result = $this->safeQuery("
                SELECT COUNT(*) as total FROM vehicles 
                WHERE status = 'active'
            ");
            $stats['active_vehicles'] = (int)$result->total;
            
            // Total drivers
            $result = $this->safeQuery("SELECT COUNT(*) as total FROM drivers");
            $stats['total_drivers'] = (int)$result->total;
            
            // Active drivers 
            $result = $this->safeQuery("
                SELECT COUNT(*) as total FROM drivers 
                WHERE status = 'active' OR status IS NULL
            ");
            $stats['active_drivers'] = (int)$result->total;
            
            // Documents expiring in next 30 days
            $result = $this->safeQuery("
                SELECT COUNT(*) as total FROM documents 
                WHERE expiry_date IS NOT NULL 
                AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ");
            $stats['expiring_documents'] = (int)$result->total;
            
            // Maintenance scheduled
            $result = $this->safeQuery("
                SELECT COUNT(*) as total FROM maintenance 
                WHERE status = 'scheduled' 
                AND (next_service_date IS NULL OR next_service_date >= CURDATE())
            ");
            $stats['scheduled_maintenance'] = (int)$result->total;
            
            // Fuel this month (using fuel_consumption table)
            $result = $this->safeQuery("
                SELECT COUNT(*) as count, COALESCE(SUM(total_cost), 0) as total_cost
                FROM fuel_consumption 
                WHERE MONTH(fill_date) = MONTH(CURDATE())
                AND YEAR(fill_date) = YEAR(CURDATE())
            ");
            $stats['fuel_entries_this_month'] = (int)$result->count;
            $stats['fuel_cost_this_month'] = (float)$result->total_cost;
            
            // Alerts count
            $stats['alerts_count'] = $stats['expiring_documents'];
            
            ApiResponse::success([
                'stats' => $stats,
                'generated_at' => date('c')
            ]);
            
        } catch (PDOException $e) {
            error_log('Dashboard stats error: ' . $e->getMessage());
            ApiResponse::error('Failed to load dashboard stats', 500);
        }
    }
    
    /**
     * GET /api/v1/dashboard/alerts
     */
    public function alerts() {
        $this->initTenantDb();
        
        try {
            $alerts = [];
            
            // Expiring documents
            try {
                $stmt = $this->tenantDb->query("
                    SELECT d.id, d.name, d.type as document_type, d.expiry_date,
                           v.registration_number as vehicle
                    FROM documents d
                    LEFT JOIN vehicles v ON d.vehicle_id = v.id
                    WHERE d.expiry_date IS NOT NULL 
                    AND d.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                    ORDER BY d.expiry_date ASC
                    LIMIT 10
                ");
                
                while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                    $daysUntil = (int)ceil((strtotime($row->expiry_date) - time()) / 86400);
                    $alerts[] = [
                        'id' => 'doc_' . $row->id,
                        'type' => 'document_expiring',
                        'title' => 'Document expiră: ' . $row->name,
                        'message' => $row->vehicle ? "Vehicul: {$row->vehicle}" : 'Document general',
                        'priority' => $daysUntil <= 7 ? 'high' : ($daysUntil <= 14 ? 'medium' : 'low'),
                        'expiry_date' => $row->expiry_date,
                        'days_until' => $daysUntil,
                        'data' => ['document_id' => (int)$row->id]
                    ];
                }
            } catch (PDOException $e) {
                // Documents table may have different structure
            }
            
            // License expiring (from drivers table)
            try {
                $stmt = $this->tenantDb->query("
                    SELECT id, name, license_expiry_date
                    FROM drivers
                    WHERE license_expiry_date IS NOT NULL 
                    AND license_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                    ORDER BY license_expiry_date ASC
                    LIMIT 10
                ");
                
                while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                    $daysUntil = (int)ceil((strtotime($row->license_expiry_date) - time()) / 86400);
                    $alerts[] = [
                        'id' => 'license_' . $row->id,
                        'type' => 'license_expiring',
                        'title' => 'Permis expiră',
                        'message' => "Șofer: {$row->name}",
                        'priority' => $daysUntil <= 7 ? 'high' : ($daysUntil <= 14 ? 'medium' : 'low'),
                        'expiry_date' => $row->license_expiry_date,
                        'days_until' => $daysUntil,
                        'data' => ['driver_id' => (int)$row->id]
                    ];
                }
            } catch (PDOException $e) {
                // Drivers license query failed
            }
            
            // Sort by days until expiry
            usort($alerts, fn($a, $b) => $a['days_until'] - $b['days_until']);
            
            ApiResponse::success([
                'alerts' => $alerts,
                'total' => count($alerts)
            ]);
            
        } catch (Exception $e) {
            error_log('Dashboard alerts error: ' . $e->getMessage());
            ApiResponse::error('Failed to load alerts', 500);
        }
    }
}
