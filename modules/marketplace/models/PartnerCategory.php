<?php
/**
 * PartnerCategory Model
 * 
 * Gestionează categoriile de parteneri
 */

require_once __DIR__ . '/../../../core/Database.php';

class PartnerCategory {
    private $db;
    private $table = 'mp_partner_categories';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obține toate categoriile
     */
    public function getAll($includeInactive = false) {
        $where = $includeInactive ? '' : 'WHERE is_active = 1';
        $sql = "SELECT * FROM {$this->table} {$where} ORDER BY sort_order ASC, name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Obține o categorie după ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Obține o categorie după slug
     */
    public function getBySlug($slug) {
        $sql = "SELECT * FROM {$this->table} WHERE slug = ? AND is_active = 1";
        return $this->db->fetch($sql, [$slug]);
    }
    
    /**
     * Creează o categorie nouă
     */
    public function create($data) {
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }
        
        $sql = "INSERT INTO {$this->table} 
                (name, slug, description, icon, color, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            $data['icon'] ?? 'fa-handshake',
            $data['color'] ?? '#007bff',
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizează o categorie
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['name', 'slug', 'description', 'icon', 'color', 'sort_order', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
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
     * Șterge o categorie
     */
    public function delete($id) {
        // Verifică dacă are parteneri
        $check = $this->db->fetch("SELECT COUNT(*) as cnt FROM mp_partners WHERE category_id = ?", [$id]);
        if (($check['cnt'] ?? 0) > 0) {
            return false; // Nu poate fi ștearsă
        }
        
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Obține categoriile cu numărul de parteneri
     */
    public function getAllWithCounts() {
        $sql = "SELECT c.*, 
                       COUNT(p.id) as partners_count,
                       SUM(CASE WHEN p.is_active = 1 THEN 1 ELSE 0 END) as active_partners_count
                FROM {$this->table} c
                LEFT JOIN mp_partners p ON c.id = p.category_id
                WHERE c.is_active = 1
                GROUP BY c.id
                ORDER BY c.sort_order ASC, c.name ASC";
        
        return $this->db->fetchAll($sql);
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
     * Verifică dacă un slug există
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
}
