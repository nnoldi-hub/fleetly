<?php
require_once __DIR__ . '/../../../core/Model.php';

/**
 * Product Model - Marketplace Products
 */
class Product extends Model {
    protected $table = 'mp_products';
    
    /**
     * Get all products with filters and pagination
     */
    public function getAll($filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                FROM {$this->table} p 
                JOIN mp_categories c ON p.category_id = c.id 
                WHERE p.is_active = 1";
        
        $params = [];
        
        // Filter by category
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        // Search by name or description
        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        // Featured only
        if (!empty($filters['featured'])) {
            $sql .= " AND p.is_featured = 1";
        }
        
        // Order by
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        $sql .= " ORDER BY p.{$orderBy} {$orderDir}";
        
        // Pagination
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Count products with filters
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE is_active = 1";
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE ? OR description LIKE ? OR sku LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        if (!empty($filters['featured'])) {
            $sql .= " AND is_featured = 1";
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }
    
    /**
     * Get product by slug
     */
    public function getBySlug($slug) {
        return $this->db->fetch(
            "SELECT p.*, c.name as category_name, c.slug as category_slug 
             FROM {$this->table} p 
             JOIN mp_categories c ON p.category_id = c.id 
             WHERE p.slug = ? AND p.is_active = 1",
            [$slug]
        );
    }
    
    /**
     * Get product by ID
     */
    public function getById($id) {
        return $this->db->fetch(
            "SELECT p.*, c.name as category_name 
             FROM {$this->table} p 
             JOIN mp_categories c ON p.category_id = c.id 
             WHERE p.id = ?",
            [$id]
        );
    }
    
    /**
     * Get featured products
     */
    public function getFeatured($limit = 6) {
        return $this->db->fetchAll(
            "SELECT p.*, c.name as category_name 
             FROM {$this->table} p 
             JOIN mp_categories c ON p.category_id = c.id 
             WHERE p.is_active = 1 AND p.is_featured = 1 
             ORDER BY p.created_at DESC 
             LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * Get related products (same category)
     */
    public function getRelated($productId, $categoryId, $limit = 4) {
        return $this->db->fetchAll(
            "SELECT p.*, c.name as category_name 
             FROM {$this->table} p 
             JOIN mp_categories c ON p.category_id = c.id 
             WHERE p.is_active = 1 
             AND p.category_id = ? 
             AND p.id != ? 
             ORDER BY RAND() 
             LIMIT ?",
            [$categoryId, $productId, $limit]
        );
    }
    
    /**
     * Create new product
     */
    public function create($data) {
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Update product
     */
    public function update($id, $data) {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }
    
    /**
     * Delete product (soft delete)
     */
    public function delete($id) {
        return $this->db->update($this->table, ['is_active' => 0], ['id' => $id]);
    }
    
    /**
     * Generate unique slug from name
     */
    public function generateSlug($name, $existingId = null) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $sql = "SELECT id FROM {$this->table} WHERE slug = ?";
            $params = [$slug];
            
            if ($existingId) {
                $sql .= " AND id != ?";
                $params[] = $existingId;
            }
            
            $existing = $this->db->fetch($sql, $params);
            
            if (!$existing) {
                break;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}
