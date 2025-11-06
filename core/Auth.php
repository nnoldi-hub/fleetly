<?php

class Auth {
    private static $instance = null;
    private $db;
    private $user = null;
    private $company = null;
    
    private function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadSession();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load user from session
     */
    private function loadSession() {
        if (isset($_SESSION['user_id'])) {
            $stmt = $this->db->prepare("
                SELECT u.*, r.slug as role_slug, r.level as role_level, r.name as role_name,
                       c.name as company_name, c.status as company_status
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN companies c ON u.company_id = c.id
                WHERE u.id = ? AND u.status = 'active'
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $this->user = $stmt->fetch(PDO::FETCH_OBJ);
            
            if ($this->user && $this->user->company_id) {
                $this->company = $this->getCompany($this->user->company_id);
            }
        }
    }
    
    /**
     * Login user
     */
    public function login($username, $password, $rememberMe = false) {
        try {
            // Check login attempts
            $stmt = $this->db->prepare("
                SELECT * FROM users WHERE (username = ? OR email = ?) AND status IN ('active', 'pending')
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$user) {
                $this->logAudit(null, null, 'login_failed', 'user', null, null, 
                    ['username' => $username, 'reason' => 'user_not_found']);
                return ['success' => false, 'message' => 'Utilizator sau parolă incorectă'];
            }
            
            // Check if account is locked
            if ($user->locked_until && strtotime($user->locked_until) > time()) {
                return ['success' => false, 'message' => 'Cont blocat temporar. Încercați mai târziu.'];
            }
            
            // Check if company is active (for non-superadmin users)
            if ($user->company_id) {
                $company = $this->getCompany($user->company_id);
                if (!$company || $company->status !== 'active') {
                    return ['success' => false, 'message' => 'Compania nu este activă'];
                }
            }
            
            // Verify password
            if (!password_verify($password, $user->password_hash)) {
                // Increment login attempts
                $attempts = $user->login_attempts + 1;
                $lockedUntil = null;
                
                if ($attempts >= 5) {
                    $lockedUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                }
                
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET login_attempts = ?, locked_until = ?
                    WHERE id = ?
                ");
                $stmt->execute([$attempts, $lockedUntil, $user->id]);
                
                $this->logAudit($user->id, $user->company_id, 'login_failed', 'user', $user->id, null,
                    ['attempts' => $attempts]);
                
                return ['success' => false, 'message' => 'Utilizator sau parolă incorectă'];
            }
            
            // Successful login
            $sessionToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime($rememberMe ? '+30 days' : '+24 hours'));
            
            // Create session
            $stmt = $this->db->prepare("
                INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user->id,
                $sessionToken,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $expiresAt
            ]);
            
            // Update user
            $stmt = $this->db->prepare("
                UPDATE users 
                SET login_attempts = 0, locked_until = NULL, 
                    last_login_at = NOW(), last_login_ip = ?
                WHERE id = ?
            ");
            $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user->id]);
            
            // Set session
            $_SESSION['user_id'] = $user->id;
            $_SESSION['session_token'] = $sessionToken;
            
            // Set cookie if remember me
            if ($rememberMe) {
                setcookie('remember_token', $sessionToken, time() + (30 * 86400), '/', '', true, true);
            }
            
            $this->logAudit($user->id, $user->company_id, 'login', 'user', $user->id);
            
            // Reload user data
            $this->loadSession();
            
            return ['success' => true, 'message' => 'Autentificare reușită', 'user' => $this->user];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Eroare la autentificare'];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if ($this->user) {
            $this->logAudit($this->user->id, $this->user->company_id ?? null, 'logout', 'user', $this->user->id);
            
            // Delete session
            if (isset($_SESSION['session_token'])) {
                $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE session_token = ?");
                $stmt->execute([$_SESSION['session_token']]);
            }
        }
        
        // Clear session
        session_destroy();
        
        // Clear cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        $this->user = null;
        $this->company = null;
    }
    
    /**
     * Check if user is authenticated
     */
    public function check() {
        return $this->user !== null;
    }
    
    /**
     * Get current user
     */
    public function user() {
        return $this->user;
    }
    
    /**
     * Get current company
     */
    public function company() {
        return $this->company;
    }

    // Intervention mode (SuperAdmin acts as a company)
    public function isActing() {
        return isset($_SESSION['acting_company']) && !empty($_SESSION['acting_company']['id']);
    }

    public function getActingCompanyId() {
        return $this->isActing() ? (int)$_SESSION['acting_company']['id'] : null;
    }

    public function getActingCompanyName() {
        return $this->isActing() ? (string)$_SESSION['acting_company']['name'] : null;
    }

    public function startActing($companyId, $companyName = null) {
        $_SESSION['acting_company'] = ['id' => (int)$companyId, 'name' => (string)($companyName ?? '')];
    }

    public function stopActing() {
        unset($_SESSION['acting_company']);
    }

    // Return effective company id for data scoping
    public function effectiveCompanyId() {
        if ($this->isActing()) return (int)$_SESSION['acting_company']['id'];
        return $this->user->company_id ?? null;
    }
    
    /**
     * Check if user has role
     */
    public function hasRole($roleSlug) {
        return $this->user && $this->user->role_slug === $roleSlug;
    }
    
    /**
     * Check if user is SuperAdmin
     */
    public function isSuperAdmin() {
        return $this->user && $this->user->role_slug === 'superadmin';
    }
    
    /**
     * Check if user is Admin
     */
    public function isAdmin() {
        return $this->user && in_array($this->user->role_slug, ['superadmin', 'admin']);
    }
    
    /**
     * Check if user has permission
     */
    public function can($permissionSlug) {
        if (!$this->user) {
            return false;
        }
        
        // SuperAdmin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ? AND p.slug = ?
        ");
        $stmt->execute([$this->user->role_id, $permissionSlug]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        
        return $result && $result->count > 0;
    }
    
    /**
     * Check multiple permissions (AND logic)
     */
    public function canAll($permissions) {
        foreach ($permissions as $permission) {
            if (!$this->can($permission)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Check multiple permissions (OR logic)
     */
    public function canAny($permissions) {
        foreach ($permissions as $permission) {
            if ($this->can($permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get user permissions
     */
    public function getPermissions() {
        if (!$this->user) {
            return [];
        }
        
        if ($this->isSuperAdmin()) {
            $stmt = $this->db->query("SELECT slug FROM permissions");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        $stmt = $this->db->prepare("
            SELECT p.slug
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ?
        ");
        $stmt->execute([$this->user->role_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get company by ID
     */
    private function getCompany($companyId) {
        $stmt = $this->db->prepare("SELECT * FROM companies WHERE id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Log audit event
     */
    public function logAudit($userId, $companyId, $action, $entityType, $entityId, $oldValues = null, $newValues = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs 
                (user_id, company_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $companyId,
                $action,
                $entityType,
                $entityId,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
        }
    }
    
    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!$this->check()) {
            header('Location: ' . ROUTE_BASE . 'login');
            exit;
        }
    }
    
    /**
     * Require role
     */
    public function requireRole($roleSlug) {
        $this->requireAuth();
        
        if (!$this->hasRole($roleSlug) && !$this->isSuperAdmin()) {
            http_response_code(403);
            die('Acces interzis');
        }
    }
    
    /**
     * Require permission
     */
    public function requirePermission($permissionSlug) {
        $this->requireAuth();
        
        if (!$this->can($permissionSlug)) {
            http_response_code(403);
            die('Nu aveți permisiunea necesară');
        }
    }
}
