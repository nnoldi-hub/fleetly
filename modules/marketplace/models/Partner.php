<?php
/**
 * Partner Model
 * 
 * Gestionează partenerii/furnizorii din marketplace
 */

require_once __DIR__ . '/../../../core/Database.php';

class Partner {
    private $db;
    private $table = 'mp_partners';
    private $categoriesTable = 'mp_partner_categories';
    private $statsTable = 'mp_partner_stats';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obține toți partenerii activi
     */
    public function getAll($filters = []) {
        $where = ['p.is_active = 1'];
        $params = [];
        
        // Filtru după categorie
        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = ?';
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['category_slug'])) {
            $where[] = 'c.slug = ?';
            $params[] = $filters['category_slug'];
        }
        
        // Filtru după featured
        if (isset($filters['is_featured']) && $filters['is_featured']) {
            $where[] = 'p.is_featured = 1';
        }
        
        // Filtru după validitate
        if (!empty($filters['valid_only'])) {
            $where[] = '(p.valid_from IS NULL OR p.valid_from <= CURDATE())';
            $where[] = '(p.valid_until IS NULL OR p.valid_until >= CURDATE())';
        }
        
        // Căutare text
        if (!empty($filters['search'])) {
            $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.promotional_text LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Include inactive pentru admin
        if (!empty($filters['include_inactive'])) {
            $where = array_filter($where, function($w) {
                return $w !== 'p.is_active = 1';
            });
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, 
                       c.icon as category_icon, c.color as category_color
                FROM {$this->table} p
                LEFT JOIN {$this->categoriesTable} c ON p.category_id = c.id
                {$whereClause}
                ORDER BY p.is_featured DESC, p.sort_order ASC, p.name ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Obține un partener după ID
     */
    public function getById($id) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug,
                       c.icon as category_icon, c.color as category_color
                FROM {$this->table} p
                LEFT JOIN {$this->categoriesTable} c ON p.category_id = c.id
                WHERE p.id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Obține un partener după slug
     */
    public function getBySlug($slug) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug,
                       c.icon as category_icon, c.color as category_color
                FROM {$this->table} p
                LEFT JOIN {$this->categoriesTable} c ON p.category_id = c.id
                WHERE p.slug = ? AND p.is_active = 1";
        return $this->db->fetch($sql, [$slug]);
    }
    
    /**
     * Creează un nou partener
     */
    public function create($data) {
        // Generează slug dacă nu există
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }
        
        $sql = "INSERT INTO {$this->table} 
                (category_id, name, slug, logo, description, promotional_text, 
                 website_url, phone, email, address, discount_info, promo_code,
                 banner_image, is_featured, is_active, valid_from, valid_until, 
                 sort_order, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['category_id'] ?? 1,
            $data['name'],
            $data['slug'],
            $data['logo'] ?? null,
            $data['description'] ?? null,
            $data['promotional_text'] ?? null,
            $data['website_url'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $data['discount_info'] ?? null,
            $data['promo_code'] ?? null,
            $data['banner_image'] ?? null,
            $data['is_featured'] ?? 0,
            $data['is_active'] ?? 1,
            !empty($data['valid_from']) ? $data['valid_from'] : null,
            !empty($data['valid_until']) ? $data['valid_until'] : null,
            $data['sort_order'] ?? 0,
            $data['created_by'] ?? null
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizează un partener
     */
    public function update($id, $data) {
        // Generează slug dacă s-a schimbat numele
        if (!empty($data['name']) && empty($data['slug'])) {
            $existing = $this->getById($id);
            if ($existing && $existing['name'] !== $data['name']) {
                $data['slug'] = $this->generateSlug($data['name'], $id);
            }
        }
        
        $fields = [];
        $params = [];
        
        $allowedFields = [
            'category_id', 'name', 'slug', 'logo', 'description', 'promotional_text',
            'website_url', 'phone', 'email', 'address', 'discount_info', 'promo_code',
            'banner_image', 'is_featured', 'is_active', 'valid_from', 'valid_until', 'sort_order'
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                // Handle empty date fields
                if (in_array($field, ['valid_from', 'valid_until']) && empty($data[$field])) {
                    $params[] = null;
                } else {
                    $params[] = $data[$field];
                }
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Șterge un partener
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Incrementează contorul de vizualizări
     */
    public function incrementViews($id, $userId = null, $companyId = null) {
        // Update counter
        $sql = "UPDATE {$this->table} SET views_count = views_count + 1 WHERE id = ?";
        $this->db->query($sql, [$id]);
        
        // Log stat
        $this->logStat($id, 'view', $userId, $companyId);
    }
    
    /**
     * Incrementează contorul de click-uri
     */
    public function incrementClicks($id, $userId = null, $companyId = null) {
        // Update counter
        $sql = "UPDATE {$this->table} SET clicks_count = clicks_count + 1 WHERE id = ?";
        $this->db->query($sql, [$id]);
        
        // Log stat
        $this->logStat($id, 'click', $userId, $companyId);
    }
    
    /**
     * Înregistrează o statistică
     */
    private function logStat($partnerId, $actionType, $userId = null, $companyId = null) {
        $sql = "INSERT INTO {$this->statsTable} 
                (partner_id, company_id, user_id, action_type, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $partnerId,
            $companyId,
            $userId,
            $actionType,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
        ]);
    }
    
    /**
     * Generează un slug unic
     */
    private function generateSlug($name, $excludeId = null) {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Verifică dacă un slug există deja
     */
    private function slugExists($slug, $excludeId = null) {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->table} WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return ($result['cnt'] ?? 0) > 0;
    }
    
    /**
     * Obține partenerii featured
     */
    public function getFeatured($limit = 4) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug,
                       c.icon as category_icon, c.color as category_color
                FROM {$this->table} p
                LEFT JOIN {$this->categoriesTable} c ON p.category_id = c.id
                WHERE p.is_active = 1 AND p.is_featured = 1
                AND (p.valid_from IS NULL OR p.valid_from <= CURDATE())
                AND (p.valid_until IS NULL OR p.valid_until >= CURDATE())
                ORDER BY p.sort_order ASC, p.name ASC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    /**
     * Obține statistici pentru un partener
     */
    public function getStats($partnerId, $days = 30) {
        $sql = "SELECT 
                    COUNT(CASE WHEN action_type = 'view' THEN 1 END) as total_views,
                    COUNT(CASE WHEN action_type = 'click' THEN 1 END) as total_clicks,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT company_id) as unique_companies
                FROM {$this->statsTable}
                WHERE partner_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        return $this->db->fetch($sql, [$partnerId, $days]);
    }
}
