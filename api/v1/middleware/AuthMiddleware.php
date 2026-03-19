<?php
/**
 * Auth Middleware
 * 
 * Verifică autentificarea JWT pentru rutele protejate.
 */
class AuthMiddleware {
    
    private static $currentUser = null;
    private static $currentCompanyId = null;
    private static $tokenData = null;
    
    /**
     * Main middleware handler
     */
    public static function handle() {
        $token = JwtHandler::getTokenFromHeader();
        
        if (!$token) {
            ApiResponse::unauthorized('No token provided');
        }
        
        $result = JwtHandler::validateToken($token);
        
        if (!$result['valid']) {
            if ($result['code'] === 'TOKEN_EXPIRED') {
                ApiResponse::error('Token expired', 401, ['code' => 'TOKEN_EXPIRED']);
            }
            ApiResponse::unauthorized('Invalid token');
        }
        
        // Check token type
        if (isset($result['data']['type']) && $result['data']['type'] !== 'access') {
            ApiResponse::unauthorized('Invalid token type. Use access token.');
        }
        
        // Store token data
        self::$tokenData = $result['data'];
        self::$currentCompanyId = $result['data']['company_id'] ?? null;
        
        // Load user from database
        self::loadUser($result['data']['sub']);
        
        // Setup tenant database if applicable
        if (self::$currentCompanyId) {
            try {
                Database::getInstance()->setTenantDatabaseByCompanyId(self::$currentCompanyId);
            } catch (Exception $e) {
                error_log('Failed to set tenant database: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Load user from database
     */
    private static function loadUser($userId) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.email, u.first_name, u.last_name, 
                   u.company_id, u.status, u.role_id,
                   r.slug as role_slug, r.name as role_name, r.level as role_level
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = ? AND u.status = 'active'
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);
        
        if (!$user) {
            ApiResponse::unauthorized('User not found or inactive');
        }
        
        self::$currentUser = $user;
    }
    
    /**
     * Get current authenticated user
     */
    public static function user() {
        return self::$currentUser;
    }
    
    /**
     * Get current user ID
     */
    public static function userId() {
        return self::$currentUser ? self::$currentUser->id : null;
    }
    
    /**
     * Get current company ID
     */
    public static function companyId() {
        return self::$currentCompanyId;
    }
    
    /**
     * Get token data
     */
    public static function tokenData() {
        return self::$tokenData;
    }
    
    /**
     * Check if user has role
     */
    public static function hasRole($role) {
        if (!self::$currentUser) {
            return false;
        }
        
        $roles = is_array($role) ? $role : [$role];
        return in_array(self::$currentUser->role_slug, $roles);
    }
    
    /**
     * Require admin role
     */
    public static function requireAdmin() {
        self::handle();
        
        if (!self::hasRole(['admin', 'superadmin'])) {
            ApiResponse::forbidden('Admin access required');
        }
    }
    
    /**
     * Require superadmin role
     */
    public static function requireSuperAdmin() {
        self::handle();
        
        if (!self::hasRole('superadmin')) {
            ApiResponse::forbidden('SuperAdmin access required');
        }
    }
    
    /**
     * Check if user can access resource
     */
    public static function canAccess($resourceCompanyId) {
        // SuperAdmin can access everything
        if (self::hasRole('superadmin')) {
            return true;
        }
        
        // Users can only access their company's resources
        return self::$currentCompanyId == $resourceCompanyId;
    }
    
    /**
     * Get role level (for permission hierarchy)
     */
    public static function roleLevel() {
        return self::$currentUser ? self::$currentUser->role_level : 0;
    }
}
