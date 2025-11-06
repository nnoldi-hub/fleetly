<?php
// modules/documents/models/Document.php
class Document extends Model {
    protected $table = 'documents';
    
    public function getAllWithVehicle() {
        $sql = "SELECT d.*, v.registration_number, v.brand, v.model 
                FROM documents d 
                LEFT JOIN vehicles v ON d.vehicle_id = v.id 
                ORDER BY d.expiry_date ASC";
    return $this->db->fetchAllOn($this->table, $sql);
    }
    
    public function getExpiring($days = 30) {
        $sql = "SELECT d.*, v.registration_number, v.brand, v.model,
                       DATEDIFF(d.expiry_date, CURDATE()) as days_until_expiry
                FROM documents d 
                LEFT JOIN vehicles v ON d.vehicle_id = v.id 
                WHERE d.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY) 
                AND d.expiry_date >= CURDATE()
                AND d.status = 'active'
                ORDER BY d.expiry_date ASC";
    return $this->db->fetchAllOn($this->table, $sql, [$days]);
    }
    
    public function getByVehicle($vehicleId) {
        $sql = "SELECT * FROM documents WHERE vehicle_id = ? ORDER BY document_type, expiry_date DESC";
    return $this->db->fetchAllOn($this->table, $sql, [$vehicleId]);
    }
    
    public function getExpired() {
        $sql = "SELECT d.*, v.registration_number, v.brand, v.model 
                FROM documents d 
                LEFT JOIN vehicles v ON d.vehicle_id = v.id 
                WHERE d.expiry_date < CURDATE() AND d.status = 'active'
                ORDER BY d.expiry_date DESC";
    return $this->db->fetchAllOn($this->table, $sql);
    }
    
    public function getByType($documentType) {
        $sql = "SELECT d.*, v.registration_number, v.brand, v.model 
                FROM documents d 
                LEFT JOIN vehicles v ON d.vehicle_id = v.id 
                WHERE d.document_type = ?
                ORDER BY d.expiry_date ASC";
    return $this->db->fetchAllOn($this->table, $sql, [$documentType]);
    }
    
    public function getCostsSummary($startDate = null, $endDate = null) {
        $sql = "SELECT document_type, 
                       COUNT(*) as total_documents,
                       SUM(cost) as total_cost,
                       AVG(cost) as average_cost,
                       MIN(cost) as min_cost,
                       MAX(cost) as max_cost
                FROM documents 
                WHERE 1=1";
        
        $params = [];
        if ($startDate) {
            $sql .= " AND issue_date >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= " AND issue_date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " GROUP BY document_type ORDER BY total_cost DESC";
        
    return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    public function markAsExpired() {
        $sql = "UPDATE documents SET status = 'expired' 
                WHERE expiry_date < CURDATE() AND status = 'active'";
    return $this->db->queryOn($this->table, $sql);
    }
    
    public function renewDocument($id, $newExpiryDate, $newCost = null, $newProvider = null) {
        $updateData = [
            'expiry_date' => $newExpiryDate,
            'status' => 'active',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($newCost !== null) {
            $updateData['cost'] = $newCost;
        }
        
        if ($newProvider !== null) {
            $updateData['provider'] = $newProvider;
        }
        
        return $this->update($id, $updateData);
    }
}