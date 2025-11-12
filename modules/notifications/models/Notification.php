<?php
// modules/notifications/models/Notification.php

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Model.php';
require_once __DIR__ . '/NotificationLog.php';

class Notification extends Model {
    protected $table = 'notifications';
    
    public function create($data) {
        // Suport pentru notificări broadcast la nivel de companie
        $companyId = $data['company_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        
        // Dacă e notificare broadcast (company_id set, user_id null), creăm câte o notificare pentru fiecare user activ
        if ($companyId && !$userId) {
            $users = $this->db->fetchAllOn('users', "SELECT id FROM users WHERE company_id = ? AND status = 'active'", [$companyId]);
            $createdIds = [];
            foreach ($users as $user) {
                $userData = array_merge($data, ['user_id' => $user['id']]);
                $createdIds[] = $this->createSingle($userData);
            }
            return $createdIds; // Returnăm array de ID-uri create
        }
        
        // Notificare individuală standard
        return $this->createSingle($data);
    }
    
    private function createSingle($data) {
        // V2: Integrare cu NotificationTemplate + NotificationQueue
        require_once __DIR__ . '/NotificationTemplate.php';
        require_once __DIR__ . '/NotificationPreference.php';
        require_once __DIR__ . '/NotificationQueue.php';
        
        $userId = (int)($data['user_id'] ?? 0);
        $companyId = (int)($data['company_id'] ?? 0);
        $type = $data['type'] ?? 'system_alert';
        
        // Step 1: Render template (dacă există)
        $templateModel = new NotificationTemplate();
        $templateVars = $data['template_vars'] ?? [];
        
        // Fallback: dacă nu sunt template_vars, folosim titlu/mesaj direct din $data
        if (empty($templateVars) && isset($data['title']) && isset($data['message'])) {
            $rendered = [
                'subject' => $data['title'],
                'body' => $data['message'],
                'priority' => $data['priority'] ?? 'medium',
                'template_id' => null
            ];
        } else {
            // Render din template
            $rendered = $templateModel->render($type, $templateVars, 'in_app', $companyId);
            
            // Dacă template nu există, fallback la date din $data
            if (!$rendered) {
                $rendered = [
                    'subject' => $data['title'] ?? 'Notificare',
                    'body' => $data['message'] ?? '',
                    'priority' => $data['priority'] ?? 'medium',
                    'template_id' => null
                ];
            }
        }
        
        // Step 2: INSERT notification în DB
        $sql = "INSERT INTO notifications (user_id, company_id, type, title, message, priority, related_id, related_type, action_url, template_id, rendered_at, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $params = [
            $userId,
            $companyId,
            $type,
            $rendered['subject'],
            $rendered['body'],
            $rendered['priority'],
            $data['related_id'] ?? null,
            $data['related_type'] ?? null,
            $data['action_url'] ?? null,
            $rendered['template_id']
        ];
        
        $this->db->queryOn($this->table, $sql, $params);
        $notificationId = $this->db->lastInsertIdOn($this->table);
        
        // Log creare
        NotificationLog::log($type, 'created', [
            'user_id' => $userId,
            'company_id' => $companyId,
            'notification_id' => $notificationId,
            'template_id' => $rendered['template_id'],
            'priority' => $rendered['priority']
        ], $notificationId);
        
        // Step 3: Get user preferences (pentru Queue)
        if ($userId > 0) {
            try {
                $prefsModel = new NotificationPreference();
                $userPrefs = $prefsModel->getOrDefault($userId, $companyId);
                
                // Get user data pentru contact info
                $user = $this->db->fetchOn($this->table, "SELECT email, phone FROM users WHERE id = ?", [$userId]);
                if (!$user) {
                    $user = ['email' => null, 'phone' => null];
                }
                
                // Determine scheduled_at pe bază de frequency
                $scheduledAt = null; // immediate
                if ($userPrefs['frequency'] === 'daily') {
                    $scheduledAt = date('Y-m-d 06:00:00', strtotime('tomorrow'));
                } elseif ($userPrefs['frequency'] === 'weekly') {
                    $scheduledAt = date('Y-m-d 09:00:00', strtotime('next monday'));
                }
                
                // Step 4: Enqueue pentru fiecare canal activ
                $queueModel = new NotificationQueue();
                $channels = [];
                
                // Email
                if ($userPrefs['email_enabled']) {
                    $emailRendered = $templateModel->render($type, $templateVars, 'email', $companyId);
                    if (!$emailRendered && isset($rendered['subject'])) {
                        $emailRendered = ['subject' => $rendered['subject'], 'body' => $rendered['body']];
                    }
                    
                    $queueModel->enqueue($notificationId, $userId, $companyId, 'email', [
                        'recipient_email' => $userPrefs['email'] ?? $user['email'],
                        'subject' => $emailRendered['subject'] ?? $rendered['subject'],
                        'message' => $emailRendered['body'] ?? $rendered['body'],
                        'scheduled_at' => $scheduledAt
                    ]);
                    $channels[] = 'email';
                }
                
                // SMS
                if ($userPrefs['sms_enabled']) {
                    $smsRendered = $templateModel->render($type, $templateVars, 'sms', $companyId);
                    $smsMessage = $smsRendered['body'] ?? mb_substr($rendered['body'], 0, 160);
                    
                    $queueModel->enqueue($notificationId, $userId, $companyId, 'sms', [
                        'recipient_phone' => $userPrefs['phone'] ?? $user['phone'],
                        'message' => $smsMessage,
                        'scheduled_at' => $scheduledAt
                    ]);
                    $channels[] = 'sms';
                }
                
                // Push (dacă activat)
                if ($userPrefs['push_enabled'] && !empty($userPrefs['push_token'])) {
                    $pushRendered = $templateModel->render($type, $templateVars, 'push', $companyId);
                    
                    $queueModel->enqueue($notificationId, $userId, $companyId, 'push', [
                        'recipient_push_token' => $userPrefs['push_token'],
                        'subject' => $pushRendered['subject'] ?? $rendered['subject'],
                        'message' => $pushRendered['body'] ?? $rendered['body'],
                        'scheduled_at' => $scheduledAt
                    ]);
                    $channels[] = 'push';
                }
                
                // In-app (default, deja în tabela notifications)
                if ($userPrefs['in_app_enabled']) {
                    $channels[] = 'in_app';
                }
                
                // Log canale enqueued
                if (!empty($channels)) {
                    NotificationLog::log($type, 'enqueued', [
                        'notification_id' => $notificationId,
                        'channels' => $channels,
                        'frequency' => $userPrefs['frequency'],
                        'scheduled_at' => $scheduledAt
                    ], $notificationId);
                }
                
            } catch (Throwable $e) {
                // Non-blocking: dacă queue eșuează, notificarea in-app rămâne în DB
                NotificationLog::log($type, 'queue_error', [
                    'notification_id' => $notificationId,
                    'error' => $e->getMessage()
                ], $notificationId, $e->getMessage());
            }
        }
        
        return $notificationId;
    }
    
    public function getAllWithDetails($conditions = [], $offset = 0, $limit = 25) {
        // Notă: $conditions ar trebui să conțină deja user_id setat de controller
        $whereClause = $this->buildWhereClause($conditions);
        
        $sql = "SELECT n.*, 
                       CASE 
                           WHEN n.related_type = 'vehicle' AND n.related_id IS NOT NULL 
                           THEN (SELECT CONCAT(registration_number, ' - ', brand, ' ', model) FROM vehicles WHERE id = n.related_id)
                           ELSE NULL
                       END as related_vehicle
                FROM notifications n 
                {$whereClause}
                ORDER BY n.is_read ASC, n.priority = 'high' DESC, n.priority = 'medium' DESC, n.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params = array_values($conditions);
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAllOn($this->table, $sql, $params);
    }
    
    public function getTotalCount($conditions = []) {
        $whereClause = $this->buildWhereClause($conditions);
        
        $sql = "SELECT COUNT(*) as count FROM notifications n {$whereClause}";
        $params = array_values($conditions);
        
        $result = $this->db->fetchOn($this->table, $sql, $params);
        return $result['count'] ?? 0;
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM notifications WHERE id = ?";
        return $this->db->fetchOn($this->table, $sql, [$id]);
    }
    
    public function markAsRead($id) {
        $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?";
        return $this->db->queryOn($this->table, $sql, [$id]);
    }
    
    public function markAllAsRead($userId) {
        $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0";
        return $this->db->queryOn($this->table, $sql, [$userId]);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM notifications WHERE id = ?";
        return $this->db->queryOn($this->table, $sql, [$id]);
    }
    
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $result = $this->db->fetchOn($this->table, $sql, [$userId]);
        return $result['count'] ?? 0;
    }
    
    public function getStatistics($userId) {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN `is_read` = 0 THEN 1 ELSE 0 END) as total_unread,
                    SUM(CASE WHEN `priority` = 'high' AND `is_read` = 0 THEN 1 ELSE 0 END) as high_priority_count,
                    SUM(CASE WHEN `type` = 'insurance_expiry' AND `is_read` = 0 THEN 1 ELSE 0 END) as insurance_expiring,
                    SUM(CASE WHEN `type` = 'maintenance_due' AND `is_read` = 0 THEN 1 ELSE 0 END) as maintenance_due,
                    SUM(CASE WHEN `type` = 'document_expiry' AND `is_read` = 0 THEN 1 ELSE 0 END) as documents_expiring
                FROM notifications 
                WHERE `user_id` = ?";
        
        return $this->db->fetchOn($this->table, $sql, [$userId]);
    }
    
    public function getRecentNotifications($userId, $limit = 10) {
        $sql = "SELECT n.*, 
                       CASE 
                           WHEN n.related_type = 'vehicle' AND n.related_id IS NOT NULL 
                           THEN (SELECT CONCAT(registration_number, ' - ', brand, ' ', model) FROM vehicles WHERE id = n.related_id)
                           ELSE NULL
                       END as related_vehicle
                FROM notifications n 
                WHERE n.user_id = ?
                ORDER BY n.is_read ASC, n.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAllOn($this->table, $sql, [$userId, $limit]);
    }
    
    public function exists($data) {
        // Verificăm duplicate în ultimele 24h fie la nivel de user, fie la nivel de companie (broadcast)
        if (!empty($data['user_id'])) {
            $sql = "SELECT COUNT(*) as count 
                    FROM notifications 
                    WHERE user_id = ? 
                      AND type = ? 
                      AND related_id = ? 
                      AND related_type = ? 
                      AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $params = [
                $data['user_id'],
                $data['type'],
                $data['related_id'] ?? null,
                $data['related_type'] ?? null
            ];
        } elseif (!empty($data['company_id'])) {
            $sql = "SELECT COUNT(*) as count 
                    FROM notifications 
                    WHERE company_id = ? 
                      AND type = ? 
                      AND related_id = ? 
                      AND related_type = ? 
                      AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $params = [
                $data['company_id'],
                $data['type'],
                $data['related_id'] ?? null,
                $data['related_type'] ?? null
            ];
        } else {
            // Dacă nu avem nici user, nici company (nu ar trebui), nu blocăm crearea
            return false;
        }

        $result = $this->db->fetchOn($this->table, $sql, $params);
        return ($result['count'] ?? 0) > 0;
    }
    
    public function getByType($userId, $type, $limit = 50) {
        $sql = "SELECT n.*, 
                       CASE 
                           WHEN n.related_type = 'vehicle' AND n.related_id IS NOT NULL 
                           THEN (SELECT CONCAT(registration_number, ' - ', brand, ' ', model) FROM vehicles WHERE id = n.related_id)
                           ELSE NULL
                       END as related_vehicle
                FROM notifications n 
                WHERE n.user_id = ? AND n.type = ?
                ORDER BY n.is_read ASC, n.created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAllOn($this->table, $sql, [$userId, $type, $limit]);
    }
    
    public function cleanup($days = 30) {
        // Șterge notificările citite mai vechi de X zile
        $sql = "DELETE FROM notifications 
                WHERE is_read = 1 
                  AND read_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        return $this->db->queryOn($this->table, $sql, [$days]);
    }
    
    public function bulkMarkAsRead($ids, $userId) {
        if (empty($ids)) return false;
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id IN ($placeholders) AND user_id = ?";
        
        $params = array_merge($ids, [$userId]);
        return $this->db->queryOn($this->table, $sql, $params);
    }
    
    public function bulkDelete($ids, $userId) {
        if (empty($ids)) return false;
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "DELETE FROM notifications 
                WHERE id IN ($placeholders) AND user_id = ?";
        
        $params = array_merge($ids, [$userId]);
        return $this->db->queryOn($this->table, $sql, $params);
    }
    
    private function buildWhereClause($conditions) {
        if (empty($conditions)) {
            return '';
        }
        
        $whereConditions = [];
        foreach ($conditions as $field => $value) {
            if ($value !== null && $value !== '') {
                $whereConditions[] = "n.$field = ?";
            }
        }
        
        return empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    private function getAdminBroadcastPreference($companyId) {
        // Căutăm un admin/manager al companiei și citim preferințele sale
        try {
            // Compatibilitate între două scheme: una simplă cu coloana `role` în users și schema RBAC cu `role_id` + tabela roles.
            // Încercăm mai întâi join pe roles (schema nouă). Dacă eșuează, revenim la varianta simplă.
            try {
                $admin = $this->db->fetchOn('users',
                    "SELECT u.id FROM users u INNER JOIN roles r ON u.role_id = r.id 
                     WHERE u.company_id = ? AND r.slug IN ('admin','manager','fleet_manager','superadmin') AND u.status = 'active' 
                     ORDER BY r.level ASC LIMIT 1",
                    [$companyId]
                );
            } catch (Throwable $e) {
                // Fallback pe schema veche (coloană role direct în users)
                $admin = $this->db->fetchOn('users',
                    "SELECT id FROM users WHERE company_id = ? AND role IN ('admin','manager') AND status = 'active' LIMIT 1",
                    [$companyId]
                );
            }
            if ($admin) {
                $key = 'notifications_prefs_user_' . $admin['id'];
                $row = $this->db->fetchOn($this->table, "SELECT setting_value FROM system_settings WHERE setting_key = ?", [$key]);
                if ($row && !empty($row['setting_value'])) {
                    $prefs = json_decode($row['setting_value'], true);
                    if (is_array($prefs) && isset($prefs['broadcastToCompany'])) {
                        return ['broadcastToCompany' => (bool)$prefs['broadcastToCompany']];
                    }
                }
            }
        } catch (Throwable $e) {
            // Ignorăm erorile și returnăm valori implicite
        }
        return ['broadcastToCompany' => false];
    }
    
    // Metode pentru crearea notificărilor automate
    public static function createInsuranceExpiryNotification($insuranceId, $vehicleLicensePlate, $insuranceType, $daysUntilExpiry, $companyId = null) {
        $notification = new self();
        
        $priority = 'medium';
        if ($daysUntilExpiry <= 7) $priority = 'high';
        elseif ($daysUntilExpiry <= 14) $priority = 'medium';
        else $priority = 'low';
        
    // Verificăm preferințele admin pentru broadcast și identificăm utilizatorul curent
    $adminPrefs = $notification->getAdminBroadcastPreference($companyId);
    $actorId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
        
        $data = [
            'type' => 'insurance_expiry',
            'title' => 'Asigurare în expirare',
            'message' => "Asigurarea $insuranceType pentru vehiculul $vehicleLicensePlate expiră în $daysUntilExpiry zile.",
            'priority' => $priority,
            'related_id' => $insuranceId,
            'related_type' => 'insurance',
            'action_url' => "/modules/insurance/views/view.php?id=$insuranceId"
        ];
        
        // Dacă broadcast e activ, setăm company_id; altfel, user_id = utilizatorul curent
        if ($adminPrefs['broadcastToCompany'] && $companyId) {
            $data['company_id'] = $companyId;
        } else {
            $data['user_id'] = $actorId;
        }
        
        if ($notification->exists($data)) {
            NotificationLog::log('insurance_expiry', 'skipped', [
                'reason' => 'duplicate_within_24h',
                'related_id' => $insuranceId,
                'company_id' => $companyId
            ], null);
            return false;
        }
        return $notification->create($data);
    }
    
    public static function createMaintenanceNotification($vehicleId, $vehicleLicensePlate, $maintenanceType, $companyId = null) {
        $notification = new self();
        
    // Verificăm dacă admin-ul companiei a activat broadcast și utilizatorul curent
    $broadcastPrefs = $companyId ? $notification->getAdminBroadcastPreference($companyId) : ['broadcastToCompany' => false];
    $actorId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
        
        $data = [
            'user_id' => $broadcastPrefs['broadcastToCompany'] ? null : $actorId,
            'company_id' => $broadcastPrefs['broadcastToCompany'] ? $companyId : null,
            'type' => 'maintenance_due',
            'title' => 'Mentenanță scadentă',
            'message' => "Vehiculul $vehicleLicensePlate necesită mentenanță: $maintenanceType",
            'priority' => 'medium',
            'related_id' => $vehicleId,
            'related_type' => 'vehicle',
            'action_url' => "/modules/maintenance/views/add.php?vehicle_id=$vehicleId"
        ];
        
        if ($notification->exists($data)) {
            NotificationLog::log('maintenance_due', 'skipped', [
                'reason' => 'duplicate_within_24h',
                'vehicle_id' => $vehicleId,
                'company_id' => $companyId
            ], null);
            return false;
        }
        return $notification->create($data);
    }
    
    public static function createDocumentExpiryNotification($documentId, $vehicleLicensePlate, $documentType, $daysUntilExpiry, $companyId = null) {
        $notification = new self();
        
        $priority = 'medium';
        if ($daysUntilExpiry <= 7) $priority = 'high';
        elseif ($daysUntilExpiry <= 14) $priority = 'medium';
        else $priority = 'low';
        
    // Verificăm dacă admin-ul companiei a activat broadcast și utilizatorul curent
    $broadcastPrefs = $companyId ? $notification->getAdminBroadcastPreference($companyId) : ['broadcastToCompany' => false];
    $actorId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
        
        $data = [
            'user_id' => $broadcastPrefs['broadcastToCompany'] ? null : $actorId,
            'company_id' => $broadcastPrefs['broadcastToCompany'] ? $companyId : null,
            'type' => 'document_expiry',
            'title' => 'Document în expirare',
            'message' => "Documentul $documentType pentru vehiculul $vehicleLicensePlate expiră în $daysUntilExpiry zile.",
            'priority' => $priority,
            'related_id' => $documentId,
            'related_type' => 'document',
            'action_url' => "/modules/documents/views/view.php?id=$documentId"
        ];
        
        if ($notification->exists($data)) {
            NotificationLog::log('document_expiry', 'skipped', [
                'reason' => 'duplicate_within_24h',
                'document_id' => $documentId,
                'company_id' => $companyId
            ], null);
            return false;
        }
        return $notification->create($data);
    }
}
?>
