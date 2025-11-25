<?php
// modules/service/models/Part.php

require_once __DIR__ . '/../../../core/Model.php';

class Part extends Model {
    protected $table = 'service_parts';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get all parts with optional filters
     */
    public function getAllParts($filters = []) {
        $sql = "SELECT p.*, 
                       COALESCE(SUM(pu.quantity), 0) as total_used,
                       (p.quantity_in_stock - COALESCE(SUM(pu.quantity), 0)) as available_quantity
                FROM service_parts p
                LEFT JOIN service_parts_usage pu ON p.id = pu.part_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (p.part_number LIKE ? OR p.name LIKE ? OR p.manufacturer LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND p.category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['low_stock'])) {
            $sql .= " AND p.quantity_in_stock <= p.minimum_quantity";
        }
        
        $sql .= " GROUP BY p.id ORDER BY p.name ASC";
        
        return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    /**
     * Get part by ID
     */
    public function getPartById($id) {
        $sql = "SELECT p.*, 
                       COALESCE(SUM(pu.quantity), 0) as total_used,
                       (p.quantity_in_stock - COALESCE(SUM(pu.quantity), 0)) as available_quantity
                FROM service_parts p
                LEFT JOIN service_parts_usage pu ON p.id = pu.part_id
                WHERE p.id = ?
                GROUP BY p.id";
        
        return $this->db->fetchOn($this->table, $sql, [$id]);
    }
    
    /**
     * Create new part
     */
    public function createPart($data) {
        $sql = "INSERT INTO service_parts (
                    part_number, name, description, category, manufacturer,
                    unit_price, sale_price, quantity_in_stock, minimum_quantity,
                    unit_of_measure, location, supplier, supplier_part_number,
                    notes, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $this->db->queryOn($this->table, $sql, [
            $data['part_number'],
            $data['name'],
            $data['description'] ?? null,
            $data['category'],
            $data['manufacturer'] ?? null,
            $data['unit_price'],
            $data['sale_price'] ?? $data['unit_price'],
            $data['quantity_in_stock'] ?? 0,
            $data['minimum_quantity'] ?? 0,
            $data['unit_of_measure'] ?? 'buc',
            $data['location'] ?? null,
            $data['supplier'] ?? null,
            $data['supplier_part_number'] ?? null,
            $data['notes'] ?? null
        ]);
        
        return $this->db->lastInsertIdOn($this->table);
    }
    
    /**
     * Update part
     */
    public function updatePart($id, $data) {
        $sql = "UPDATE service_parts SET
                    part_number = ?,
                    name = ?,
                    description = ?,
                    category = ?,
                    manufacturer = ?,
                    unit_price = ?,
                    sale_price = ?,
                    quantity_in_stock = ?,
                    minimum_quantity = ?,
                    unit_of_measure = ?,
                    location = ?,
                    supplier = ?,
                    supplier_part_number = ?,
                    notes = ?,
                    updated_at = NOW()
                WHERE id = ?";
        
        return $this->db->queryOn($this->table, $sql, [
            $data['part_number'],
            $data['name'],
            $data['description'] ?? null,
            $data['category'],
            $data['manufacturer'] ?? null,
            $data['unit_price'],
            $data['sale_price'] ?? $data['unit_price'],
            $data['quantity_in_stock'] ?? 0,
            $data['minimum_quantity'] ?? 0,
            $data['unit_of_measure'] ?? 'buc',
            $data['location'] ?? null,
            $data['supplier'] ?? null,
            $data['supplier_part_number'] ?? null,
            $data['notes'] ?? null,
            $id
        ]);
    }
    
    /**
     * Delete part
     */
    public function deletePart($id) {
        // Check if part is used in any work orders
        $sql = "SELECT COUNT(*) as cnt FROM service_parts_usage WHERE part_id = ?";
        $result = $this->db->fetchOn('service_parts_usage', $sql, [$id]);
        
        if ($result['cnt'] > 0) {
            return ['success' => false, 'message' => 'Piesa nu poate fi stearsa - este folosita in ordine de lucru'];
        }
        
        $sql = "DELETE FROM service_parts WHERE id = ?";
        $this->db->queryOn($this->table, $sql, [$id]);
        
        return ['success' => true, 'message' => 'Piesa a fost stearsa cu succes'];
    }
    
    /**
     * Add stock to part
     */
    public function addStock($partId, $quantity, $notes = null) {
        // Update stock
        $sql = "UPDATE service_parts SET 
                    quantity_in_stock = quantity_in_stock + ?,
                    updated_at = NOW()
                WHERE id = ?";
        
        $this->db->queryOn($this->table, $sql, [$quantity, $partId]);
        
        // Log transaction
        $this->logTransaction($partId, 'in', $quantity, $notes);
        
        return true;
    }
    
    /**
     * Remove stock from part
     */
    public function removeStock($partId, $quantity, $notes = null) {
        // Check available stock
        $part = $this->getPartById($partId);
        if ($part['available_quantity'] < $quantity) {
            return ['success' => false, 'message' => 'Stoc insuficient'];
        }
        
        // Update stock
        $sql = "UPDATE service_parts SET 
                    quantity_in_stock = quantity_in_stock - ?,
                    updated_at = NOW()
                WHERE id = ?";
        
        $this->db->queryOn($this->table, $sql, [$quantity, $partId]);
        
        // Log transaction
        $this->logTransaction($partId, 'out', $quantity, $notes);
        
        return ['success' => true];
    }
    
    /**
     * Log stock transaction
     */
    private function logTransaction($partId, $type, $quantity, $notes) {
        $sql = "INSERT INTO service_parts_transactions 
                (part_id, transaction_type, quantity, notes, created_at)
                VALUES (?, ?, ?, ?, NOW())";
        
        $this->db->queryOn('service_parts_transactions', $sql, [
            $partId, $type, $quantity, $notes
        ]);
    }
    
    /**
     * Get part usage history
     */
    public function getPartUsageHistory($partId, $limit = 50) {
        $sql = "SELECT pu.*, wo.work_order_number, v.registration_number,
                       CONCAT(v.brand, ' ', v.model) as vehicle_name
                FROM service_parts_usage pu
                INNER JOIN service_work_orders wo ON pu.work_order_id = wo.id
                INNER JOIN vehicles v ON wo.vehicle_id = v.id
                WHERE pu.part_id = ?
                ORDER BY pu.created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAllOn('service_parts_usage', $sql, [$partId, $limit]);
    }
    
    /**
     * Get stock transactions
     */
    public function getStockTransactions($partId, $limit = 50) {
        $sql = "SELECT * FROM service_parts_transactions
                WHERE part_id = ?
                ORDER BY created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAllOn('service_parts_transactions', $sql, [$partId, $limit]);
    }
    
    /**
     * Get low stock parts
     */
    public function getLowStockParts() {
        $sql = "SELECT p.*, 
                       COALESCE(SUM(pu.quantity), 0) as total_used,
                       (p.quantity_in_stock - COALESCE(SUM(pu.quantity), 0)) as available_quantity
                FROM service_parts p
                LEFT JOIN service_parts_usage pu ON p.id = pu.part_id
                GROUP BY p.id
                HAVING available_quantity <= p.minimum_quantity
                ORDER BY available_quantity ASC";
        
        return $this->db->fetchAllOn($this->table, $sql, []);
    }
    
    /**
     * Get parts statistics
     */
    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_parts,
                    SUM(quantity_in_stock * unit_price) as total_stock_value,
                    SUM(CASE WHEN quantity_in_stock <= minimum_quantity THEN 1 ELSE 0 END) as low_stock_count
                FROM service_parts";
        
        return $this->db->fetchOn($this->table, $sql, []);
    }
    
    /**
     * Get categories
     */
    public function getCategories() {
        $sql = "SELECT DISTINCT category FROM service_parts WHERE category IS NOT NULL ORDER BY category";
        return $this->db->fetchAllOn($this->table, $sql, []);
    }
}
