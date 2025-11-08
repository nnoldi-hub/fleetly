<?php
// Ensure Database class is available
require_once __DIR__ . '/database.php';

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->fetchOn($this->table, $sql, [$id]);
    }
    
    public function findAll($conditions = [], $limit = null, $offset = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "$column = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        if ($limit) {
            $sql .= " LIMIT $limit";
            if ($offset) {
                $sql .= " OFFSET $offset";
            }
        }
        
        return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    public function create($data) {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->db->queryOn($this->table, $sql, array_values($data));
        return $this->db->lastInsertIdOn($this->table);
    }
    
    public function update($id, $data) {
        $columns = array_keys($data);
        $setClauses = array_map(function($col) { return "$col = ?"; }, $columns);
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . 
               " WHERE {$this->primaryKey} = ?";
        
        $params = array_values($data);
        $params[] = $id;
        
        return $this->db->queryOn($this->table, $sql, $params);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->queryOn($this->table, $sql, [$id]);
    }
    
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "$column = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        $result = $this->db->fetchOn($this->table, $sql, $params);
        return $result['total'];
    }
}