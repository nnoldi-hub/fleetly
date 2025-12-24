<?php
require_once __DIR__ . '/../../../core/Model.php';

/**
 * Cart Model - Shopping Cart
 */
class Cart extends Model {
    protected $table = 'mp_cart';
    
    /**
     * Get cart items for a company/user
     */
    public function getItems($companyId, $userId) {
        return $this->db->fetchAll(
            "SELECT c.*, 
                    p.name as product_name,
                    p.slug as product_slug,
                    p.image_main as product_image,
                    p.price as current_price,
                    p.is_active as product_active,
                    (c.quantity * c.price) as item_total
             FROM {$this->table} c
             JOIN mp_products p ON c.product_id = p.id
             WHERE c.company_id = ? AND c.user_id = ?
             ORDER BY c.created_at DESC",
            [$companyId, $userId]
        );
    }
    
    /**
     * Get cart summary (totals)
     */
    public function getSummary($companyId, $userId) {
        $result = $this->db->fetch(
            "SELECT 
                COUNT(*) as item_count,
                SUM(c.quantity) as total_quantity,
                SUM(c.quantity * c.price) as subtotal
             FROM {$this->table} c
             JOIN mp_products p ON c.product_id = p.id
             WHERE c.company_id = ? AND c.user_id = ? AND p.is_active = 1",
            [$companyId, $userId]
        );
        
        return [
            'item_count' => (int)($result['item_count'] ?? 0),
            'total_quantity' => (int)($result['total_quantity'] ?? 0),
            'subtotal' => (float)($result['subtotal'] ?? 0)
        ];
    }
    
    /**
     * Add item to cart (or update quantity if exists)
     */
    public function addItem($companyId, $userId, $productId, $quantity, $price) {
        // Check if item already exists
        $existing = $this->db->fetch(
            "SELECT * FROM {$this->table} 
             WHERE company_id = ? AND user_id = ? AND product_id = ?",
            [$companyId, $userId, $productId]
        );
        
        if ($existing) {
            // Update quantity
            $newQuantity = $existing['quantity'] + $quantity;
            return $this->db->update(
                $this->table,
                [
                    'quantity' => $newQuantity,
                    'price' => $price, // Update price to current
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                ['id' => $existing['id']]
            );
        } else {
            // Insert new item
            return $this->db->insert($this->table, [
                'company_id' => $companyId,
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price
            ]);
        }
    }
    
    /**
     * Update cart item quantity
     */
    public function updateQuantity($cartItemId, $companyId, $userId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($cartItemId, $companyId, $userId);
        }
        
        return $this->db->update(
            $this->table,
            [
                'quantity' => $quantity,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => $cartItemId,
                'company_id' => $companyId,
                'user_id' => $userId
            ]
        );
    }
    
    /**
     * Remove item from cart
     */
    public function removeItem($cartItemId, $companyId, $userId) {
        return $this->db->delete($this->table, [
            'id' => $cartItemId,
            'company_id' => $companyId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Clear entire cart
     */
    public function clearCart($companyId, $userId) {
        return $this->db->delete($this->table, [
            'company_id' => $companyId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Get cart item count
     */
    public function getItemCount($companyId, $userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} 
             WHERE company_id = ? AND user_id = ?",
            [$companyId, $userId]
        );
        
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Validate cart (check if all products still active and prices)
     */
    public function validateCart($companyId, $userId) {
        $items = $this->getItems($companyId, $userId);
        $issues = [];
        
        foreach ($items as $item) {
            // Check if product still active
            if (!$item['product_active']) {
                $issues[] = [
                    'type' => 'inactive',
                    'item_id' => $item['id'],
                    'product_name' => $item['product_name'],
                    'message' => 'Produsul nu mai este disponibil'
                ];
            }
            
            // Check if price changed
            if (abs($item['price'] - $item['current_price']) > 0.01) {
                $issues[] = [
                    'type' => 'price_changed',
                    'item_id' => $item['id'],
                    'product_name' => $item['product_name'],
                    'old_price' => $item['price'],
                    'new_price' => $item['current_price'],
                    'message' => 'Prețul produsului s-a modificat'
                ];
            }
        }
        
        return $issues;
    }
}
