<?php
require_once __DIR__ . '/../../../config/Database.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Model.php';

class Maintenance extends Model {
    protected $table = 'maintenance';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function create($data) {
        $sql = "INSERT INTO maintenance (
            vehicle_id, maintenance_type, description, service_date, 
            mileage_at_service, next_service_date, next_service_mileage, provider,
            cost, priority, status, notes, created_at, updated_at
        ) VALUES (
            :vehicle_id, :maintenance_type, :description, :service_date,
            :mileage_at_service, :next_service_date, :next_service_mileage, :provider,
            :cost, :priority, :status, :notes, :created_at, :updated_at
        )";
        
    $this->db->queryOn($this->table, $sql, $data);
    return $this->db->lastInsertIdOn($this->table);
    }
    
    public function update($id, $data) {
        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
        }
        
        $sql = "UPDATE maintenance SET " . implode(', ', $setParts) . " WHERE id = :id";
        $data['id'] = $id;
        
    return $this->db->queryOn($this->table, $sql, $data);
    }
    
    public function delete($id) {
    $sql = "DELETE FROM maintenance WHERE id = :id";
    return $this->db->queryOn($this->table, $sql, ['id' => $id]);
    }
    
    public function getById($id) {
    $sql = "SELECT * FROM maintenance WHERE id = :id";
    return $this->db->fetchOn($this->table, $sql, ['id' => $id]);
    }
    
    public function getByIdWithDetails($id) {
        $sql = "SELECT m.*, 
                       v.license_plate, v.make, v.model, v.year, v.current_odometer,
                       CONCAT(v.make, ' ', v.model, ' (', v.license_plate, ')') as vehicle_info
                FROM maintenance m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                WHERE m.id = :id";
        
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    public function getAllWithDetails($conditions = [], $offset = 0, $limit = 25, $search = '', $sortBy = 'scheduled_date', $sortOrder = 'DESC') {
        $whereClause = $this->buildWhereClause($conditions, $search);
        $params = $this->buildWhereParams($conditions, $search);
        
        $sql = "SELECT m.*, 
                       v.registration_number, v.brand, v.model, v.year, v.current_mileage,
                       CONCAT(v.brand, ' ', v.model, ' (', v.registration_number, ')') as vehicle_info,
                       CASE 
                           WHEN m.service_date < CURDATE() AND m.status != 'completed' THEN 'overdue'
                           WHEN m.service_date = CURDATE() AND m.status != 'completed' THEN 'due_today'
                           WHEN m.service_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND m.status != 'completed' THEN 'due_soon'
                           ELSE 'normal'
                       END as urgency_status,
                       DATEDIFF(m.service_date, CURDATE()) as days_until_due
                FROM maintenance m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                {$whereClause}
                ORDER BY {$sortBy} {$sortOrder}
                LIMIT {$limit} OFFSET {$offset}";
        
    return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    public function getTotalCount($conditions = [], $search = '') {
        $whereClause = $this->buildWhereClause($conditions, $search);
        $params = $this->buildWhereParams($conditions, $search);
        
        $sql = "SELECT COUNT(*) as total
                FROM maintenance m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                {$whereClause}";
        
    $result = $this->db->fetchOn($this->table, $sql, $params);
        return $result['total'] ?? 0;
    }
    
    public function getAllForExport($conditions = []) {
        $whereClause = $this->buildWhereClause($conditions);
        $params = $this->buildWhereParams($conditions);
        
        $sql = "SELECT m.*, 
                       v.license_plate, v.make, v.model, v.year,
                       CONCAT(v.make, ' ', v.model, ' (', v.license_plate, ')') as vehicle_info
                FROM maintenance m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                {$whereClause}
                ORDER BY m.scheduled_date DESC";
        
    return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    public function getVehicleHistory($vehicleId, $limit = 50) {
        $sql = "SELECT m.*, 
                       v.license_plate, v.make, v.model,
                       DATEDIFF(m.completed_date, m.scheduled_date) as delay_days
                FROM maintenance m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                WHERE m.vehicle_id = :vehicle_id
                ORDER BY m.scheduled_date DESC
                LIMIT {$limit}";
        
    return $this->db->fetchAllOn($this->table, $sql, ['vehicle_id' => $vehicleId]);
    }
    
    public function getUpcomingMaintenance($vehicleId = null, $days = 30) {
        $params = [];
        $vehicleCondition = "";
        
        if ($vehicleId) {
            $vehicleCondition = "AND m.vehicle_id = :vehicle_id";
            $params['vehicle_id'] = $vehicleId;
        }
        
        $sql = "SELECT m.*, 
                       v.license_plate, v.make, v.model, v.current_odometer,
                       CONCAT(v.make, ' ', v.model, ' (', v.license_plate, ')') as vehicle_info,
                       DATEDIFF(m.scheduled_date, CURDATE()) as days_until_due,
                       CASE 
                           WHEN m.scheduled_date < CURDATE() THEN 'overdue'
                           WHEN m.scheduled_date = CURDATE() THEN 'due_today'
                           WHEN m.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'due_soon'
                           ELSE 'upcoming'
                       END as urgency_status
                FROM maintenance m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                WHERE m.status IN ('scheduled', 'in_progress')
                AND m.scheduled_date <= DATE_ADD(CURDATE(), INTERVAL {$days} DAY)
                {$vehicleCondition}
                ORDER BY m.scheduled_date ASC";
        
    return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    public function getUpcomingMaintenanceAll($days = 30) {
        return $this->getUpcomingMaintenance(null, $days);
    }
    
    public function getOverdueMaintenance() {
        $sql = "SELECT m.*, 
                       v.license_plate, v.make, v.model, v.current_odometer,
                       CONCAT(v.make, ' ', v.model, ' (', v.license_plate, ')') as vehicle_info,
                       DATEDIFF(CURDATE(), m.scheduled_date) as days_overdue
                FROM maintenance m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                WHERE m.status IN ('scheduled', 'in_progress')
                AND m.scheduled_date < CURDATE()
                ORDER BY m.scheduled_date ASC";
        
        return $this->db->fetchAll($sql, []);
    }
    
    public function getSummaryStats($conditions = []) {
        $whereClause = $this->buildWhereClause($conditions);
        $params = $this->buildWhereParams($conditions);
        
        // Total maintenance records
        $totalSql = "SELECT COUNT(*) as total FROM maintenance m";
        if (!empty($conditions)) {
            $totalSql .= " LEFT JOIN vehicles v ON m.vehicle_id = v.id {$whereClause}";
        }
        
    $totalResult = $this->db->fetchOn($this->table, $totalSql, $params);
        $totalRecords = $totalResult['total'] ?? 0;
        
        // Status breakdown
        $statusSql = "SELECT status, COUNT(*) as count FROM maintenance m";
        if (!empty($conditions)) {
            $statusSql .= " LEFT JOIN vehicles v ON m.vehicle_id = v.id {$whereClause}";
        }
        $statusSql .= " GROUP BY status";
        
    $statusBreakdown = $this->db->fetchAllOn($this->table, $statusSql, $params);
        
        // Cost totals (schema has only 'cost')
        $costSql = "SELECT 
                        SUM(cost) as total_cost,
                        AVG(cost) as average_cost,
                        COUNT(CASE WHEN cost > 0 THEN 1 END) as paid_maintenance_count
                    FROM maintenance m";
        if (!empty($conditions)) {
            $costSql .= " LEFT JOIN vehicles v ON m.vehicle_id = v.id {$whereClause}";
        }
        
    $costStats = $this->db->fetchOn($this->table, $costSql, $params);
        
        // Upcoming and overdue counts (use service_date which exists in schema)
        $urgencySql = "SELECT 
                           COUNT(CASE WHEN service_date < CURDATE() AND status != 'completed' THEN 1 END) as overdue_count,
                           COUNT(CASE WHEN service_date = CURDATE() AND status != 'completed' THEN 1 END) as due_today_count,
                           COUNT(CASE WHEN service_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status != 'completed' THEN 1 END) as due_this_week_count
                       FROM maintenance m";
        if (!empty($conditions)) {
            $urgencySql .= " LEFT JOIN vehicles v ON m.vehicle_id = v.id {$whereClause}";
        }
        
    $urgencyStats = $this->db->fetchOn($this->table, $urgencySql, $params);
        
        return [
            'total_records' => $totalRecords,
            'status_breakdown' => $statusBreakdown,
            'total_cost' => $costStats['total_cost'] ?? 0,
            'average_cost' => $costStats['average_cost'] ?? 0,
            'paid_maintenance_count' => $costStats['paid_maintenance_count'] ?? 0,
            'overdue_count' => $urgencyStats['overdue_count'] ?? 0,
            'due_today_count' => $urgencyStats['due_today_count'] ?? 0,
            'due_this_week_count' => $urgencyStats['due_this_week_count'] ?? 0
        ];
    }
    
    public function getVehicleMaintenanceStats($vehicleId) {
        $sql = "SELECT 
                    COUNT(*) as total_maintenance,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_maintenance,
                    COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as scheduled_maintenance,
                    COUNT(CASE WHEN scheduled_date < CURDATE() AND status != 'completed' THEN 1 END) as overdue_maintenance,
                    SUM(cost) as total_cost,
                    AVG(cost) as average_cost,
                    MAX(scheduled_date) as last_maintenance_date,
                    MIN(CASE WHEN status IN ('scheduled', 'in_progress') AND scheduled_date >= CURDATE() THEN scheduled_date END) as next_maintenance_date,
                    AVG(CASE WHEN completed_date IS NOT NULL AND scheduled_date IS NOT NULL 
                        THEN DATEDIFF(completed_date, scheduled_date) END) as average_delay_days
                FROM maintenance 
                WHERE vehicle_id = :vehicle_id";
        
    return $this->db->fetchOn($this->table, $sql, ['vehicle_id' => $vehicleId]);
    }
    
    public function getMaintenanceTypes() {
        $sql = "SELECT DISTINCT maintenance_type, COUNT(*) as count 
                FROM maintenance 
                GROUP BY maintenance_type 
                ORDER BY count DESC, maintenance_type ASC";
        
    return $this->db->fetchAllOn($this->table, $sql, []);
    }
    
    public function getCostAnalysis($dateFrom, $dateTo, $vehicleId = null) {
        $params = ['date_from' => $dateFrom, 'date_to' => $dateTo];
        $vehicleCondition = "";
        
        if ($vehicleId) {
            $vehicleCondition = "AND m.vehicle_id = :vehicle_id";
            $params['vehicle_id'] = $vehicleId;
        }
        
        $sql = "SELECT 
                    DATE_FORMAT(m.scheduled_date, '%Y-%m') as month,
                    COUNT(*) as maintenance_count,
                    SUM(m.cost) as total_cost,
                    SUM(m.parts_cost) as total_parts_cost,
                    SUM(m.labor_cost) as total_labor_cost,
                    AVG(m.cost) as average_cost,
                    m.maintenance_type,
                    COUNT(CASE WHEN m.status = 'completed' THEN 1 END) as completed_count,
                    v.license_plate, v.make, v.model
                FROM maintenance m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                WHERE m.scheduled_date BETWEEN :date_from AND :date_to
                {$vehicleCondition}
                GROUP BY DATE_FORMAT(m.scheduled_date, '%Y-%m'), m.maintenance_type, v.id
                ORDER BY month DESC, total_cost DESC";
        
    return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    public function getMaintenanceScheduleReport($dateFrom, $dateTo, $vehicleId = null) {
        $params = ['date_from' => $dateFrom, 'date_to' => $dateTo];
        $vehicleCondition = "";
        
        if ($vehicleId) {
            $vehicleCondition = "AND m.vehicle_id = :vehicle_id";
            $params['vehicle_id'] = $vehicleId;
        }
        
        $sql = "SELECT m.*, 
                       v.license_plate, v.make, v.model, v.current_odometer,
                       CONCAT(v.make, ' ', v.model, ' (', v.license_plate, ')') as vehicle_info,
                       CASE 
                           WHEN m.scheduled_date < CURDATE() AND m.status != 'completed' THEN 'Întârziat'
                           WHEN m.scheduled_date = CURDATE() AND m.status != 'completed' THEN 'Astăzi'
                           WHEN m.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND m.status != 'completed' THEN 'Săptămâna aceasta'
                           WHEN m.status = 'completed' THEN 'Finalizat'
                           ELSE 'Programat'
                       END as status_text,
                       DATEDIFF(m.scheduled_date, CURDATE()) as days_until_due
                FROM maintenance m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                WHERE m.scheduled_date BETWEEN :date_from AND :date_to
                {$vehicleCondition}
                ORDER BY m.scheduled_date ASC, v.license_plate ASC";
        
    return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    public function getVehiclePerformanceReport($dateFrom, $dateTo, $vehicleId = null) {
        $params = ['date_from' => $dateFrom, 'date_to' => $dateTo];
        $vehicleCondition = "";
        
        if ($vehicleId) {
            $vehicleCondition = "AND m.vehicle_id = :vehicle_id";
            $params['vehicle_id'] = $vehicleId;
        }
        
        $sql = "SELECT 
                    v.id as vehicle_id,
                    v.license_plate, 
                    v.make, 
                    v.model, 
                    v.year,
                    v.current_odometer,
                    COUNT(m.id) as total_maintenance,
                    COUNT(CASE WHEN m.status = 'completed' THEN 1 END) as completed_maintenance,
                    COUNT(CASE WHEN m.scheduled_date < CURDATE() AND m.status != 'completed' THEN 1 END) as overdue_maintenance,
                    SUM(m.cost) as total_cost,
                    AVG(m.cost) as average_cost,
                    SUM(m.parts_cost) as total_parts_cost,
                    SUM(m.labor_cost) as total_labor_cost,
                    MAX(m.scheduled_date) as last_maintenance_date,
                    MIN(CASE WHEN m.status IN ('scheduled', 'in_progress') AND m.scheduled_date >= CURDATE() THEN m.scheduled_date END) as next_maintenance_date,
                    AVG(CASE WHEN m.completed_date IS NOT NULL AND m.scheduled_date IS NOT NULL 
                        THEN DATEDIFF(m.completed_date, m.scheduled_date) END) as average_delay_days,
                    GROUP_CONCAT(DISTINCT m.maintenance_type ORDER BY m.maintenance_type SEPARATOR ', ') as maintenance_types
                FROM vehicles v
                LEFT JOIN maintenance m ON v.id = m.vehicle_id 
                    AND m.scheduled_date BETWEEN :date_from AND :date_to
                WHERE v.status = 'active'
                {$vehicleCondition}
                GROUP BY v.id, v.license_plate, v.make, v.model, v.year, v.current_odometer
                ORDER BY total_cost DESC, v.license_plate ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getProviderAnalysis($dateFrom, $dateTo, $vehicleId = null) {
        $params = ['date_from' => $dateFrom, 'date_to' => $dateTo];
        $vehicleCondition = "";
        
        if ($vehicleId) {
            $vehicleCondition = "AND m.vehicle_id = :vehicle_id";
            $params['vehicle_id'] = $vehicleId;
        }
        
        $sql = "SELECT 
                    m.service_provider,
                    COUNT(*) as total_services,
                    COUNT(CASE WHEN m.status = 'completed' THEN 1 END) as completed_services,
                    SUM(m.cost) as total_cost,
                    AVG(m.cost) as average_cost,
                    SUM(m.parts_cost) as total_parts_cost,
                    SUM(m.labor_cost) as total_labor_cost,
                    AVG(CASE WHEN m.completed_date IS NOT NULL AND m.scheduled_date IS NOT NULL 
                        THEN DATEDIFF(m.completed_date, m.scheduled_date) END) as average_delay_days,
                    GROUP_CONCAT(DISTINCT m.maintenance_type ORDER BY m.maintenance_type SEPARATOR ', ') as services_provided,
                    COUNT(DISTINCT m.vehicle_id) as vehicles_serviced
                FROM maintenance m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                WHERE m.scheduled_date BETWEEN :date_from AND :date_to
                AND m.service_provider IS NOT NULL 
                AND m.service_provider != ''
                {$vehicleCondition}
                GROUP BY m.service_provider
                ORDER BY total_cost DESC, total_services DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getMaintenanceTrends($months = 12) {
        $sql = "SELECT 
                    DATE_FORMAT(scheduled_date, '%Y-%m') as month,
                    DATE_FORMAT(scheduled_date, '%Y-%m-01') as month_start,
                    COUNT(*) as total_maintenance,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_maintenance,
                    SUM(cost) as total_cost,
                    AVG(cost) as average_cost,
                    COUNT(DISTINCT vehicle_id) as vehicles_maintained
                FROM maintenance
                WHERE scheduled_date >= DATE_SUB(CURDATE(), INTERVAL {$months} MONTH)
                GROUP BY DATE_FORMAT(scheduled_date, '%Y-%m')
                ORDER BY month_start ASC";
        
    return $this->db->fetchAllOn($this->table, $sql, []);
    }
    
    public function getMaintenanceByType($dateFrom, $dateTo) {
        $sql = "SELECT 
                    maintenance_type,
                    COUNT(*) as count,
                    SUM(cost) as total_cost,
                    AVG(cost) as average_cost,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                    COUNT(DISTINCT vehicle_id) as vehicles_count
                FROM maintenance
                WHERE scheduled_date BETWEEN :date_from AND :date_to
                GROUP BY maintenance_type
                ORDER BY total_cost DESC";
        
    return $this->db->fetchAllOn($this->table, $sql, ['date_from' => $dateFrom, 'date_to' => $dateTo]);
    }
    
    private function buildWhereClause($conditions = [], $search = '') {
        $whereParts = [];
        
        if (!empty($conditions)) {
            foreach ($conditions as $field => $value) {
                if (strpos($field, ' ') !== false) {
                    // Handle operators like >=, <=
                    $whereParts[] = "m.{$field} :param_" . str_replace([' ', '>', '<', '='], '_', $field);
                } else {
                    $whereParts[] = "m.{$field} = :param_{$field}";
                }
            }
        }
        
        if (!empty($search)) {
            $searchParts = [
                "v.license_plate LIKE :search",
                "v.make LIKE :search",
                "v.model LIKE :search",
                "m.maintenance_type LIKE :search",
                "m.description LIKE :search",
                "m.service_provider LIKE :search",
                "m.notes LIKE :search"
            ];
            $whereParts[] = "(" . implode(" OR ", $searchParts) . ")";
        }
        
        return !empty($whereParts) ? "WHERE " . implode(" AND ", $whereParts) : "";
    }
    
    private function buildWhereParams($conditions = [], $search = '') {
        $params = [];
        
        if (!empty($conditions)) {
            foreach ($conditions as $field => $value) {
                $paramName = 'param_' . str_replace([' ', '>', '<', '='], '_', $field);
                $params[$paramName] = $value;
            }
        }
        
        if (!empty($search)) {
            $params['search'] = "%{$search}%";
        }
        
        return $params;
    }
    
    public function getDueMaintenance() {
        $sql = "SELECT m.*, v.license_plate, v.make, v.model,
                       CASE 
                           WHEN m.next_service_date IS NOT NULL AND m.next_service_date <= CURDATE() THEN 'date_due'
                           WHEN m.next_service_km IS NOT NULL AND v.odometer >= m.next_service_km THEN 'km_due'
                           WHEN m.next_service_date IS NOT NULL AND m.next_service_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'date_soon'
                           WHEN m.next_service_km IS NOT NULL AND v.odometer >= (m.next_service_km - 1000) THEN 'km_soon'
                           ELSE 'not_due'
                       END as due_status
                FROM maintenance m
                LEFT JOIN vehicles v ON m.vehicle_id = v.id
                WHERE m.status != 'completed'
                  AND (
                      (m.next_service_date IS NOT NULL AND m.next_service_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY))
                      OR 
                      (m.next_service_km IS NOT NULL AND v.odometer >= (m.next_service_km - 2000))
                  )
                ORDER BY 
                    CASE 
                        WHEN m.next_service_date IS NOT NULL AND m.next_service_date <= CURDATE() THEN 1
                        WHEN m.next_service_km IS NOT NULL AND v.odometer >= m.next_service_km THEN 1
                        ELSE 2
                    END,
                    m.next_service_date ASC, m.next_service_km ASC";
        
        return $this->db->fetchAllOn($this->table, $sql, []);
    }
}
?>
