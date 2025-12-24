<?php
require_once __DIR__ . '/../../../core/Model.php';

/**
 * OrderItem Model - Order Line Items
 */
class OrderItem extends Model {
    protected $table = 'mp_order_items';
    
    /**
     * Get items for an order
     */
    public function getByOrderId($orderId) {
        return $this->db->fetchAll(
            "SELECT oi.*, p.image_main as product_image
             FROM {$this->table} oi
             LEFT JOIN mp_products p ON oi.product_id = p.id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC",
            [$orderId]
        );
    }
    
    /**
     * Create order item
     */
    public function create($data) {
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Create multiple order items from cart
     */
    public function createFromCart($orderId, $cartItems) {
        $success = true;
        
        foreach ($cartItems as $item) {
            $data = [
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'product_sku' => $item['sku'] ?? '',
                'product_description' => $item['description'] ?? '',
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'total' => $item['quantity'] * $item['price']
            ];
            
            $result = $this->create($data);
            
            if (!$result) {
                $success = false;
                break;
            }
        }
        
        return $success;
    }
    
    /**
     * Get total quantity and amount for an order
     */
    public function getOrderTotals($orderId) {
        $result = $this->db->fetch(
            "SELECT 
                COUNT(*) as item_count,
                SUM(quantity) as total_quantity,
                SUM(total) as total_amount
             FROM {$this->table}
             WHERE order_id = ?",
            [$orderId]
        );
        
        return [
            'item_count' => (int)($result['item_count'] ?? 0),
            'total_quantity' => (int)($result['total_quantity'] ?? 0),
            'total_amount' => (float)($result['total_amount'] ?? 0)
        ];
    }
}
