<?php
require_once __DIR__ . '/../../../core/Model.php';

/**
 * Order Model - Marketplace Orders
 */
class Order extends Model {
    protected $table = 'mp_orders';
    
    /**
     * Generate unique order number
     */
    public function generateOrderNumber() {
        $prefix = 'MP';
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $orderNumber = $prefix . $date . $random;
        
        // Ensure uniqueness
        while ($this->db->fetch("SELECT id FROM {$this->table} WHERE order_number = ?", [$orderNumber])) {
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $orderNumber = $prefix . $date . $random;
        }
        
        return $orderNumber;
    }
    
    /**
     * Create new order
     */
    public function create($data) {
        if (empty($data['order_number'])) {
            $data['order_number'] = $this->generateOrderNumber();
        }
        
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Get order by ID
     */
    public function getById($orderId, $companyId = null) {
        $sql = "SELECT o.*, 
                       c.name as company_name,
                       u.name as user_name,
                       u.email as user_email
                FROM {$this->table} o
                JOIN companies c ON o.company_id = c.id
                JOIN users u ON o.user_id = u.id
                WHERE o.id = ?";
        
        $params = [$orderId];
        
        if ($companyId) {
            $sql .= " AND o.company_id = ?";
            $params[] = $companyId;
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    /**
     * Get order by order number
     */
    public function getByOrderNumber($orderNumber, $companyId = null) {
        $sql = "SELECT o.*, 
                       c.name as company_name,
                       u.name as user_name,
                       u.email as user_email
                FROM {$this->table} o
                JOIN companies c ON o.company_id = c.id
                JOIN users u ON o.user_id = u.id
                WHERE o.order_number = ?";
        
        $params = [$orderNumber];
        
        if ($companyId) {
            $sql .= " AND o.company_id = ?";
            $params[] = $companyId;
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    /**
     * Get orders for a company
     */
    public function getByCompany($companyId, $filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT o.*, u.name as user_name
                FROM {$this->table} o
                JOIN users u ON o.user_id = u.id
                WHERE o.company_id = ?";
        
        $params = [$companyId];
        
        if (!empty($filters['status'])) {
            $sql .= " AND o.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get all orders (admin)
     */
    public function getAll($filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT o.*, 
                       c.name as company_name,
                       u.name as user_name
                FROM {$this->table} o
                JOIN companies c ON o.company_id = c.id
                JOIN users u ON o.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND o.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['company_id'])) {
            $sql .= " AND o.company_id = ?";
            $params[] = $filters['company_id'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE ? OR c.name LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Count orders
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} o WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND o.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['company_id'])) {
            $sql .= " AND o.company_id = ?";
            $params[] = $filters['company_id'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE ? OR EXISTS (
                SELECT 1 FROM companies c WHERE c.id = o.company_id AND c.name LIKE ?
            ))";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }
    
    /**
     * Update order status
     */
    public function updateStatus($orderId, $status, $notes = null) {
        $data = ['status' => $status];
        
        // Set timestamp based on status
        switch ($status) {
            case 'confirmed':
                $data['confirmed_at'] = date('Y-m-d H:i:s');
                break;
            case 'completed':
                $data['completed_at'] = date('Y-m-d H:i:s');
                break;
            case 'cancelled':
                $data['cancelled_at'] = date('Y-m-d H:i:s');
                break;
        }
        
        if ($notes !== null) {
            $data['admin_notes'] = $notes;
        }
        
        return $this->db->update($this->table, $data, ['id' => $orderId]);
    }
    
    /**
     * Update order
     */
    public function update($orderId, $data) {
        return $this->db->update($this->table, $data, ['id' => $orderId]);
    }
    
    /**
     * Get order statistics
     */
    public function getStatistics($companyId = null) {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as orders_pending,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                    COALESCE(SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END), 0) as orders_today,
                    COALESCE(SUM(CASE WHEN DATE(created_at) = ? THEN total_amount ELSE 0 END), 0) as revenue_today,
                    COALESCE(SUM(CASE WHEN DATE(created_at) >= ? THEN 1 ELSE 0 END), 0) as orders_month,
                    COALESCE(SUM(CASE WHEN DATE(created_at) >= ? THEN total_amount ELSE 0 END), 0) as revenue_month,
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(AVG(total_amount), 0) as average_order_value
                FROM {$this->table}
                WHERE 1=1";
        
        $params = [$today, $today, $monthStart, $monthStart];
        
        if ($companyId) {
            $sql .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        return $this->db->fetch($sql, $params);
    }
}
