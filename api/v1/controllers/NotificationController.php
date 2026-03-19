<?php
/**
 * Notification API Controller
 * 
 * Gestionează notificările utilizatorului via API mobile.
 * Include suport pentru push notifications cu Firebase.
 */
class NotificationController {
    
    private $db;
    private $tenantDb;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    private function initTenantDb() {
        // In single-tenant mode, use core DB directly
        if (method_exists('DatabaseConfig', 'getTenancyMode') && DatabaseConfig::getTenancyMode() === 'single') {
            $this->tenantDb = $this->db;
            return;
        }
        
        $companyId = AuthMiddleware::companyId();
        if ($companyId) {
            Database::getInstance()->setTenantDatabaseByCompanyId($companyId);
            $this->tenantDb = Database::getInstance()->getTenantPdo();
        }
        if (!$this->tenantDb) {
            $this->tenantDb = $this->db;
        }
    }
    
    /**
     * GET /api/v1/notifications
     * 
     * List notifications for current user
     */
    public function index() {
        $this->initTenantDb();
        
        $userId = AuthMiddleware::userId();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === 'true';
        
        $offset = ($page - 1) * $perPage;
        
        try {
            $where = ['user_id = ?'];
            $params = [$userId];
            
            if ($unreadOnly) {
                $where[] = "read_at IS NULL";
            }
            
            $whereClause = implode(' AND ', $where);
            
            // Count
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM notifications WHERE {$whereClause}");
            $stmt->execute($params);
            $total = (int)$stmt->fetch(PDO::FETCH_OBJ)->total;
            
            // Get notifications
            $sql = "
                SELECT id, title, message, type, priority, data, 
                       read_at, created_at
                FROM notifications
                WHERE {$whereClause}
                ORDER BY created_at DESC
                LIMIT {$perPage} OFFSET {$offset}
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $notifications = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $notifications[] = [
                    'id' => (int)$row->id,
                    'title' => $row->title,
                    'message' => $row->message,
                    'type' => $row->type,
                    'priority' => $row->priority ?? 'normal',
                    'data' => $row->data ? json_decode($row->data, true) : null,
                    'is_read' => $row->read_at !== null,
                    'read_at' => $row->read_at,
                    'created_at' => $row->created_at
                ];
            }
            
            ApiResponse::paginated($notifications, $page, $perPage, $total);
            
        } catch (PDOException $e) {
            error_log('Notifications list error: ' . $e->getMessage());
            ApiResponse::error('Failed to load notifications', 500);
        }
    }
    
    /**
     * GET /api/v1/notifications/unread-count
     * 
     * Get count of unread notifications
     */
    public function unreadCount() {
        $userId = AuthMiddleware::userId();
        
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = ? AND read_at IS NULL
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            
            ApiResponse::success([
                'unread_count' => (int)$result->count
            ]);
            
        } catch (PDOException $e) {
            error_log('Unread count error: ' . $e->getMessage());
            ApiResponse::error('Failed to get unread count', 500);
        }
    }
    
    /**
     * POST /api/v1/notifications/{id}/read
     * 
     * Mark notification as read
     */
    public function markRead($id) {
        $userId = AuthMiddleware::userId();
        
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET read_at = NOW() 
                WHERE id = ? AND user_id = ? AND read_at IS NULL
            ");
            $stmt->execute([$id, $userId]);
            
            if ($stmt->rowCount() === 0) {
                // Either not found or already read
                $stmt = $this->db->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $userId]);
                if (!$stmt->fetch()) {
                    ApiResponse::notFound('Notification not found');
                }
            }
            
            ApiResponse::success(null, 'Notification marked as read');
            
        } catch (PDOException $e) {
            error_log('Mark read error: ' . $e->getMessage());
            ApiResponse::error('Failed to mark notification as read', 500);
        }
    }
    
    /**
     * POST /api/v1/notifications/read-all
     * 
     * Mark all notifications as read
     */
    public function markAllRead() {
        $userId = AuthMiddleware::userId();
        
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET read_at = NOW() 
                WHERE user_id = ? AND read_at IS NULL
            ");
            $stmt->execute([$userId]);
            
            ApiResponse::success([
                'marked_count' => $stmt->rowCount()
            ], 'All notifications marked as read');
            
        } catch (PDOException $e) {
            error_log('Mark all read error: ' . $e->getMessage());
            ApiResponse::error('Failed to mark notifications as read', 500);
        }
    }
    
    /**
     * DELETE /api/v1/notifications/{id}
     * 
     * Delete notification
     */
    public function destroy($id) {
        $userId = AuthMiddleware::userId();
        
        try {
            $stmt = $this->db->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$id, $userId]);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Notification not found');
            }
            
            ApiResponse::success(null, 'Notification deleted');
            
        } catch (PDOException $e) {
            error_log('Delete notification error: ' . $e->getMessage());
            ApiResponse::error('Failed to delete notification', 500);
        }
    }
    
    /**
     * POST /api/v1/notifications/register-device
     * 
     * Register device for push notifications
     */
    public function registerDevice() {
        $userId = AuthMiddleware::userId();
        $input = $this->getJsonInput();
        
        if (empty($input['device_token'])) {
            ApiResponse::validationError(['device_token' => 'Device token is required']);
        }
        
        $deviceToken = trim($input['device_token']);
        $deviceType = $input['device_type'] ?? 'unknown'; // android, ios
        $deviceName = $input['device_name'] ?? null;
        
        try {
            // Check if token already exists for this user
            $stmt = $this->db->prepare("
                SELECT id FROM push_device_tokens 
                WHERE user_id = ? AND device_token = ?
            ");
            $stmt->execute([$userId, $deviceToken]);
            
            if ($stmt->fetch()) {
                // Update existing
                $stmt = $this->db->prepare("
                    UPDATE push_device_tokens 
                    SET device_type = ?, device_name = ?, updated_at = NOW()
                    WHERE user_id = ? AND device_token = ?
                ");
                $stmt->execute([$deviceType, $deviceName, $userId, $deviceToken]);
            } else {
                // Insert new - but first check if table exists
                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO push_device_tokens (user_id, device_token, device_type, device_name, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$userId, $deviceToken, $deviceType, $deviceName]);
                } catch (PDOException $e) {
                    // Table might not exist - create it
                    if (strpos($e->getMessage(), "doesn't exist") !== false) {
                        $this->createDeviceTokensTable();
                        $stmt = $this->db->prepare("
                            INSERT INTO push_device_tokens (user_id, device_token, device_type, device_name, created_at)
                            VALUES (?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$userId, $deviceToken, $deviceType, $deviceName]);
                    } else {
                        throw $e;
                    }
                }
            }
            
            ApiResponse::success(null, 'Device registered for push notifications');
            
        } catch (PDOException $e) {
            error_log('Register device error: ' . $e->getMessage());
            ApiResponse::error('Failed to register device', 500);
        }
    }
    
    /**
     * DELETE /api/v1/notifications/unregister-device
     * 
     * Unregister device from push notifications
     */
    public function unregisterDevice() {
        $userId = AuthMiddleware::userId();
        $input = $this->getJsonInput();
        
        if (empty($input['device_token'])) {
            ApiResponse::validationError(['device_token' => 'Device token is required']);
        }
        
        try {
            $stmt = $this->db->prepare("
                DELETE FROM push_device_tokens 
                WHERE user_id = ? AND device_token = ?
            ");
            $stmt->execute([$userId, trim($input['device_token'])]);
            
            ApiResponse::success(null, 'Device unregistered');
            
        } catch (PDOException $e) {
            // If table doesn't exist, that's fine
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                ApiResponse::success(null, 'Device unregistered');
                return;
            }
            error_log('Unregister device error: ' . $e->getMessage());
            ApiResponse::error('Failed to unregister device', 500);
        }
    }
    
    // ===== Helper Methods =====
    
    private function getJsonInput() {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
    
    /**
     * Create push_device_tokens table if it doesn't exist
     */
    private function createDeviceTokensTable() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS push_device_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                device_token VARCHAR(500) NOT NULL,
                device_type ENUM('android', 'ios', 'unknown') DEFAULT 'unknown',
                device_name VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                UNIQUE KEY unique_user_token (user_id, device_token(255))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}
