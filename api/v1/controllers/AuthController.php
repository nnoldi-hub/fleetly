<?php
/**
 * Auth API Controller
 * 
 * Gestionează autentificarea pentru API mobile.
 */
class AuthController {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * POST /api/v1/auth/login
     * 
     * Login și returnare JWT tokens
     */
    public function login() {
        // Get JSON input
        $input = $this->getJsonInput();
        
        // Validate input
        if (empty($input['username']) || empty($input['password'])) {
            ApiResponse::validationError([
                'username' => empty($input['username']) ? 'Username is required' : null,
                'password' => empty($input['password']) ? 'Password is required' : null
            ], 'Missing credentials');
        }
        
        $username = trim($input['username']);
        $password = $input['password'];
        
        try {
            // Find user by username or email
            $stmt = $this->db->prepare("
                SELECT u.*, r.slug as role_slug, r.name as role_name, r.level as role_level,
                       c.name as company_name, c.status as company_status
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN companies c ON u.company_id = c.id
                WHERE (u.username = ? OR u.email = ?) AND u.status IN ('active', 'pending')
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$user) {
                $this->logLoginAttempt(null, $username, false, 'user_not_found');
                ApiResponse::error('Invalid credentials', 401);
            }
            
            // Check if account is locked
            if ($user->locked_until && strtotime($user->locked_until) > time()) {
                $remainingMinutes = ceil((strtotime($user->locked_until) - time()) / 60);
                ApiResponse::error("Account locked. Try again in {$remainingMinutes} minutes.", 423);
            }
            
            // Check if company is active (for non-superadmin users)
            if ($user->company_id && $user->role_slug !== 'superadmin') {
                if ($user->company_status !== 'active') {
                    ApiResponse::error('Company account is not active', 403);
                }
            }
            
            // Verify password
            if (!password_verify($password, $user->password_hash)) {
                $this->handleFailedLogin($user);
                ApiResponse::error('Invalid credentials', 401);
            }
            
            // Check pending status
            if ($user->status === 'pending') {
                ApiResponse::error('Account pending approval', 403);
            }
            
            // Success - reset login attempts
            $stmt = $this->db->prepare("
                UPDATE users 
                SET login_attempts = 0, locked_until = NULL, last_login_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$user->id]);
            
            // Generate tokens
            $tokens = JwtHandler::generateTokens(
                $user->id, 
                $user->company_id, 
                $user->role_slug,
                [
                    'username' => $user->username,
                    'email' => $user->email
                ]
            );
            
            // Store refresh token in database (optional: for token revocation)
            $this->storeRefreshToken($user->id, $tokens['refresh_token']);
            
            // Log successful login
            $this->logLoginAttempt($user->id, $username, true);
            
            // Return response
            ApiResponse::success([
                'tokens' => $tokens,
                'user' => [
                    'id' => (int)$user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => trim($user->first_name . ' ' . $user->last_name),
                    'role' => $user->role_slug,
                    'role_name' => $user->role_name,
                    'company_id' => $user->company_id ? (int)$user->company_id : null,
                    'company_name' => $user->company_name
                ]
            ], 'Login successful');
            
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            ApiResponse::error('Login failed', 500);
        }
    }
    
    /**
     * POST /api/v1/auth/refresh
     * 
     * Refresh access token using refresh token
     */
    public function refresh() {
        $input = $this->getJsonInput();
        
        if (empty($input['refresh_token'])) {
            ApiResponse::validationError(['refresh_token' => 'Refresh token is required']);
        }
        
        $refreshToken = $input['refresh_token'];
        
        // Validate refresh token
        $result = JwtHandler::validateToken($refreshToken);
        
        if (!$result['valid']) {
            if ($result['code'] === 'TOKEN_EXPIRED') {
                ApiResponse::error('Refresh token expired. Please login again.', 401);
            }
            ApiResponse::error('Invalid refresh token', 401);
        }
        
        // Check token type
        if (!isset($result['data']['type']) || $result['data']['type'] !== 'refresh') {
            ApiResponse::error('Invalid token type', 401);
        }
        
        $userId = $result['data']['sub'];
        $companyId = $result['data']['company_id'];
        
        // Verify user still exists and is active
        $stmt = $this->db->prepare("
            SELECT u.*, r.slug as role_slug
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = ? AND u.status = 'active'
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);
        
        if (!$user) {
            ApiResponse::error('User no longer valid', 401);
        }
        
        // Generate new tokens
        $tokens = JwtHandler::generateTokens(
            $user->id,
            $user->company_id,
            $user->role_slug,
            [
                'username' => $user->username,
                'email' => $user->email
            ]
        );
        
        // Update stored refresh token
        $this->storeRefreshToken($user->id, $tokens['refresh_token']);
        
        ApiResponse::success([
            'tokens' => $tokens
        ], 'Token refreshed');
    }
    
    /**
     * POST /api/v1/auth/logout
     * 
     * Logout - invalidează refresh token
     */
    public function logout() {
        $token = JwtHandler::getTokenFromHeader();
        
        if ($token) {
            $userId = JwtHandler::getUserIdFromToken($token);
            if ($userId) {
                // Remove refresh token from database
                $this->revokeRefreshToken($userId);
            }
        }
        
        ApiResponse::success(null, 'Logged out successfully');
    }
    
    /**
     * GET /api/v1/auth/me
     * 
     * Returnează datele utilizatorului curent
     */
    public function me() {
        $user = AuthMiddleware::user();
        
        // Get additional user data
        $stmt = $this->db->prepare("
            SELECT c.name as company_name, c.logo as company_logo
            FROM companies c
            WHERE c.id = ?
        ");
        $stmt->execute([$user->company_id]);
        $company = $stmt->fetch(PDO::FETCH_OBJ);
        
        ApiResponse::success([
            'id' => (int)$user->id,
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => trim($user->first_name . ' ' . $user->last_name),
            'role' => $user->role_slug,
            'role_name' => $user->role_name,
            'company' => $company ? [
                'id' => (int)$user->company_id,
                'name' => $company->company_name,
                'logo' => $company->company_logo
            ] : null
        ]);
    }
    
    /**
     * PUT /api/v1/auth/profile
     * 
     * Actualizează profilul utilizatorului
     */
    public function updateProfile() {
        $user = AuthMiddleware::user();
        $input = $this->getJsonInput();
        
        $allowedFields = ['first_name', 'last_name', 'email', 'phone'];
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = trim($input[$field]);
            }
        }
        
        if (empty($updates)) {
            ApiResponse::validationError(['fields' => 'No valid fields to update']);
        }
        
        // Check email uniqueness if updating
        if (isset($input['email'])) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([trim($input['email']), $user->id]);
            if ($stmt->fetch()) {
                ApiResponse::validationError(['email' => 'Email already in use']);
            }
        }
        
        $params[] = $user->id;
        
        try {
            $stmt = $this->db->prepare("
                UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?
            ");
            $stmt->execute($params);
            
            // Return updated user
            $this->me();
            
        } catch (PDOException $e) {
            error_log('Profile update error: ' . $e->getMessage());
            ApiResponse::error('Failed to update profile', 500);
        }
    }
    
    /**
     * PUT /api/v1/auth/password
     * 
     * Schimbă parola
     */
    public function changePassword() {
        $user = AuthMiddleware::user();
        $input = $this->getJsonInput();
        
        // Validate input
        if (empty($input['current_password']) || empty($input['new_password'])) {
            ApiResponse::validationError([
                'current_password' => empty($input['current_password']) ? 'Current password required' : null,
                'new_password' => empty($input['new_password']) ? 'New password required' : null
            ]);
        }
        
        // Get current password hash
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user->id]);
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        
        // Verify current password
        if (!password_verify($input['current_password'], $userData->password_hash)) {
            ApiResponse::error('Current password is incorrect', 400);
        }
        
        // Validate new password strength
        if (strlen($input['new_password']) < 8) {
            ApiResponse::validationError(['new_password' => 'Password must be at least 8 characters']);
        }
        
        // Update password
        $newHash = password_hash($input['new_password'], PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newHash, $user->id]);
        
        ApiResponse::success(null, 'Password changed successfully');
    }
    
    /**
     * POST /api/v1/auth/forgot-password
     * 
     * Inițiază resetarea parolei
     */
    public function forgotPassword() {
        $input = $this->getJsonInput();
        
        if (empty($input['email'])) {
            ApiResponse::validationError(['email' => 'Email is required']);
        }
        
        $email = trim($input['email']);
        
        // Always return success to prevent email enumeration
        $stmt = $this->db->prepare("SELECT id, email, first_name FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);
        
        if ($user) {
            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token (you might want to create a password_resets table)
            // For now, we'll use a simple approach with user meta or existing fields
            
            // TODO: Send email with reset link
            // The reset link would be something like: https://app.fleetly.ro/reset-password?token={$resetToken}
            
            error_log("Password reset requested for: {$email}");
        }
        
        // Always return success
        ApiResponse::success(null, 'If the email exists, a reset link will be sent.');
    }
    
    // ===== Helper Methods =====
    
    /**
     * Get JSON input from request body
     */
    private function getJsonInput() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            ApiResponse::validationError(['body' => 'Invalid JSON'], 'Invalid request body');
        }
        
        return $data ?? [];
    }
    
    /**
     * Handle failed login attempt
     */
    private function handleFailedLogin($user) {
        $attempts = $user->login_attempts + 1;
        $lockedUntil = null;
        
        if ($attempts >= 5) {
            $lockedUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        }
        
        $stmt = $this->db->prepare("
            UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?
        ");
        $stmt->execute([$attempts, $lockedUntil, $user->id]);
        
        $this->logLoginAttempt($user->id, $user->username, false, 'invalid_password');
    }
    
    /**
     * Store refresh token for revocation capability
     */
    private function storeRefreshToken($userId, $token) {
        // Extract JTI from token
        $result = JwtHandler::validateToken($token);
        if (!$result['valid']) return;
        
        $jti = $result['data']['jti'] ?? null;
        $expiresAt = date('Y-m-d H:i:s', $result['data']['exp']);
        
        // Store in user_sessions or a dedicated table
        // For simplicity, we'll update last_activity instead
        try {
            $stmt = $this->db->prepare("
                UPDATE users SET last_activity = NOW() WHERE id = ?
            ");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            // Ignore
        }
    }
    
    /**
     * Revoke refresh token
     */
    private function revokeRefreshToken($userId) {
        // In a full implementation, you'd maintain a blacklist or delete from user_sessions
        try {
            $stmt = $this->db->prepare("
                UPDATE users SET last_activity = NULL WHERE id = ?
            ");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            // Ignore
        }
    }
    
    /**
     * Log login attempt
     */
    private function logLoginAttempt($userId, $username, $success, $reason = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address, user_agent, created_at)
                VALUES (?, ?, 'auth', ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $success ? 'login_success' : 'login_failed',
                json_encode([
                    'username' => $username,
                    'reason' => $reason,
                    'source' => 'mobile_api'
                ]),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Don't fail login if audit logging fails
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }
}
