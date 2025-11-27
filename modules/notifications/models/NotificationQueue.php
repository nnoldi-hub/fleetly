<?php
// modules/notifications/models/NotificationQueue.php

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Model.php';

class NotificationQueue extends Model {
    protected $table = 'notification_queue';
    
    /**
     * Adaugă notificare în queue pentru procesare asincronă
     */
    public function enqueue($notificationId, $userId, $companyId, $channel, $data) {
        // Validare canal
        $validChannels = ['email', 'sms', 'push', 'in_app'];
        if (!in_array($channel, $validChannels)) {
            return ['success' => false, 'message' => 'Invalid channel: ' . $channel];
        }
        
        // Validare date obligatorii per canal
        if ($channel === 'email' && empty($data['recipient_email'])) {
            return ['success' => false, 'message' => 'Email channel requires recipient_email'];
        }
        if ($channel === 'sms' && empty($data['recipient_phone'])) {
            return ['success' => false, 'message' => 'SMS channel requires recipient_phone'];
        }
        if ($channel === 'push' && empty($data['recipient_push_token'])) {
            return ['success' => false, 'message' => 'Push channel requires recipient_push_token'];
        }
        
        $sql = "INSERT INTO notification_queue 
                (notification_id, user_id, company_id, channel, 
                 recipient_email, recipient_phone, recipient_push_token,
                 subject, message, scheduled_at, max_attempts, metadata)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $notificationId,
            $userId,
            $companyId,
            $channel,
            $data['recipient_email'] ?? null,
            $data['recipient_phone'] ?? null,
            $data['recipient_push_token'] ?? null,
            $data['subject'] ?? null,
            $data['message'] ?? '',
            $data['scheduled_at'] ?? null, // NULL = immediate
            $data['max_attempts'] ?? 3,
            isset($data['metadata']) && is_array($data['metadata']) ? json_encode($data['metadata']) : null
        ];
        
        try {
            $this->db->queryOn('notification_queue', $sql, $params);
            $id = $this->db->lastInsertId();
            
            // Log în notification_logs
            require_once __DIR__ . '/NotificationLog.php';
            NotificationLog::log('queue', 'enqueued', [
                'queue_id' => $id,
                'notification_id' => $notificationId,
                'channel' => $channel,
                'scheduled_at' => $data['scheduled_at'] ?? 'immediate'
            ], $notificationId);
            
            return ['success' => true, 'queue_id' => $id];
            
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Queue insert failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get pending items din queue (pentru procesare)
     */
    public function getPending($limit = 100, $channel = null) {
        $sql = "SELECT * FROM notification_queue 
                WHERE status = 'pending' 
                  AND (scheduled_at IS NULL OR scheduled_at <= NOW())
                  AND attempts < max_attempts";
        
        $params = [];
        
        if ($channel) {
            $sql .= " AND channel = ?";
            $params[] = $channel;
        }
        
        $sql .= " ORDER BY created_at ASC LIMIT ?";
        $params[] = $limit;
        
        try {
            return $this->db->fetchAllOn('notification_queue', $sql, $params);
        } catch (Throwable $e) {
            return [];
        }
    }
    
    /**
     * Marchează item ca fiind în procesare
     */
    public function markAsProcessing($id) {
        $sql = "UPDATE notification_queue 
                SET status = 'processing', 
                    last_attempt_at = NOW(),
                    attempts = attempts + 1
                WHERE id = ?";
        
        try {
            $this->db->queryOn('notification_queue', $sql, [$id]);
            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Marchează item ca trimis cu succes
     */
    public function markAsSent($id, $metadata = null) {
        $sql = "UPDATE notification_queue 
                SET status = 'sent', 
                    processed_at = NOW()";
        
        if ($metadata && is_array($metadata)) {
            $sql .= ", metadata = ?";
            $params = [json_encode($metadata), $id];
        } else {
            $params = [$id];
        }
        
        $sql .= " WHERE id = ?";
        
        try {
            $this->db->queryOn('notification_queue', $sql, $params);
            
            // Log success
            require_once __DIR__ . '/NotificationLog.php';
            $item = $this->getById($id);
            NotificationLog::log('queue', 'sent', [
                'queue_id' => $id,
                'notification_id' => $item['notification_id'] ?? null,
                'channel' => $item['channel'] ?? 'unknown',
                'attempts' => $item['attempts'] ?? 0
            ], $item['notification_id'] ?? null);
            
            return ['success' => true];
            
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Marchează item ca eșuat
     */
    public function markAsFailed($id, $errorMessage) {
        $item = $this->getById($id);
        
        // Check dacă mai avem încercări rămase
        if ($item && $item['attempts'] >= $item['max_attempts']) {
            // Final failure
            $status = 'failed';
        } else {
            // Retry mai târziu
            $status = 'pending';
        }
        
        $sql = "UPDATE notification_queue 
                SET status = ?, 
                    error_message = ?,
                    last_attempt_at = NOW()
                WHERE id = ?";
        
        try {
            $this->db->queryOn('notification_queue', $sql, [$status, $errorMessage, $id]);
            
            // Log failure
            require_once __DIR__ . '/NotificationLog.php';
            NotificationLog::log('queue', $status === 'failed' ? 'final_failure' : 'retry', [
                'queue_id' => $id,
                'notification_id' => $item['notification_id'] ?? null,
                'channel' => $item['channel'] ?? 'unknown',
                'attempts' => $item['attempts'] ?? 0,
                'max_attempts' => $item['max_attempts'] ?? 3
            ], $item['notification_id'] ?? null, $errorMessage);
            
            return ['success' => true, 'status' => $status];
            
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Retry failed items (doar cele care nu au depășit max_attempts)
     */
    public function retryFailed($limit = 50) {
        $sql = "SELECT * FROM notification_queue 
                WHERE status = 'failed' 
                  AND attempts < max_attempts
                  AND last_attempt_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
                LIMIT ?";
        
        try {
            $items = $this->db->fetchAllOn('notification_queue', $sql, [$limit]);
            
            $retried = 0;
            foreach ($items as $item) {
                $updateSql = "UPDATE notification_queue 
                             SET status = 'pending', error_message = NULL 
                             WHERE id = ?";
                $this->db->queryOn('notification_queue', $updateSql, [$item['id']]);
                $retried++;
            }
            
            return ['success' => true, 'retried' => $retried];
            
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Cancel un item din queue
     */
    public function cancel($id) {
        $sql = "UPDATE notification_queue SET status = 'cancelled' WHERE id = ?";
        
        try {
            $this->db->queryOn('notification_queue', $sql, [$id]);
            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM notification_queue WHERE id = ?";
        return $this->db->fetchOn('notification_queue', $sql, [$id]);
    }
    
    /**
     * Get toate item-urile pentru o notificare
     */
    public function getByNotificationId($notificationId) {
        $sql = "SELECT * FROM notification_queue WHERE notification_id = ? ORDER BY created_at ASC";
        return $this->db->fetchAllOn('notification_queue', $sql, [$notificationId]);
    }
    
    /**
     * Cleanup queue items vechi (sent/cancelled > 30 zile)
     */
    public function cleanup($daysOld = 30) {
        $sql = "DELETE FROM notification_queue 
                WHERE status IN ('sent', 'cancelled', 'failed')
                  AND processed_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        try {
            $this->db->queryOn('notification_queue', $sql, [$daysOld]);
            return ['success' => true, 'affected' => $this->db->rowCount()];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get statistics pentru queue
     */
    public function getStats($companyId = null) {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status='processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status='sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN channel='email' THEN 1 ELSE 0 END) as email_count,
                    SUM(CASE WHEN channel='sms' THEN 1 ELSE 0 END) as sms_count,
                    SUM(CASE WHEN channel='push' THEN 1 ELSE 0 END) as push_count,
                    AVG(attempts) as avg_attempts
                FROM notification_queue";
        
        $params = [];
        
        if ($companyId) {
            $sql .= " WHERE company_id = ?";
            $params[] = $companyId;
        }
        
        try {
            return $this->db->fetchOn('notification_queue', $sql, $params);
        } catch (Throwable $e) {
            return null;
        }
    }
    
    /**
     * Get backlog size (pending items)
     */
    public function getBacklogSize($companyId = null) {
        $sql = "SELECT COUNT(*) as count FROM notification_queue 
                WHERE status = 'pending'";
        
        $params = [];
        
        if ($companyId) {
            $sql .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        try {
            $result = $this->db->fetchOn('notification_queue', $sql, $params);
            return (int)($result['count'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }
    
    /**
     * Check dacă un notification_id are deja items în queue (evită duplicate)
     */
    public function existsForNotification($notificationId, $channel) {
        $sql = "SELECT COUNT(*) as count FROM notification_queue 
                WHERE notification_id = ? AND channel = ? AND status != 'cancelled'";
        
        try {
            $result = $this->db->fetchOn('notification_queue', $sql, [$notificationId, $channel]);
            return (int)($result['count'] ?? 0) > 0;
        } catch (Throwable $e) {
            return false;
        }
    }
}
?>
