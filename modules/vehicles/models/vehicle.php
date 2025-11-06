<?php
// modules/vehicles/models/Vehicle.php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../core/database.php';
require_once __DIR__ . '/../../../core/model.php';

class Vehicle extends Model {
    protected $table = 'vehicles';
    
    public function getAllWithType() {
        $sql = "SELECT v.*, vt.name as vehicle_type_name, vt.category 
                FROM vehicles v 
                LEFT JOIN vehicle_types vt ON v.vehicle_type_id = vt.id 
                ORDER BY v.registration_number";
    return $this->db->fetchAllOn($this->table, $sql);
    }
    
    public function getByType($typeId) {
        $sql = "SELECT v.*, vt.name as vehicle_type_name 
                FROM vehicles v 
                LEFT JOIN vehicle_types vt ON v.vehicle_type_id = vt.id 
                WHERE v.vehicle_type_id = ?
                ORDER BY v.registration_number";
    return $this->db->fetchAllOn($this->table, $sql, [$typeId]);
    }
    
    public function getActiveVehicles() {
        return $this->findAll(['status' => 'active']);
    }
    
    public function getExpiringDocuments($days = 30) {
        $sql = "SELECT v.registration_number, v.brand, v.model, d.document_type, 
                       d.expiry_date, DATEDIFF(d.expiry_date, CURDATE()) as days_until_expiry
                FROM vehicles v 
                JOIN documents d ON v.id = d.vehicle_id 
                WHERE d.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY) 
                AND d.expiry_date >= CURDATE()
                AND d.status = 'active'
                ORDER BY d.expiry_date ASC";
    return $this->db->fetchAllOn($this->table, $sql, [$days]);
    }
    
    public function getMaintenanceDue($mileage_threshold = 1000) {
        $sql = "SELECT v.*, m.next_service_date, m.description as last_service
                FROM vehicles v 
                LEFT JOIN maintenance m ON v.id = m.vehicle_id 
                WHERE (v.current_mileage - COALESCE(m.mileage_at_service, 0)) > ?
                OR m.next_service_date <= CURDATE()
                ORDER BY v.registration_number";
    return $this->db->fetchAllOn($this->table, $sql, [$mileage_threshold]);
    }
    
    public function updateMileage($id, $mileage) {
        return $this->update($id, ['current_mileage' => $mileage]);
    }
    
    public function getVehicleStats($id) {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM maintenance WHERE vehicle_id = ?) as total_maintenance,
                    (SELECT SUM(cost) FROM maintenance WHERE vehicle_id = ?) as total_maintenance_cost,
                    (SELECT COUNT(*) FROM fuel_consumption WHERE vehicle_id = ?) as total_refuels,
                    (SELECT SUM(total_cost) FROM fuel_consumption WHERE vehicle_id = ?) as total_fuel_cost,
                    (SELECT AVG(liters/((SELECT current_mileage FROM vehicles WHERE id = ?) - 
                            LAG(mileage) OVER (ORDER BY fuel_date))) * 100 
                     FROM fuel_consumption WHERE vehicle_id = ?) as avg_consumption";
    return $this->db->fetchOn($this->table, $sql, [$id, $id, $id, $id, $id, $id]);
    }
    
    public function getTotalCount() {
        $sql = "SELECT COUNT(*) as count FROM vehicles WHERE status = 'active'";
    $result = $this->db->fetchOn($this->table, $sql);
        return $result['count'] ?? 0;
    }
    
    public function getById($id) {
        $sql = "SELECT v.*, vt.name as vehicle_type_name 
                FROM vehicles v 
                LEFT JOIN vehicle_types vt ON v.vehicle_type_id = vt.id 
                WHERE v.id = ?";
    return $this->db->fetchOn($this->table, $sql, [$id]);
    }
    
    public function create($data) {
        try {
            // Ensure only valid columns are inserted (avoid unknown keys)
            $allowed = [
                'registration_number','vin_number','brand','model','year','vehicle_type_id','status',
                'purchase_date','purchase_price','current_mileage','engine_capacity','fuel_type','color','notes',
                'created_at','updated_at'
            ];
            $filtered = array_intersect_key($data, array_flip($allowed));
            // Minimal required
            foreach (['registration_number','brand','model','year','vehicle_type_id'] as $req) {
                if (!isset($filtered[$req]) || $filtered[$req] === '' || $filtered[$req] === null) {
                    throw new Exception("Missing required field: $req");
                }
            }
            $columns = array_keys($filtered);
            $placeholders = array_fill(0, count($columns), '?');
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->db->queryOn($this->table, $sql, array_values($filtered));
            $id = $this->db->lastInsertIdOn($this->table);
            return (int)$id;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function searchWithFilters($search = '', $typeId = null, $status = null, $limit = null, $offset = null) {
        $sql = "SELECT v.*, vt.name as vehicle_type_name 
                FROM vehicles v 
                LEFT JOIN vehicle_types vt ON v.vehicle_type_id = vt.id 
                WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (v.registration_number LIKE ? OR v.brand LIKE ? OR v.model LIKE ? OR v.vin_number LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($typeId)) {
            $sql .= " AND v.vehicle_type_id = ?";
            $params[] = $typeId;
        }
        
        if (!empty($status)) {
            $sql .= " AND v.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY v.registration_number";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }
        
    return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    public function countWithFilters($search = '', $typeId = null, $status = null) {
        $sql = "SELECT COUNT(*) as total FROM vehicles v WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (v.registration_number LIKE ? OR v.brand LIKE ? OR v.model LIKE ? OR v.vin_number LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($typeId)) {
            $sql .= " AND v.vehicle_type_id = ?";
            $params[] = $typeId;
        }
        
        if (!empty($status)) {
            $sql .= " AND v.status = ?";
            $params[] = $status;
        }
        
    $result = $this->db->fetchOn($this->table, $sql, $params);
        return (int)($result['total'] ?? 0);
    }
}
?>
