<?php
class VehicleType extends Model {
    protected $table = 'vehicle_types';
    
    public function getByCategory($category) {
        return $this->findAll(['category' => $category]);
    }
    
    public function getAllWithVehicleCount() {
        $sql = "SELECT vt.*, COUNT(v.id) as vehicle_count 
                FROM vehicle_types vt 
                LEFT JOIN vehicles v ON vt.id = v.vehicle_type_id 
                GROUP BY vt.id 
                ORDER BY vt.category, vt.name";
    return $this->db->fetchAllOn($this->table, $sql);
    }
    
    public function getByName($name) {
        $sql = "SELECT * FROM {$this->table} WHERE name = ?";
    return $this->db->fetchOn($this->table, $sql, [$name]);
    }
    
    public function countVehiclesByType($typeId) {
        $sql = "SELECT COUNT(*) as count FROM vehicles WHERE vehicle_type_id = ?";
    $result = $this->db->fetchOn($this->table, $sql, [$typeId]);
        return $result ? (int)$result['count'] : 0;
    }
    
    // Folosim metodele din clasa de bază pentru create și update
    public function getById($id) {
        return $this->find($id);
    }
    
    public function getAll() {
        return $this->findAll();
    }
}
