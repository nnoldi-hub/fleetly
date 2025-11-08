<?php
// modules/drivers/models/Driver.php
require_once __DIR__ . '/../../../config/Database.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Model.php';

class Driver extends Model {
    protected $table = 'drivers';
    
    public function getAllWithVehicle() {
        $sql = "SELECT d.*, v.registration_number, v.brand, v.model,
                       DATEDIFF(d.license_expiry_date, CURDATE()) as days_until_expiry
                FROM drivers d 
                LEFT JOIN vehicles v ON d.assigned_vehicle_id = v.id 
                ORDER BY d.name";
    return $this->db->fetchAllOn($this->table, $sql);
    }
    
    public function getActiveDrivers() {
        return $this->findAll(['status' => 'active']);
    }
    
    public function getWithExpiringLicenses($days = 30) {
        $sql = "SELECT d.*, v.registration_number, v.brand, v.model,
                       DATEDIFF(d.license_expiry_date, CURDATE()) as days_until_expiry
                FROM drivers d 
                LEFT JOIN vehicles v ON d.assigned_vehicle_id = v.id 
                WHERE d.license_expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY) 
                AND d.license_expiry_date >= CURDATE()
                AND d.status = 'active'
                ORDER BY d.license_expiry_date ASC";
    return $this->db->fetchAllOn($this->table, $sql, [$days]);
    }
    
    public function getExpiredLicenses() {
        $sql = "SELECT d.*, v.registration_number, v.brand, v.model 
                FROM drivers d 
                LEFT JOIN vehicles v ON d.assigned_vehicle_id = v.id 
                WHERE d.license_expiry_date < CURDATE() AND d.status = 'active'
                ORDER BY d.license_expiry_date DESC";
    return $this->db->fetchAllOn($this->table, $sql);
    }
    
    public function assignVehicle($driverId, $vehicleId) {
        // Dezasignează vehiculul de la șoferul curent
    $this->db->queryOn($this->table, "UPDATE drivers SET assigned_vehicle_id = NULL WHERE assigned_vehicle_id = ?", [$vehicleId]);
        
        // Asignează vehiculul la noul șofer
        return $this->update($driverId, ['assigned_vehicle_id' => $vehicleId]);
    }
    
    public function unassignVehicle($driverId) {
        return $this->update($driverId, ['assigned_vehicle_id' => null]);
    }
    
    public function getDriverPerformance($driverId, $startDate = null, $endDate = null) {
        $startDate = $startDate ?: date('Y-01-01');
        $endDate = $endDate ?: date('Y-12-31');
        
        $sql = "SELECT 
                    COUNT(DISTINCT fc.id) as total_trips,
                    SUM(fc.liters) as total_fuel_consumed,
                    SUM(fc.total_cost) as total_fuel_cost,
                    AVG(fc.liters) as avg_fuel_per_trip,
                    COUNT(DISTINCT m.id) as maintenance_incidents,
                    SUM(m.cost) as total_maintenance_cost
                FROM drivers d
                LEFT JOIN fuel_consumption fc ON d.id = fc.driver_id 
                    AND fc.fuel_date BETWEEN ? AND ?
                LEFT JOIN maintenance m ON d.assigned_vehicle_id = m.vehicle_id 
                    AND m.service_date BETWEEN ? AND ?
                WHERE d.id = ?
                GROUP BY d.id";
        
    return $this->db->fetchOn($this->table, $sql, [$startDate, $endDate, $startDate, $endDate, $driverId]);
    }
    
    public function getDriversByStatus($status) {
        return $this->findAll(['status' => $status]);
    }
    
    public function updateLicenseExpiry($id, $newExpiryDate) {
        return $this->update($id, [
            'license_expiry_date' => $newExpiryDate,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    // Stats helpers
    public function countActive(): int {
        return (int)$this->count(['status' => 'active']);
    }

    public function countInactive(): int {
        return (int)$this->count(['status' => 'inactive']);
    }

    public function countAssignedActive(): int {
        // Use explicit SQL to support IS NOT NULL condition safely
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} WHERE status = 'active' AND assigned_vehicle_id IS NOT NULL";
    $row = $this->db->fetchOn($this->table, $sql);
        return (int)($row['total'] ?? 0);
    }
}
