<?php
// modules/notifications/models/Notification.php

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Model.php';

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
        $sql = "INSERT INTO notifications (user_id, company_id, type, title, message, priority, related_id, related_type, action_url, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $data['user_id'] ?? null,
            $data['company_id'] ?? null,
            $data['type'],
            $data['title'],
            $data['message'],
            $data['priority'] ?? 'medium',
            $data['related_id'] ?? null,
            $data['related_type'] ?? null,
            $data['action_url'] ?? null
        ];
        
        $this->db->queryOn($this->table, $sql, $params);
        $id = $this->db->lastInsertIdOn($this->table);

        // Încercare trimitere imediată pe email/SMS conform preferințelor utilizatorului (non-blocking)
        try {
            require_once __DIR__ . '/../services/Notifier.php';
            $notifier = new Notifier();

            $userId = (int)($data['user_id'] ?? 0);
            if ($userId > 0) {
                // Preferințe
                $prefsKey = 'notifications_prefs_user_' . $userId;
                $prefsRow = $this->db->fetchOn($this->table, "SELECT setting_value FROM system_settings WHERE setting_key = ?", [$prefsKey]);
                $prefs = ['methods' => ['in_app'=>1,'email'=>0,'sms'=>0]];
                if ($prefsRow && !empty($prefsRow['setting_value'])) {
                    $dec = json_decode($prefsRow['setting_value'], true);
                    if (is_array($dec)) { $prefs = array_replace_recursive($prefs, $dec); }
                }

                $sentAny = false;
                $subject = ($data['title'] ?? 'Notificare') . ' - ' . (defined('APP_NAME') ? APP_NAME : 'Fleet Management');
                $body = ($data['message'] ?? '');
                if (!empty($data['action_url'])) { $body .= "\n\nVezi detalii: " . rtrim(BASE_URL, '/') . $data['action_url']; }

                if (!empty($prefs['methods']['email'])) {
                    $user = $this->db->fetchOn($this->table, "SELECT email FROM users WHERE id = ?", [$userId]);
                    $emailTo = $user['email'] ?? '';
                    if ($emailTo) {
                        [$ok, $err] = $notifier->sendEmail($emailTo, $subject, $body);
                        if ($ok) { $sentAny = true; }
                    }
                }

                if (!empty($prefs['methods']['sms'])) {
                    $userPhoneRow = $this->db->fetchOn($this->table, "SELECT setting_value FROM system_settings WHERE setting_key = ?", ['user_'.$userId.'_sms_to']);
                    $smsSettingsRow = $this->db->fetchOn($this->table, "SELECT setting_value FROM system_settings WHERE setting_key = 'sms_settings'");
                    $smsSettings = $smsSettingsRow && $smsSettingsRow['setting_value'] ? json_decode($smsSettingsRow['setting_value'], true) : [];
                    $toPhone = '';
                    if ($userPhoneRow && !empty($userPhoneRow['setting_value'])) { $toPhone = trim($userPhoneRow['setting_value']); }
                    elseif (!empty($smsSettings['sms_default_to'])) { $toPhone = trim($smsSettings['sms_default_to']); }
                    if ($toPhone) {
                        [$ok, $err] = $notifier->sendSms($toPhone, $data['message'] ?? 'Notificare');
                        if ($ok) { $sentAny = true; }
                    }
                }

                if ($sentAny) {
                    $this->db->queryOn($this->table, "UPDATE notifications SET status='sent', sent_at = NOW() WHERE id = ?", [$id]);
                }
            }
        } catch (Throwable $e) {
            // Ignorăm erorile pentru a nu bloca fluxul aplicației
        }

        return $id;
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
        // Verificăm dacă există deja o notificare similară în ultimele 24 ore
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
        
        // Verificăm preferințele admin pentru broadcast
        $adminPrefs = $notification->getAdminBroadcastPreference($companyId);
        
        $data = [
            'type' => 'insurance_expiry',
            'title' => 'Asigurare în expirare',
            'message' => "Asigurarea $insuranceType pentru vehiculul $vehicleLicensePlate expiră în $daysUntilExpiry zile.",
            'priority' => $priority,
            'related_id' => $insuranceId,
            'related_type' => 'insurance',
            'action_url' => "/modules/insurance/views/view.php?id=$insuranceId"
        ];
        
        // Dacă broadcast e activ, setăm company_id; altfel, user_id = 1 (admin)
        if ($adminPrefs['broadcastToCompany'] && $companyId) {
            $data['company_id'] = $companyId;
        } else {
            $data['user_id'] = 1; // Admin user implicit
        }
        
        if (!$notification->exists($data)) {
            return $notification->create($data);
        }
        
        return false;
    }
    
    public static function createMaintenanceNotification($vehicleId, $vehicleLicensePlate, $maintenanceType, $companyId = null) {
        $notification = new self();
        
        // Verificăm dacă admin-ul companiei a activat broadcast
        $broadcastPrefs = $companyId ? $notification->getAdminBroadcastPreference($companyId) : ['broadcastToCompany' => false];
        
        $data = [
            'user_id' => $broadcastPrefs['broadcastToCompany'] ? null : 1,
            'company_id' => $broadcastPrefs['broadcastToCompany'] ? $companyId : null,
            'type' => 'maintenance_due',
            'title' => 'Mentenanță scadentă',
            'message' => "Vehiculul $vehicleLicensePlate necesită mentenanță: $maintenanceType",
            'priority' => 'medium',
            'related_id' => $vehicleId,
            'related_type' => 'vehicle',
            'action_url' => "/modules/maintenance/views/add.php?vehicle_id=$vehicleId"
        ];
        
        if (!$notification->exists($data)) {
            return $notification->create($data);
        }
        
        return false;
    }
    
    public static function createDocumentExpiryNotification($documentId, $vehicleLicensePlate, $documentType, $daysUntilExpiry, $companyId = null) {
        $notification = new self();
        
        $priority = 'medium';
        if ($daysUntilExpiry <= 7) $priority = 'high';
        elseif ($daysUntilExpiry <= 14) $priority = 'medium';
        else $priority = 'low';
        
        // Verificăm dacă admin-ul companiei a activat broadcast
        $broadcastPrefs = $companyId ? $notification->getAdminBroadcastPreference($companyId) : ['broadcastToCompany' => false];
        
        $data = [
            'user_id' => $broadcastPrefs['broadcastToCompany'] ? null : 1,
            'company_id' => $broadcastPrefs['broadcastToCompany'] ? $companyId : null,
            'type' => 'document_expiry',
            'title' => 'Document în expirare',
            'message' => "Documentul $documentType pentru vehiculul $vehicleLicensePlate expiră în $daysUntilExpiry zile.",
            'priority' => $priority,
            'related_id' => $documentId,
            'related_type' => 'document',
            'action_url' => "/modules/documents/views/view.php?id=$documentId"
        ];
        
        if (!$notification->exists($data)) {
            return $notification->create($data);
        }
        
        return false;
    }
}
?>
