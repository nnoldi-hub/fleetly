<?php
// modules/insurance/models/insurance.php
require_once __DIR__ . '/../../../config/Database.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Model.php';

class Insurance extends Model {
    protected $table = 'insurance';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function create($data) {
        $sql = "INSERT INTO insurance (
            vehicle_id, insurance_type, policy_number, insurance_company,
            start_date, expiry_date, coverage_amount, premium_amount, deductible,
            payment_frequency, agent_name, agent_phone, agent_email,
            coverage_details, policy_file, status, notes, created_at, updated_at
        ) VALUES (
            :vehicle_id, :insurance_type, :policy_number, :insurance_company,
            :start_date, :expiry_date, :coverage_amount, :premium_amount, :deductible,
            :payment_frequency, :agent_name, :agent_phone, :agent_email,
            :coverage_details, :policy_file, :status, :notes, :created_at, :updated_at
        )";
        
        $this->db->query($sql, $data);
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
        }
        
        $sql = "UPDATE insurance SET " . implode(', ', $setParts) . " WHERE id = :id";
        $data['id'] = $id;
        
        return $this->db->query($sql, $data);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM insurance WHERE id = :id";
        return $this->db->query($sql, ['id' => $id]);
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM insurance WHERE id = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    public function getByIdWithDetails($id) {
        $sql = "SELECT i.*, 
                       v.license_plate, v.make, v.model, v.year,
                       CONCAT(v.make, ' ', v.model, ' (', v.license_plate, ')') as vehicle_info
                FROM insurance i
                LEFT JOIN vehicles v ON i.vehicle_id = v.id
                WHERE i.id = :id";
        
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    public function getAllWithDetails($conditions = [], $offset = 0, $limit = 25, $search = '') {
        $whereClause = $this->buildWhereClause($conditions, $search);
        $params = $this->buildWhereParams($conditions, $search);
        
        $sql = "SELECT i.*, 
                       v.license_plate, v.make, v.model, v.year,
                       CONCAT(v.make, ' ', v.model, ' (', v.license_plate, ')') as vehicle_info,
                       CASE 
                           WHEN i.expiry_date < CURDATE() THEN 'expired'
                           WHEN i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
                           ELSE 'valid'
                       END as urgency_status,
                       DATEDIFF(i.expiry_date, CURDATE()) as days_until_expiry
                FROM insurance i
                LEFT JOIN vehicles v ON i.vehicle_id = v.id
                {$whereClause}
                ORDER BY i.expiry_date ASC
                LIMIT {$limit} OFFSET {$offset}";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getTotalCount($conditions = [], $search = '') {
        $whereClause = $this->buildWhereClause($conditions, $search);
        $params = $this->buildWhereParams($conditions, $search);
        
        $sql = "SELECT COUNT(*) as total
                FROM insurance i
                LEFT JOIN vehicles v ON i.vehicle_id = v.id
                {$whereClause}";
        
        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }
    
    public function getAllForExport($conditions = []) {
        $whereClause = $this->buildWhereClause($conditions);
        $params = $this->buildWhereParams($conditions);
        
        $sql = "SELECT i.*, 
                       v.license_plate, v.make, v.model, v.year,
                       CONCAT(v.make, ' ', v.model, ' (', v.license_plate, ')') as vehicle_info
                FROM insurance i
                LEFT JOIN vehicles v ON i.vehicle_id = v.id
                {$whereClause}
                ORDER BY i.expiry_date ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getExpiring($days = 30) {
        $sql = "SELECT i.*, 
                       v.license_plate, v.make, v.model, v.year,
                       CONCAT(v.make, ' ', v.model, ' (', v.license_plate, ')') as vehicle_info,
                       DATEDIFF(i.end_date, CURDATE()) as days_until_expiry
                FROM insurance i
                LEFT JOIN vehicles v ON i.vehicle_id = v.id
                WHERE i.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY i.end_date ASC";
        
        return $this->db->fetchAll($sql, [$days]);
    }
    
    public function getExpiringInsurance($days = 30) {
        $sql = "SELECT i.*, 
                       v.license_plate, v.make, v.model, v.year,
                       CONCAT(v.make, ' ', v.model, ' (', v.license_plate, ')') as vehicle_info,
                       DATEDIFF(i.expiry_date, CURDATE()) as days_until_expiry
                FROM insurance i
                LEFT JOIN vehicles v ON i.vehicle_id = v.id
                WHERE i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$days} DAY)
                AND i.status = 'active'
                ORDER BY i.expiry_date ASC";
        
        return $this->db->fetchAll($sql, []);
    }
    
    public function getExpiredInsurance() {
        $sql = "SELECT i.*, 
                       v.license_plate, v.make, v.model, v.year,
                       CONCAT(v.make, ' ', v.model, ' (', v.license_plate, ')') as vehicle_info,
                       ABS(DATEDIFF(CURDATE(), i.expiry_date)) as days_expired
                FROM insurance i
                LEFT JOIN vehicles v ON i.vehicle_id = v.id
                WHERE i.expiry_date < CURDATE()
                AND i.status = 'active'
                ORDER BY i.expiry_date ASC";
        
        return $this->db->fetchAll($sql, []);
    }
    
    public function getVehicleInsurance($vehicleId) {
        $sql = "SELECT i.*, 
                       CASE 
                           WHEN i.expiry_date < CURDATE() THEN 'expired'
                           WHEN i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
                           ELSE 'valid'
                       END as urgency_status,
                       DATEDIFF(i.expiry_date, CURDATE()) as days_until_expiry
                FROM insurance i
                WHERE i.vehicle_id = :vehicle_id
                ORDER BY i.expiry_date DESC";
        
        return $this->db->fetchAll($sql, ['vehicle_id' => $vehicleId]);
    }
    
    public function getStatistics() {
        // Total de polițe
        $totalSql = "SELECT COUNT(*) as total FROM insurance WHERE status = 'active'";
        $totalResult = $this->db->fetch($totalSql, []);
        $total = $totalResult['total'] ?? 0;
        
        // Polițe expirate
        $expiredSql = "SELECT COUNT(*) as expired 
                       FROM insurance 
                       WHERE expiry_date < CURDATE() AND status = 'active'";
        $expiredResult = $this->db->fetch($expiredSql, []);
        $expired = $expiredResult['expired'] ?? 0;
        
        // Polițe care expiră în 30 de zile
        $expiringSql = "SELECT COUNT(*) as expiring 
                        FROM insurance 
                        WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
                        AND status = 'active'";
        $expiringResult = $this->db->fetch($expiringSql, []);
        $expiring = $expiringResult['expiring'] ?? 0;
        
        // Cost total prime
        $costSql = "SELECT SUM(premium_amount) as total_premium 
                    FROM insurance 
                    WHERE status = 'active'";
        $costResult = $this->db->fetch($costSql, []);
        $totalPremium = $costResult['total_premium'] ?? 0;
        
        // Statistici pe tipuri de asigurare
        $typesSql = "SELECT insurance_type, COUNT(*) as count, SUM(premium_amount) as total_premium
                     FROM insurance 
                     WHERE status = 'active'
                     GROUP BY insurance_type
                     ORDER BY count DESC";
        $types = $this->db->fetchAll($typesSql, []);
        
        return [
            'total_policies' => $total,
            'expired_policies' => $expired,
            'expiring_policies' => $expiring,
            'total_premium' => $totalPremium,
            'valid_policies' => $total - $expired,
            'types_breakdown' => $types
        ];
    }
    
    public function findByPolicyNumber($policyNumber, $excludeId = null) {
        $sql = "SELECT * FROM insurance WHERE policy_number = :policy_number";
        $params = ['policy_number' => $policyNumber];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    public function getInsuranceByCompany() {
        $sql = "SELECT insurance_company, 
                       COUNT(*) as policy_count,
                       SUM(premium_amount) as total_premium,
                       AVG(premium_amount) as average_premium
                FROM insurance 
                WHERE status = 'active'
                GROUP BY insurance_company
                ORDER BY policy_count DESC";
        
        return $this->db->fetchAll($sql, []);
    }
    
    public function getMonthlyExpirations($months = 12) {
        $sql = "SELECT 
                    DATE_FORMAT(expiry_date, '%Y-%m') as month,
                    COUNT(*) as expiring_count,
                    SUM(premium_amount) as total_premium
                FROM insurance
                WHERE expiry_date >= CURDATE() 
                AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL {$months} MONTH)
                AND status = 'active'
                GROUP BY DATE_FORMAT(expiry_date, '%Y-%m')
                ORDER BY month ASC";
        
        return $this->db->fetchAll($sql, []);
    }
    
    public function getCoverageAnalysis() {
        $sql = "SELECT 
                    insurance_type,
                    COUNT(*) as policy_count,
                    SUM(coverage_amount) as total_coverage,
                    AVG(coverage_amount) as average_coverage,
                    SUM(premium_amount) as total_premium,
                    AVG(premium_amount) as average_premium
                FROM insurance 
                WHERE status = 'active'
                GROUP BY insurance_type
                ORDER BY total_coverage DESC";
        
        return $this->db->fetchAll($sql, []);
    }
    
    private function buildWhereClause($conditions = [], $search = '') {
        $whereParts = [];
        
        if (!empty($conditions)) {
            foreach ($conditions as $field => $value) {
                if (strpos($field, ' ') !== false) {
                    // Handle operators like >=, <=, <, >
                    $whereParts[] = "i.{$field} :param_" . str_replace([' ', '>', '<', '='], '_', $field);
                } else {
                    $whereParts[] = "i.{$field} = :param_{$field}";
                }
            }
        }
        
        if (!empty($search)) {
            $searchParts = [
                "v.license_plate LIKE :search",
                "v.make LIKE :search",
                "v.model LIKE :search",
                "i.policy_number LIKE :search",
                "i.insurance_company LIKE :search",
                "i.insurance_type LIKE :search",
                "i.agent_name LIKE :search"
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
}
?>
