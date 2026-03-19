<?php
/**
 * JWT Handler
 * 
 * Gestionează crearea și validarea token-urilor JWT.
 * Folosește biblioteca firebase/php-jwt.
 */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JwtHandler {
    
    private static $secretKey;
    private static $algorithm = 'HS256';
    private static $accessTokenExpiry = 3600;      // 1 hour
    private static $refreshTokenExpiry = 604800;   // 7 days
    
    /**
     * Initialize JWT settings
     */
    public static function init() {
        // Get secret key from config or generate a default
        self::$secretKey = defined('JWT_SECRET_KEY') 
            ? JWT_SECRET_KEY 
            : 'fleetly_jwt_secret_key_change_in_production_' . md5(__DIR__);
    }
    
    /**
     * Generate access token
     */
    public static function generateAccessToken($userId, $companyId, $roleSlug, $extra = []) {
        self::init();
        
        $issuedAt = time();
        $expiresAt = $issuedAt + self::$accessTokenExpiry;
        
        $payload = [
            'iss' => 'fleetly-api',
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'type' => 'access',
            'sub' => $userId,
            'company_id' => $companyId,
            'role' => $roleSlug
        ];
        
        // Add extra claims
        foreach ($extra as $key => $value) {
            $payload[$key] = $value;
        }
        
        return JWT::encode($payload, self::$secretKey, self::$algorithm);
    }
    
    /**
     * Generate refresh token
     */
    public static function generateRefreshToken($userId, $companyId) {
        self::init();
        
        $issuedAt = time();
        $expiresAt = $issuedAt + self::$refreshTokenExpiry;
        
        $payload = [
            'iss' => 'fleetly-api',
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'type' => 'refresh',
            'sub' => $userId,
            'company_id' => $companyId,
            'jti' => bin2hex(random_bytes(16)) // Unique token ID
        ];
        
        return JWT::encode($payload, self::$secretKey, self::$algorithm);
    }
    
    /**
     * Generate both tokens
     */
    public static function generateTokens($userId, $companyId, $roleSlug, $extra = []) {
        return [
            'access_token' => self::generateAccessToken($userId, $companyId, $roleSlug, $extra),
            'refresh_token' => self::generateRefreshToken($userId, $companyId),
            'token_type' => 'Bearer',
            'expires_in' => self::$accessTokenExpiry
        ];
    }
    
    /**
     * Validate and decode token
     */
    public static function validateToken($token) {
        self::init();
        
        try {
            $decoded = JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
            return [
                'valid' => true,
                'data' => (array) $decoded
            ];
        } catch (ExpiredException $e) {
            return [
                'valid' => false,
                'error' => 'Token expired',
                'code' => 'TOKEN_EXPIRED'
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => 'Invalid token',
                'code' => 'INVALID_TOKEN'
            ];
        }
    }
    
    /**
     * Get token from Authorization header
     */
    public static function getTokenFromHeader() {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Check if token is refresh token
     */
    public static function isRefreshToken($token) {
        $result = self::validateToken($token);
        if ($result['valid'] && isset($result['data']['type'])) {
            return $result['data']['type'] === 'refresh';
        }
        return false;
    }
    
    /**
     * Get user ID from token
     */
    public static function getUserIdFromToken($token) {
        $result = self::validateToken($token);
        if ($result['valid'] && isset($result['data']['sub'])) {
            return $result['data']['sub'];
        }
        return null;
    }
    
    /**
     * Get company ID from token
     */
    public static function getCompanyIdFromToken($token) {
        $result = self::validateToken($token);
        if ($result['valid'] && isset($result['data']['company_id'])) {
            return $result['data']['company_id'];
        }
        return null;
    }
}
