<?php

class User extends Model {
    protected $table = 'users';
    private $conn;
    
    public function __construct() {
        parent::__construct();
        $this->conn = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all users for a company
     */
    public function getByCompany($companyId, $filters = []) {
        $sql = "SELECT u.*, r.name as role_name, r.slug as role_slug,
                       creator.username as created_by_username
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN users creator ON u.created_by = creator.id
                WHERE u.company_id = ?";
        
        $params = [$companyId];
        
        if (!empty($filters['status'])) {
            $sql .= " AND u.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['role_id'])) {
            $sql .= " AND u.role_id = ?";
            $params[] = $filters['role_id'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql .= " ORDER BY u.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        $stmt = $this->conn->prepare("
            SELECT u.*, r.name as role_name, r.slug as role_slug, r.level as role_level,
                   c.name as company_name,
                   creator.username as created_by_username
            FROM users u
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN companies c ON u.company_id = c.id
            LEFT JOIN users creator ON u.created_by = creator.id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $stmt = $this->conn->prepare("
            SELECT u.*, r.slug as role_slug
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.email = ?
        ");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Create new user
     */
    public function create($data) {
        try {
            // Check if company has reached max users
            if (!empty($data['company_id'])) {
                $company = (new Company())->getById($data['company_id']);
                $userCount = $this->countByCompany($data['company_id']);
                
                if ($userCount >= $company->max_users) {
                    return ['success' => false, 'message' => 'Limita de utilizatori a fost atinsă'];
                }
            }
            
            // Check if email already exists
            if ($this->getByEmail($data['email'])) {
                return ['success' => false, 'message' => 'Email-ul este deja folosit'];
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO users 
                (company_id, role_id, username, email, password_hash, first_name, last_name, 
                 phone, status, email_verified, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $password = $data['password'] ?? bin2hex(random_bytes(8));
            
            $stmt->execute([
                $data['company_id'] ?? null,
                $data['role_id'],
                $data['username'],
                $data['email'],
                password_hash($password, PASSWORD_BCRYPT),
                $data['first_name'] ?? null,
                $data['last_name'] ?? null,
                $data['phone'] ?? null,
                $data['status'] ?? 'active',
                $data['email_verified'] ?? false,
                $data['created_by'] ?? null
            ]);
            
            $userId = $this->conn->lastInsertId();
            
            // Log audit
            Auth::getInstance()->logAudit(
                $data['created_by'] ?? null,
                $data['company_id'] ?? null,
                'create',
                'user',
                $userId,
                null,
                array_merge($data, ['password' => '[HIDDEN]'])
            );
            
            return [
                'success' => true, 
                'user_id' => $userId,
                'generated_password' => $password
            ];
            
        } catch (Exception $e) {
            error_log("User creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Eroare la crearea utilizatorului'];
        }
    }
    
    /**
     * Update user
     */
    public function update($id, $data) {
        try {
            // Get old data for audit
            $oldData = $this->getById($id);
            
            $fields = [];
            $params = [];
            
            if (isset($data['username'])) {
                $fields[] = "username = ?";
                $params[] = $data['username'];
            }
            if (isset($data['email'])) {
                $fields[] = "email = ?";
                $params[] = $data['email'];
            }
            if (isset($data['first_name'])) {
                $fields[] = "first_name = ?";
                $params[] = $data['first_name'];
            }
            if (isset($data['last_name'])) {
                $fields[] = "last_name = ?";
                $params[] = $data['last_name'];
            }
            if (isset($data['phone'])) {
                $fields[] = "phone = ?";
                $params[] = $data['phone'];
            }
            if (isset($data['role_id'])) {
                $fields[] = "role_id = ?";
                $params[] = $data['role_id'];
            }
            if (isset($data['status'])) {
                $fields[] = "status = ?";
                $params[] = $data['status'];
            }
            if (isset($data['password']) && !empty($data['password'])) {
                $fields[] = "password_hash = ?";
                $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
            }
            
            if (empty($fields)) {
                return ['success' => false, 'message' => 'Nu există date de actualizat'];
            }
            
            $params[] = $id;
            
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            // Log audit
            Auth::getInstance()->logAudit(
                Auth::getInstance()->user()->id ?? null,
                $oldData->company_id ?? null,
                'update',
                'user',
                $id,
                (array)$oldData,
                $data
            );
            
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log("User update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Eroare la actualizarea utilizatorului'];
        }
    }
    
    /**
     * Delete user
     */
    public function delete($id) {
        try {
            $user = $this->getById($id);
            
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log audit
            Auth::getInstance()->logAudit(
                Auth::getInstance()->user()->id ?? null,
                $user->company_id ?? null,
                'delete',
                'user',
                $id,
                (array)$user,
                null
            );
            
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log("User deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Eroare la ștergerea utilizatorului'];
        }
    }
    
    /**
     * Count users by company
     */
    public function countByCompany($companyId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM users WHERE company_id = ? AND status = 'active'");
        $stmt->execute([$companyId]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result->count;
    }
    
    /**
     * Get available roles for a company
     */
    public function getAvailableRoles($companyId = null) {
        if ($companyId) {
            // Get system roles and company-specific roles
            $stmt = $this->conn->prepare("
                SELECT * FROM roles 
                WHERE (is_system = 1 AND slug != 'superadmin') OR company_id = ?
                ORDER BY level ASC, name ASC
            ");
            $stmt->execute([$companyId]);
        } else {
            // SuperAdmin context - all roles
            $stmt = $this->conn->query("SELECT * FROM roles ORDER BY level ASC, name ASC");
        }
        
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Get user activity log
     */
    public function getActivityLog($userId, $limit = 50) {
        $stmt = $this->conn->prepare("
            SELECT * FROM audit_logs
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
