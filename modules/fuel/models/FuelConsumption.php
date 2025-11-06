<?php
// modules/fuel/models/FuelConsumption.php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../core/database.php';
require_once __DIR__ . '/../../../core/model.php';

class FuelConsumption extends Model {
    protected $table = 'fuel_consumption';
    
    public function getAllWithDetails($conditions = [], $offset = 0, $limit = 25, $search = '') {
        // Align field names with schema: fuel_date, mileage, cost_per_liter, station, location
        $sql = "SELECT fc.*, 
                       v.registration_number, v.brand, v.model, v.vin_number as vin,
                       d.name as driver_name, d.phone as driver_phone
                FROM fuel_consumption fc
                LEFT JOIN vehicles v ON fc.vehicle_id = v.id
                LEFT JOIN drivers d ON fc.driver_id = d.id";
        
        $params = [];
        $whereClauses = [];
        
        // Aplicăm condițiile
        foreach ($conditions as $column => $value) {
            if (strpos($column, '>=') !== false) {
                $realColumn = str_replace(' >=', '', $column);
                $whereClauses[] = "fc.$realColumn >= ?";
                $params[] = $value;
            } elseif (strpos($column, '<=') !== false) {
                $realColumn = str_replace(' <=', '', $column);
                $whereClauses[] = "fc.$realColumn <= ?";
                $params[] = $value;
            } else {
                $whereClauses[] = "fc.$column = ?";
                $params[] = $value;
            }
        }
        
        // Aplicăm căutarea
        if (!empty($search)) {
            $searchClause = "(v.registration_number LIKE ? OR v.brand LIKE ? OR v.model LIKE ? 
                            OR d.name LIKE ? OR fc.station LIKE ? OR fc.receipt_number LIKE ?)";
            $searchParam = "%$search%";
            $whereClauses[] = $searchClause;
            $params = array_merge($params, array_fill(0, 6, $searchParam));
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
    $sql .= " ORDER BY fc.fuel_date DESC, fc.id DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
    return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    public function getTotalCount($conditions = [], $search = '') {
    $sql = "SELECT COUNT(*) as total
        FROM fuel_consumption fc
        LEFT JOIN vehicles v ON fc.vehicle_id = v.id
        LEFT JOIN drivers d ON fc.driver_id = d.id";
        
        $params = [];
        $whereClauses = [];
        
        // Aplicăm condițiile
        foreach ($conditions as $column => $value) {
            if (strpos($column, '>=') !== false) {
                $realColumn = str_replace(' >=', '', $column);
                $whereClauses[] = "fc.$realColumn >= ?";
                $params[] = $value;
            } elseif (strpos($column, '<=') !== false) {
                $realColumn = str_replace(' <=', '', $column);
                $whereClauses[] = "fc.$realColumn <= ?";
                $params[] = $value;
            } else {
                $whereClauses[] = "fc.$column = ?";
                $params[] = $value;
            }
        }
        
        // Aplicăm căutarea
        if (!empty($search)) {
            $searchClause = "(v.registration_number LIKE ? OR v.brand LIKE ? OR v.model LIKE ? 
                            OR d.name LIKE ? OR fc.station LIKE ? OR fc.receipt_number LIKE ?)";
            $searchParam = "%$search%";
            $whereClauses[] = $searchClause;
            $params = array_merge($params, array_fill(0, 6, $searchParam));
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
    $result = $this->db->fetchOn($this->table, $sql, $params);
        return $result['total'] ?? 0;
    }
    
    public function getStatistics($conditions = []) {
        $sql = "SELECT 
                    COUNT(*) as total_records,
                    SUM(fc.liters) as total_liters,
                    SUM(fc.total_cost) as total_cost,
                    AVG(fc.cost_per_liter) as avg_price_per_liter,
                    MIN(fc.fuel_date) as first_record,
                    MAX(fc.fuel_date) as last_record,
                    COUNT(DISTINCT fc.vehicle_id) as vehicles_count,
                    COUNT(DISTINCT fc.driver_id) as drivers_count
                FROM fuel_consumption fc";
        
        $params = [];
        $whereClauses = [];
        
        // Aplicăm condițiile
        foreach ($conditions as $column => $value) {
            if (empty($value)) continue;
            
            if (strpos($column, '>=') !== false) {
                $realColumn = str_replace(' >=', '', $column);
                $whereClauses[] = "fc.$realColumn >= ?";
                $params[] = $value;
            } elseif (strpos($column, '<=') !== false) {
                $realColumn = str_replace(' <=', '', $column);
                $whereClauses[] = "fc.$realColumn <= ?";
                $params[] = $value;
            } else {
                $whereClauses[] = "fc.$column = ?";
                $params[] = $value;
            }
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
    return $this->db->fetchOn($this->table, $sql, $params);
    }
    
    public function getByVehicle($vehicleId, $limit = null) {
    $sql = "SELECT fc.*, d.name as driver_name 
                FROM fuel_consumption fc
                LEFT JOIN drivers d ON fc.driver_id = d.id
                WHERE fc.vehicle_id = ?
        ORDER BY fc.fuel_date DESC, fc.id DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
    return $this->db->fetchAllOn($this->table, $sql, [$vehicleId]);
    }
    
    public function getByDriver($driverId, $limit = null) {
    $sql = "SELECT fc.*, v.registration_number, v.brand, v.model
                FROM fuel_consumption fc
                LEFT JOIN vehicles v ON fc.vehicle_id = v.id
                WHERE fc.driver_id = ?
        ORDER BY fc.fuel_date DESC, fc.id DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
    return $this->db->fetchAllOn($this->table, $sql, [$driverId]);
    }
    
    public function getLastOdometerReading($vehicleId) {
    $sql = "SELECT mileage 
        FROM fuel_consumption 
        WHERE vehicle_id = ? 
        ORDER BY fuel_date DESC, id DESC 
        LIMIT 1";
        
    $result = $this->db->fetchOn($this->table, $sql, [$vehicleId]);
    return $result ? $result['mileage'] : null;
    }
    
    public function getLastFuelRecord($vehicleId, $beforeOdometer = null) {
    $sql = "SELECT * 
        FROM fuel_consumption 
        WHERE vehicle_id = ?";
        
        $params = [$vehicleId];
        
        if ($beforeOdometer !== null) {
            $sql .= " AND mileage < ?";
            $params[] = $beforeOdometer;
        }
        
        $sql .= " ORDER BY fuel_date DESC, id DESC LIMIT 1";
        
    return $this->db->fetchOn($this->table, $sql, $params);
    }
    
    public function getVehicleConsumptionHistory($vehicleId, $months = 12) {
        $sql = "SELECT 
                    DATE_FORMAT(fuel_date, '%Y-%m') as month,
                    SUM(liters) as total_liters,
                    SUM(total_cost) as total_cost,
                    AVG(cost_per_liter) as avg_price,
                    COUNT(*) as fill_count,
                    MAX(mileage) - MIN(mileage) as distance_traveled
                FROM fuel_consumption 
                WHERE vehicle_id = ? 
                AND fuel_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(fuel_date, '%Y-%m')
                ORDER BY month DESC";
        
    return $this->db->fetchAllOn($this->table, $sql, [$vehicleId, $months]);
    }
    
    public function getDriverConsumptionHistory($driverId, $months = 12) {
        $sql = "SELECT 
                    DATE_FORMAT(fuel_date, '%Y-%m') as month,
                    SUM(liters) as total_liters,
                    SUM(total_cost) as total_cost,
                    AVG(cost_per_liter) as avg_price,
                    COUNT(*) as fill_count,
                    COUNT(DISTINCT vehicle_id) as vehicles_used
                FROM fuel_consumption 
                WHERE driver_id = ? 
                AND fuel_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(fuel_date, '%Y-%m')
                ORDER BY month DESC";
        
    return $this->db->fetchAllOn($this->table, $sql, [$driverId, $months]);
    }
    
    public function getTopStations($limit = 10, $conditions = []) {
        $sql = "SELECT 
                    station as station_name,
                    location as station_location,
                    COUNT(*) as visit_count,
                    SUM(liters) as total_liters,
                    SUM(total_cost) as total_cost,
                    AVG(cost_per_liter) as avg_price
                FROM fuel_consumption 
                WHERE station IS NOT NULL AND station != ''";
        
        $params = [];
        
        // Aplicăm condițiile
        foreach ($conditions as $column => $value) {
            if (empty($value)) continue;
            
            if (strpos($column, '>=') !== false) {
                $realColumn = str_replace(' >=', '', $column);
                $sql .= " AND $realColumn >= ?";
                $params[] = $value;
            } elseif (strpos($column, '<=') !== false) {
                $realColumn = str_replace(' <=', '', $column);
                $sql .= " AND $realColumn <= ?";
                $params[] = $value;
            } else {
                $sql .= " AND $column = ?";
                $params[] = $value;
            }
        }
        
    $sql .= " GROUP BY station, location
                  ORDER BY visit_count DESC, total_liters DESC
                  LIMIT $limit";
        
    return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    public function getFuelTypeStatistics($conditions = []) {
        $sql = "SELECT 
                    fuel_type,
                    COUNT(*) as record_count,
                    SUM(liters) as total_liters,
                    SUM(total_cost) as total_cost,
                    AVG(cost_per_liter) as avg_price
                FROM fuel_consumption 
                WHERE fuel_type IS NOT NULL AND fuel_type != ''";
        
        $params = [];
        
        // Aplicăm condițiile
        foreach ($conditions as $column => $value) {
            if (empty($value)) continue;
            
            if (strpos($column, '>=') !== false) {
                $realColumn = str_replace(' >=', '', $column);
                $sql .= " AND $realColumn >= ?";
                $params[] = $value;
            } elseif (strpos($column, '<=') !== false) {
                $realColumn = str_replace(' <=', '', $column);
                $sql .= " AND $realColumn <= ?";
                $params[] = $value;
            } else {
                $sql .= " AND $column = ?";
                $params[] = $value;
            }
        }
        
        $sql .= " GROUP BY fuel_type
                  ORDER BY total_liters DESC";
        
    return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    public function generateReports($filters = []) {
        $reports = [];
        
        // Raport de consum pe vehicule
        $reports['vehicle_consumption'] = $this->getVehicleConsumptionReport($filters);
        
        // Raport de consum pe șoferi
        $reports['driver_consumption'] = $this->getDriverConsumptionReport($filters);
        
        // Raport de costuri pe luni
        $reports['monthly_costs'] = $this->getMonthlyCostReport($filters);
        
        // Raport de eficiență
        $reports['efficiency'] = $this->getEfficiencyReport($filters);
        
        // Raport stații de alimentare
        $reports['stations'] = $this->getTopStations(10, $this->buildConditions($filters));
        
        // Raport tipuri de combustibil
        $reports['fuel_types'] = $this->getFuelTypeStatistics($this->buildConditions($filters));
        
        return $reports;
    }
    
    private function getVehicleConsumptionReport($filters) {
        $conditions = $this->buildConditions($filters);
        
        $sql = "SELECT 
                    v.registration_number,
                    v.brand,
                    v.model,
                    COUNT(fc.id) as fill_count,
                    SUM(fc.liters) as total_liters,
                    SUM(fc.total_cost) as total_cost,
                    AVG(fc.cost_per_liter) as avg_price,
                    MAX(fc.mileage) - MIN(fc.mileage) as distance_traveled
                FROM vehicles v
                LEFT JOIN fuel_consumption fc ON v.id = fc.vehicle_id";
        
        $params = [];
        $whereClauses = [];
        
        foreach ($conditions as $column => $value) {
            if (empty($value)) continue;
            
            if (strpos($column, '>=') !== false) {
                $realColumn = str_replace(' >=', '', $column);
                $whereClauses[] = "fc.$realColumn >= ?";
                $params[] = $value;
            } elseif (strpos($column, '<=') !== false) {
                $realColumn = str_replace(' <=', '', $column);
                $whereClauses[] = "fc.$realColumn <= ?";
                $params[] = $value;
            } else {
                $whereClauses[] = "fc.$column = ?";
                $params[] = $value;
            }
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        $sql .= " GROUP BY v.id, v.registration_number, v.brand, v.model
                  HAVING fill_count > 0
                  ORDER BY total_cost DESC";
        
    return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    private function getDriverConsumptionReport($filters) {
        $conditions = $this->buildConditions($filters);
        
        $sql = "SELECT 
                    d.name as driver_name,
                    d.license_number,
                    COUNT(fc.id) as fill_count,
                    SUM(fc.liters) as total_liters,
                    SUM(fc.total_cost) as total_cost,
                    AVG(fc.cost_per_liter) as avg_price,
                    COUNT(DISTINCT fc.vehicle_id) as vehicles_used
                FROM drivers d
                LEFT JOIN fuel_consumption fc ON d.id = fc.driver_id";
        
        $params = [];
        $whereClauses = [];
        
        foreach ($conditions as $column => $value) {
            if (empty($value)) continue;
            
            if (strpos($column, '>=') !== false) {
                $realColumn = str_replace(' >=', '', $column);
                $whereClauses[] = "fc.$realColumn >= ?";
                $params[] = $value;
            } elseif (strpos($column, '<=') !== false) {
                $realColumn = str_replace(' <=', '', $column);
                $whereClauses[] = "fc.$realColumn <= ?";
                $params[] = $value;
            } else {
                $whereClauses[] = "fc.$column = ?";
                $params[] = $value;
            }
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        $sql .= " GROUP BY d.id, d.name, d.license_number
                  HAVING fill_count > 0
                  ORDER BY total_cost DESC";
        
    return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    private function getMonthlyCostReport($filters) {
        $conditions = $this->buildConditions($filters);
        
        $sql = "SELECT 
                    DATE_FORMAT(fuel_date, '%Y-%m') as month,
                    DATE_FORMAT(fuel_date, '%M %Y') as month_name,
                    COUNT(*) as fill_count,
                    SUM(liters) as total_liters,
                    SUM(total_cost) as total_cost,
                    AVG(cost_per_liter) as avg_price
                FROM fuel_consumption fc";
        
        $params = [];
        $whereClauses = [];
        
        foreach ($conditions as $column => $value) {
            if (empty($value)) continue;
            
            if (strpos($column, '>=') !== false) {
                $realColumn = str_replace(' >=', '', $column);
                $whereClauses[] = "$realColumn >= ?";
                $params[] = $value;
            } elseif (strpos($column, '<=') !== false) {
                $realColumn = str_replace(' <=', '', $column);
                $whereClauses[] = "$realColumn <= ?";
                $params[] = $value;
            } else {
                $whereClauses[] = "$column = ?";
                $params[] = $value;
            }
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
    $sql .= " GROUP BY DATE_FORMAT(fuel_date, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT 12";
        
    return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    private function getEfficiencyReport($filters) {
        $conditions = $this->buildConditions($filters);
        
        // Efficiency report requires a consumption metric which is not stored in schema.
        // We'll approximate using (liters / distance) * 100 when successive records allow it.
        // For now, return recent refuels with basic info.
        $sql = "SELECT 
                    v.registration_number,
                    v.brand,
                    v.model,
                    d.name as driver_name,
                    fc.fuel_date,
                    fc.mileage,
                    fc.liters
                FROM fuel_consumption fc
                LEFT JOIN vehicles v ON fc.vehicle_id = v.id
                LEFT JOIN drivers d ON fc.driver_id = d.id";
        
        $params = [];
        
        foreach ($conditions as $column => $value) {
            if (empty($value)) continue;
            
            if (strpos($column, '>=') !== false) {
                $realColumn = str_replace(' >=', '', $column);
                $sql .= " AND $realColumn >= ?";
                $params[] = $value;
            } elseif (strpos($column, '<=') !== false) {
                $realColumn = str_replace(' <=', '', $column);
                $sql .= " AND $realColumn <= ?";
                $params[] = $value;
            } else {
                $sql .= " AND $column = ?";
                $params[] = $value;
            }
        }
        
    $sql .= " ORDER BY fc.fuel_date DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    private function buildConditions($filters) {
        $conditions = [];
        
        if (!empty($filters['vehicle_id'])) {
            $conditions['vehicle_id'] = $filters['vehicle_id'];
        }
        
        if (!empty($filters['driver_id'])) {
            $conditions['driver_id'] = $filters['driver_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $conditions['fuel_date >='] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions['fuel_date <='] = $filters['date_to'];
        }
        
        if (!empty($filters['fuel_type'])) {
            $conditions['fuel_type'] = $filters['fuel_type'];
        }
        
        return $conditions;
    }
    
    public function findById($id) {
        return $this->find($id);
    }
    
    public function getAll() {
        return $this->findAll();
    }
    
    public function deleteByVehicle($vehicleId) {
        return $this->db->queryOn($this->table, "DELETE FROM {$this->table} WHERE vehicle_id = ?", [$vehicleId]);
    }
    
    public function deleteByDriver($driverId) {
        return $this->db->queryOn($this->table, "DELETE FROM {$this->table} WHERE driver_id = ?", [$driverId]);
    }
}
?>
