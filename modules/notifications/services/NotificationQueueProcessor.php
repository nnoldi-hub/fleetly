<?php
// modules/notifications/services/NotificationQueueProcessor.php

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../models/NotificationQueue.php';
require_once __DIR__ . '/../models/NotificationPreference.php';
require_once __DIR__ . '/../models/NotificationLog.php';
require_once __DIR__ . '/Notifier.php';

/**
 * Service pentru procesarea queue-ului de notificări
 * Rulează la fiecare 5 minute via cron
 */
class NotificationQueueProcessor {
    private $db;
    private $queue;
    private $notifier;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->queue = new NotificationQueue();
        $this->notifier = new Notifier();
    }
    
    /**
     * Procesează queue-ul de notificări pending
     * @param int $limit Max items de procesat într-o rulare
     * @return array Statistici ['sent' => N, 'failed' => N, 'skipped' => N]
     */
    public function processQueue($limit = 100) {
        $stats = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        // Get pending items
        $items = $this->queue->getPending($limit);
        
        if (empty($items)) {
            return $stats;
        }
        
        foreach ($items as $item) {
            try {
                $result = $this->processItem($item);
                
                if ($result['status'] === 'sent') {
                    $stats['sent']++;
                } elseif ($result['status'] === 'failed') {
                    $stats['failed']++;
                    $stats['errors'][] = [
                        'queue_id' => $item['id'],
                        'error' => $result['error'] ?? 'Unknown error'
                    ];
                } elseif ($result['status'] === 'skipped') {
                    $stats['skipped']++;
                }
                
            } catch (Throwable $e) {
                $stats['failed']++;
                $stats['errors'][] = [
                    'queue_id' => $item['id'],
                    'error' => $e->getMessage()
                ];
                
                // Mark as failed
                $this->queue->markAsFailed($item['id'], $e->getMessage());
            }
        }
        
        // Log summary
        NotificationLog::log('queue_processing', 'completed', [
            'processed' => count($items),
            'sent' => $stats['sent'],
            'failed' => $stats['failed'],
            'skipped' => $stats['skipped']
        ], null);
        
        return $stats;
    }
    
    /**
     * Procesează un singur item din queue
     * @param array $item
     * @return array ['status' => 'sent'|'failed'|'skipped', 'error' => '...']
     */
    private function processItem($item) {
        // Marchează ca fiind în procesare
        $this->queue->markAsProcessing($item['id']);
        
        // Get user preferences
        $prefs = new NotificationPreference();
        $userPrefs = $prefs->getByUserId($item['user_id']);
        
        // Dacă user nu are preferințe, folosim default (toate canalele active)
        if (!$userPrefs) {
            $userPrefs = $prefs->getDefaultPreferences($item['user_id'], $item['company_id']);
        }
        
        // Check 1: Canal activat?
        $channelField = $item['channel'] . '_enabled';
        if (!isset($userPrefs[$channelField]) || !$userPrefs[$channelField]) {
            $this->queue->cancel($item['id']);
            return ['status' => 'skipped', 'reason' => 'Channel disabled in preferences'];
        }
        
        // Check 2: Quiet hours?
        if ($prefs->isInQuietHours($item['user_id'])) {
            // Reschedule pentru mâine dimineață (09:00)
            $tomorrow = date('Y-m-d 09:00:00', strtotime('tomorrow'));
            $this->db->query(
                "UPDATE notification_queue SET scheduled_at = ?, status = 'pending' WHERE id = ?",
                [$tomorrow, $item['id']]
            );
            return ['status' => 'skipped', 'reason' => 'In quiet hours, rescheduled'];
        }
        
        // Check 3: Rate limiting
        if (!$this->checkRateLimit($item['company_id'], $item['channel'])) {
            // Reschedule pentru +1 hour
            $later = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $this->db->query(
                "UPDATE notification_queue SET scheduled_at = ?, status = 'pending' WHERE id = ?",
                [$later, $item['id']]
            );
            return ['status' => 'skipped', 'reason' => 'Rate limit exceeded, rescheduled'];
        }
        
        // Procesează pe bază de canal
        switch ($item['channel']) {
            case 'email':
                return $this->sendEmail($item);
                
            case 'sms':
                return $this->sendSms($item);
                
            case 'push':
                return $this->sendPush($item);
                
            case 'in_app':
                // In-app notifications sunt deja în tabela notifications, doar marcăm ca trimis
                $this->queue->markAsSent($item['id']);
                return ['status' => 'sent', 'channel' => 'in_app'];
                
            default:
                $this->queue->markAsFailed($item['id'], 'Unknown channel: ' . $item['channel']);
                return ['status' => 'failed', 'error' => 'Unknown channel'];
        }
    }
    
    /**
     * Trimite notificare prin email
     */
    private function sendEmail($item) {
        if (empty($item['recipient_email'])) {
            $this->queue->markAsFailed($item['id'], 'Missing recipient email');
            return ['status' => 'failed', 'error' => 'Missing recipient email'];
        }
        
        [$success, $error] = $this->notifier->sendEmail(
            $item['recipient_email'],
            $item['subject'] ?? 'Notificare Fleet Management',
            $item['message']
        );
        
        if ($success) {
            $this->queue->markAsSent($item['id']);
            $this->incrementRateLimit($item['company_id'], 'email');
            
            // Update notification status
            $this->updateNotificationStatus($item['notification_id']);
            
            return ['status' => 'sent', 'channel' => 'email'];
        } else {
            $this->queue->markAsFailed($item['id'], $error);
            return ['status' => 'failed', 'error' => $error];
        }
    }
    
    /**
     * Trimite notificare prin SMS
     */
    private function sendSms($item) {
        if (empty($item['recipient_phone'])) {
            $this->queue->markAsFailed($item['id'], 'Missing recipient phone');
            return ['status' => 'failed', 'error' => 'Missing recipient phone'];
        }
        
        // Truncate message la 160 caractere pentru SMS
        $message = mb_substr($item['message'], 0, 160);
        
        [$success, $error] = $this->notifier->sendSms(
            $item['recipient_phone'],
            $message
        );
        
        if ($success) {
            $this->queue->markAsSent($item['id']);
            $this->incrementRateLimit($item['company_id'], 'sms');
            
            // Update notification status
            $this->updateNotificationStatus($item['notification_id']);
            
            return ['status' => 'sent', 'channel' => 'sms'];
        } else {
            $this->queue->markAsFailed($item['id'], $error);
            return ['status' => 'failed', 'error' => $error];
        }
    }
    
    /**
     * Trimite notificare prin push (Firebase/OneSignal)
     */
    private function sendPush($item) {
        if (empty($item['recipient_push_token'])) {
            $this->queue->markAsFailed($item['id'], 'Missing push token');
            return ['status' => 'failed', 'error' => 'Missing push token'];
        }
        
        // TODO: Implementare push notifications cu Firebase/OneSignal
        // Pentru MVP, marcăm ca skipped
        $this->queue->cancel($item['id']);
        return ['status' => 'skipped', 'reason' => 'Push notifications not yet implemented'];
    }
    
    /**
     * Update notification status după trimitere cu succes
     */
    private function updateNotificationStatus($notificationId) {
        if (!$notificationId) return;
        
        try {
            $this->db->query(
                "UPDATE notifications SET status = 'sent', sent_at = NOW() WHERE id = ?",
                [$notificationId]
            );
        } catch (Throwable $e) {
            // Ignore error, notification already exists
        }
    }
    
    /**
     * Check rate limiting pentru a preveni spam și costuri excesive
     * @param int $companyId
     * @param string $channel
     * @return bool True dacă poate trimite, false dacă limita e depășită
     */
    private function checkRateLimit($companyId, $channel) {
        try {
            // Get sau create rate limit entry
            $limit = $this->db->fetch(
                "SELECT * FROM notification_rate_limits WHERE company_id = ? AND channel = ?",
                [$companyId, $channel]
            );
            
            if (!$limit) {
                // Create default limits
                $limitsPerChannel = [
                    'email' => ['hourly' => 100, 'daily' => 1000],
                    'sms' => ['hourly' => 20, 'daily' => 100],
                    'push' => ['hourly' => 500, 'daily' => 5000]
                ];
                
                $limits = $limitsPerChannel[$channel] ?? ['hourly' => 100, 'daily' => 500];
                
                $this->db->query(
                    "INSERT INTO notification_rate_limits (company_id, channel, count_current, reset_at, limit_hourly, limit_daily) 
                     VALUES (?, ?, 0, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?, ?)",
                    [$companyId, $channel, $limits['hourly'], $limits['daily']]
                );
                
                return true; // Prima trimitere, OK
            }
            
            // Check dacă trebuie resetat counter-ul
            if (strtotime($limit['reset_at']) <= time()) {
                $this->db->query(
                    "UPDATE notification_rate_limits SET count_current = 0, reset_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?",
                    [$limit['id']]
                );
                return true;
            }
            
            // Check limita hourly (prioritate)
            if ($limit['count_current'] >= $limit['limit_hourly']) {
                return false; // Limită depășită
            }
            
            return true;
            
        } catch (Throwable $e) {
            // În caz de eroare, permitem trimiterea (fail-open)
            return true;
        }
    }
    
    /**
     * Incrementează counter-ul pentru rate limiting
     */
    private function incrementRateLimit($companyId, $channel) {
        try {
            $this->db->query(
                "UPDATE notification_rate_limits SET count_current = count_current + 1 WHERE company_id = ? AND channel = ?",
                [$companyId, $channel]
            );
        } catch (Throwable $e) {
            // Ignore
        }
    }
    
    /**
     * Procesează doar un anumit canal (pentru debugging)
     * @param string $channel email|sms|push|in_app
     * @param int $limit
     * @return array
     */
    public function processByChannel($channel, $limit = 50) {
        $items = $this->queue->getPending($limit, $channel);
        
        $stats = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'channel' => $channel
        ];
        
        foreach ($items as $item) {
            try {
                $result = $this->processItem($item);
                
                if ($result['status'] === 'sent') {
                    $stats['sent']++;
                } elseif ($result['status'] === 'failed') {
                    $stats['failed']++;
                } else {
                    $stats['skipped']++;
                }
                
            } catch (Throwable $e) {
                $stats['failed']++;
                $this->queue->markAsFailed($item['id'], $e->getMessage());
            }
        }
        
        return $stats;
    }
    
    /**
     * Get statistici despre procesare (pentru monitoring)
     * @return array
     */
    public function getProcessingStats() {
        try {
            $stats = $this->db->fetch("
                SELECT 
                    COUNT(*) as total_pending,
                    SUM(CASE WHEN channel='email' THEN 1 ELSE 0 END) as pending_email,
                    SUM(CASE WHEN channel='sms' THEN 1 ELSE 0 END) as pending_sms,
                    SUM(CASE WHEN channel='push' THEN 1 ELSE 0 END) as pending_push,
                    AVG(attempts) as avg_attempts,
                    MIN(created_at) as oldest_pending
                FROM notification_queue
                WHERE status = 'pending' AND (scheduled_at IS NULL OR scheduled_at <= NOW())
            ");
            
            return $stats ?: [];
            
        } catch (Throwable $e) {
            return [];
        }
    }
}
?>
