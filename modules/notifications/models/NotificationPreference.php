<?php
// modules/notifications/models/NotificationPreference.php

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Model.php';

class NotificationPreference extends Model {
    protected $table = 'notification_preferences';
    
    /**
     * Get preferences pentru un utilizator
     * @return array|null
     */
    public function getByUserId($userId) {
        $sql = "SELECT * FROM notification_preferences WHERE user_id = ?";
        $result = $this->db->fetchOn('notification_preferences', $sql, [$userId]);
        
        if ($result && !empty($result['enabled_types'])) {
            $result['enabled_types'] = json_decode($result['enabled_types'], true) ?: [];
        }
        if ($result && !empty($result['quiet_hours'])) {
            $result['quiet_hours'] = json_decode($result['quiet_hours'], true);
        }
        
        return $result ?: null;
    }
    
    /**
     * Get preferences SAU returnează valori default
     */
    public function getOrDefault($userId, $companyId) {
        $prefs = $this->getByUserId($userId);
        
        if (!$prefs) {
            return $this->getDefaultPreferences($userId, $companyId);
        }
        
        return $prefs;
    }
    
    /**
     * Create sau Update preferences pentru un user
     */
    public function createOrUpdate($userId, $companyId, $data) {
        // Validare enabled_types (trebuie JSON array)
        if (isset($data['enabled_types']) && is_array($data['enabled_types'])) {
            $data['enabled_types'] = json_encode($data['enabled_types']);
        }
        
        // Validare quiet_hours (JSON object: {"start":"22:00", "end":"08:00"})
        if (isset($data['quiet_hours']) && is_array($data['quiet_hours'])) {
            $data['quiet_hours'] = json_encode($data['quiet_hours']);
        }
        
        // Check dacă există
        $existing = $this->getByUserId($userId);
        
        if ($existing) {
            // UPDATE
            $updates = [];
            $params = [];
            
            $allowedFields = [
                'email_enabled', 'sms_enabled', 'push_enabled', 'in_app_enabled',
                'enabled_types', 'frequency', 'email', 'phone', 'push_token',
                'min_priority', 'broadcast_to_company', 'days_before_expiry',
                'quiet_hours', 'timezone'
            ];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                return ['success' => false, 'message' => 'No fields to update'];
            }
            
            $params[] = $userId;
            $sql = "UPDATE notification_preferences SET " . implode(', ', $updates) . " WHERE user_id = ?";
            
            try {
                $this->db->queryOn('notification_preferences', $sql, $params);
                return ['success' => true, 'action' => 'updated'];
            } catch (Throwable $e) {
                return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
            }
            
        } else {
            // INSERT
            $sql = "INSERT INTO notification_preferences 
                    (user_id, company_id, email_enabled, sms_enabled, push_enabled, in_app_enabled,
                     enabled_types, frequency, email, phone, push_token, min_priority, 
                     broadcast_to_company, days_before_expiry, quiet_hours, timezone)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $userId,
                $companyId,
                $data['email_enabled'] ?? 1,
                $data['sms_enabled'] ?? 0,
                $data['push_enabled'] ?? 0,
                $data['in_app_enabled'] ?? 1,
                $data['enabled_types'] ?? '["document_expiry","insurance_expiry","maintenance_due"]',
                $data['frequency'] ?? 'immediate',
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['push_token'] ?? null,
                $data['min_priority'] ?? 'low',
                $data['broadcast_to_company'] ?? 0,
                $data['days_before_expiry'] ?? 30,
                $data['quiet_hours'] ?? null,
                $data['timezone'] ?? 'Europe/Bucharest'
            ];
            
            try {
                // Debug: log query pentru debugging
                error_log("NotificationPreference INSERT SQL: $sql");
                error_log("NotificationPreference INSERT params: " . json_encode($params));
                
                $this->db->queryOn('notification_preferences', $sql, $params);
                $insertId = $this->db->lastInsertIdOn('notification_preferences');
                
                error_log("NotificationPreference INSERT success, ID: $insertId");
                return ['success' => true, 'action' => 'created', 'id' => $insertId];
            } catch (Throwable $e) {
                error_log("NotificationPreference INSERT failed: " . $e->getMessage());
                return ['success' => false, 'message' => 'Insert failed: ' . $e->getMessage()];
            }
        }
    }
    
    /**
     * Returnează valori default pentru un utilizator nou
     */
    public function getDefaultPreferences($userId, $companyId) {
        return [
            'user_id' => $userId,
            'company_id' => $companyId,
            'email_enabled' => 1,
            'sms_enabled' => 0,
            'push_enabled' => 0,
            'in_app_enabled' => 1,
            'enabled_types' => ['document_expiry', 'insurance_expiry', 'maintenance_due'],
            'frequency' => 'immediate',
            'email' => null,
            'phone' => null,
            'push_token' => null,
            'min_priority' => 'low',
            'broadcast_to_company' => 0,
            'days_before_expiry' => 30,
            'quiet_hours' => null,
            'timezone' => 'Europe/Bucharest'
        ];
    }
    
    /**
     * Check dacă utilizatorul are un canal activat
     */
    public function isChannelEnabled($userId, $channel) {
        $prefs = $this->getByUserId($userId);
        if (!$prefs) return false;
        
        $field = $channel . '_enabled';
        return !empty($prefs[$field]);
    }
    
    /**
     * Check dacă un tip de notificare este activat
     */
    public function isTypeEnabled($userId, $type) {
        $prefs = $this->getByUserId($userId);
        if (!$prefs) return true; // Default: toate activate
        
        $enabledTypes = !empty($prefs['enabled_types']) ? json_decode($prefs['enabled_types'], true) : [];
        return in_array($type, $enabledTypes);
    }
    
    /**
     * Check dacă acum este în quiet hours
     */
    public function isInQuietHours($userId) {
        $prefs = $this->getByUserId($userId);
        if (!$prefs || empty($prefs['quiet_hours'])) return false;
        
        $quietHours = json_decode($prefs['quiet_hours'], true);
        if (!isset($quietHours['start']) || !isset($quietHours['end'])) return false;
        
        $timezone = $prefs['timezone'] ?? 'Europe/Bucharest';
        $now = new DateTime('now', new DateTimeZone($timezone));
        $currentTime = $now->format('H:i');
        
        return ($currentTime >= $quietHours['start'] || $currentTime <= $quietHours['end']);
    }
    
    /**
     * Get toate preferences pentru o companie (pentru broadcast)
     */
    public function getAllByCompany($companyId) {
        $sql = "SELECT * FROM notification_preferences WHERE company_id = ?";
        $results = $this->db->fetchAllOn('notification_preferences', $sql, [$companyId]);
        
        foreach ($results as &$result) {
            if (!empty($result['enabled_types'])) {
                $result['enabled_types'] = json_decode($result['enabled_types'], true) ?: [];
            }
            if (!empty($result['quiet_hours'])) {
                $result['quiet_hours'] = json_decode($result['quiet_hours'], true);
            }
        }
        
        return $results;
    }
    
    /**
     * Migrator: Import preferences din system_settings (legacy)
     * Key format vechi: notifications_prefs_user_{id}
     */
    public static function migrateFromSystemSettings($userId, $companyId) {
        $db = Database::getInstance();
        $key = 'notifications_prefs_user_' . $userId;
        
        try {
            $row = $db->fetchOn('system_settings', "SELECT setting_value FROM system_settings WHERE setting_key = ?", [$key]);
            
            if (!$row || empty($row['setting_value'])) {
                return ['success' => false, 'message' => 'No legacy preferences found'];
            }
            
            $legacy = json_decode($row['setting_value'], true);
            if (!is_array($legacy)) {
                return ['success' => false, 'message' => 'Invalid legacy data'];
            }
            
            // Mapping din format vechi → nou
            $newPrefs = [
                'user_id' => $userId,
                'company_id' => $companyId,
                'email_enabled' => !empty($legacy['methods']['email']) ? 1 : 0,
                'sms_enabled' => !empty($legacy['methods']['sms']) ? 1 : 0,
                'in_app_enabled' => !empty($legacy['methods']['in_app']) ? 1 : 0,
                'enabled_types' => $legacy['enabledCategories'] ?? ['document_expiry', 'insurance_expiry', 'maintenance_due'],
                'frequency' => 'immediate', // Legacy nu avea, setăm default
                'min_priority' => $legacy['minPriority'] ?? 'low',
                'broadcast_to_company' => !empty($legacy['broadcastToCompany']) ? 1 : 0,
                'days_before_expiry' => $legacy['daysBefore'] ?? 30,
                'timezone' => 'Europe/Bucharest'
            ];
            
            $model = new self();
            return $model->createOrUpdate($userId, $companyId, $newPrefs);
            
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Migration error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Bulk migrate pentru toți userii activi
     */
    public static function migrateAllUsers() {
        $db = Database::getInstance();
        
        try {
            // Get all users cu company_id
            $users = $db->fetchAllOn('users', "SELECT id, company_id FROM users WHERE status = 'active' AND company_id IS NOT NULL");
            
            $migrated = 0;
            $skipped = 0;
            $errors = [];
            
            foreach ($users as $user) {
                $result = self::migrateFromSystemSettings($user['id'], $user['company_id']);
                
                if ($result['success']) {
                    $migrated++;
                } else {
                    if (strpos($result['message'], 'No legacy preferences') !== false) {
                        $skipped++;
                    } else {
                        $errors[] = "User {$user['id']}: {$result['message']}";
                    }
                }
            }
            
            return [
                'success' => true,
                'migrated' => $migrated,
                'skipped' => $skipped,
                'errors' => $errors
            ];
            
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Bulk migration error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete preferences pentru un user
     */
    public function delete($userId) {
        $sql = "DELETE FROM notification_preferences WHERE user_id = ?";
        try {
            $this->db->queryOn('notification_preferences', $sql, [$userId]);
            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>
