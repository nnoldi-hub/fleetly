<?php
require_once __DIR__ . '/../../../core/Model.php';

/**
 * Category Model - Marketplace Categories
 */
class Category extends Model {
    protected $table = 'mp_categories';
    
    /**
     * Get all active categories ordered by sort_order
     */
    public function getAll($activeOnly = true) {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        
        $sql .= " ORDER BY sort_order ASC, name ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get category by slug
     */
    public function getBySlug($slug) {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE slug = ? AND is_active = 1",
            [$slug]
        );
    }
    
    /**
     * Get category with product count
     */
    public function getWithProductCount($categoryId = null) {
        $sql = "SELECT c.*, COUNT(p.id) as product_count 
                FROM {$this->table} c
                LEFT JOIN mp_products p ON c.id = p.category_id AND p.is_active = 1
                WHERE c.is_active = 1";
        
        if ($categoryId) {
            $sql .= " AND c.id = ?";
            $result = $this->db->fetch($sql . " GROUP BY c.id", [$categoryId]);
        } else {
            $sql .= " GROUP BY c.id ORDER BY c.sort_order ASC";
            $result = $this->db->fetchAll($sql);
        }
        
        return $result;
    }
    
    /**
     * Create new category
     */
    public function create($data) {
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Update category
     */
    public function update($id, $data) {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }
    
    /**
     * Delete category (soft delete by setting is_active = 0)
     */
    public function delete($id) {
        return $this->db->update($this->table, ['is_active' => 0], ['id' => $id]);
    }
}
