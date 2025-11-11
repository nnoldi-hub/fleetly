<?php
// modules/notifications/models/NotificationLog.php

require_once __DIR__ . '/../../../core/Model.php';
require_once __DIR__ . '/../../../core/Database.php';

class NotificationLog extends Model {
    protected $table = 'notification_logs';

    private static function ensureTable($db) {
        try {
            $db->query("CREATE TABLE IF NOT EXISTS notification_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                notification_id INT NULL,
                type VARCHAR(50) NOT NULL,
                context TEXT NULL,
                status ENUM('created','skipped','error','sent') DEFAULT 'created',
                error_message TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_notification_id (notification_id),
                INDEX idx_type (type),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (Throwable $e) {
            // Ignorăm eroarea pentru a nu bloca aplicația
        }
    }

    public static function log($type, $status = 'created', $context = [], $notificationId = null, $errorMessage = null) {
        $instance = new self();
        $db = $instance->db; // referință DB core/tenant actual

        self::ensureTable($db);

        $ctxSerialized = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : null;
        try {
            $db->queryOn('notification_logs', "INSERT INTO notification_logs (notification_id, type, context, status, error_message, created_at) VALUES (?, ?, ?, ?, ?, NOW())", [
                $notificationId,
                $type,
                $ctxSerialized,
                $status,
                $errorMessage
            ]);
        } catch (Throwable $e) {
            // Nu blocăm logica principală dacă log-ul eșuează
        }
    }
}
?>