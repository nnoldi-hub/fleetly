<?php
/**
 * scripts/cleanup_notifications.php
 * 
 * CRON JOB: Rulează zilnic pentru curățarea datelor vechi
 * Schedule: 0 4 * * * php /path/to/scripts/cleanup_notifications.php
 * 
 * Curăță:
 * - notification_queue: Items cu status='sent'/'cancelled' mai vechi de 30 zile
 * - notifications: Notificări citite mai vechi de 90 zile
 * - notification_logs: Log-uri mai vechi de 180 zile (6 luni)
 * - notification_rate_limits: Resetează counters expirați
 */

// Rulează doar din CLI
if (php_sapi_name() !== 'cli') {
    die("Acest script poate fi rulat doar din linia de comandă.\n");
}

// Autoload dependencies
$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/config.php';
require_once $rootDir . '/config/Database.php';
require_once $rootDir . '/modules/notifications/models/NotificationQueue.php';
require_once $rootDir . '/modules/notifications/models/Notification.php';

// Output formatting
function logMessage($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $colors = [
        'INFO' => "\033[0;32m",
        'WARNING' => "\033[0;33m",
        'ERROR' => "\033[0;31m",
        'SUCCESS' => "\033[1;32m",
        'RESET' => "\033[0m"
    ];
    
    $color = $colors[$type] ?? $colors['INFO'];
    $reset = $colors['RESET'];
    
    echo "{$color}[{$timestamp}] [{$type}]{$reset} {$message}\n";
}

// Start cleanup
logMessage("=== NOTIFICATION CLEANUP START ===", 'INFO');
logMessage("Script: cleanup_notifications.php", 'INFO');
logMessage("Date: " . date('Y-m-d H:i:s'), 'INFO');

try {
    $db = Database::getInstance();
    $queueModel = new NotificationQueue();
    
    // Configuration (can be overridden by command line args)
    $queueRetentionDays = (int)($argv[1] ?? 30);
    $notificationRetentionDays = (int)($argv[2] ?? 90);
    $logRetentionDays = (int)($argv[3] ?? 180);
    
    logMessage("Retention policies:", 'INFO');
    logMessage("  - Queue (sent/cancelled): {$queueRetentionDays} days", 'INFO');
    logMessage("  - Notifications (read): {$notificationRetentionDays} days", 'INFO');
    logMessage("  - Logs: {$logRetentionDays} days", 'INFO');
    
    $totalCleaned = 0;
    $startTime = microtime(true);
    
    // 1. Clean notification_queue (sent/cancelled items)
    logMessage("", 'INFO');
    logMessage("[1/5] Cleaning notification_queue...", 'INFO');
    
    $cleanedQueue = $queueModel->cleanup($queueRetentionDays);
    logMessage("Deleted {$cleanedQueue} queue items older than {$queueRetentionDays} days", 'SUCCESS');
    $totalCleaned += $cleanedQueue;
    
    // 2. Clean old read notifications
    logMessage("", 'INFO');
    logMessage("[2/5] Cleaning read notifications...", 'INFO');
    
    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$notificationRetentionDays} days"));
    $db->query("DELETE FROM notifications WHERE is_read = 1 AND read_at < ?", [$cutoffDate]);
    $cleanedNotifications = $db->affectedRows();
    logMessage("Deleted {$cleanedNotifications} read notifications older than {$notificationRetentionDays} days", 'SUCCESS');
    $totalCleaned += $cleanedNotifications;
    
    // 3. Clean old notification logs
    logMessage("", 'INFO');
    logMessage("[3/5] Cleaning notification_logs...", 'INFO');
    
    $logCutoffDate = date('Y-m-d H:i:s', strtotime("-{$logRetentionDays} days"));
    $db->query("DELETE FROM notification_logs WHERE created_at < ?", [$logCutoffDate]);
    $cleanedLogs = $db->affectedRows();
    logMessage("Deleted {$cleanedLogs} log entries older than {$logRetentionDays} days", 'SUCCESS');
    $totalCleaned += $cleanedLogs;
    
    // 4. Clean expired rate limit counters
    logMessage("", 'INFO');
    logMessage("[4/5] Resetting expired rate limit counters...", 'INFO');
    
    $db->query("UPDATE notification_rate_limits SET count_current = 0 WHERE reset_at < NOW()");
    $resetCounters = $db->affectedRows();
    logMessage("Reset {$resetCounters} expired rate limit counters", 'SUCCESS');
    
    // 5. Optimize tables (compact and rebuild indexes)
    logMessage("", 'INFO');
    logMessage("[5/5] Optimizing tables...", 'INFO');
    
    $tables = [
        'notifications',
        'notification_queue',
        'notification_logs',
        'notification_rate_limits',
        'notification_preferences'
    ];
    
    foreach ($tables as $table) {
        try {
            $db->query("OPTIMIZE TABLE {$table}");
            logMessage("  ✓ Optimized {$table}", 'INFO');
        } catch (Throwable $e) {
            logMessage("  ✗ Failed to optimize {$table}: " . $e->getMessage(), 'WARNING');
        }
    }
    
    // Calculate execution time
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    // Summary
    logMessage("", 'INFO');
    logMessage("=== CLEANUP SUMMARY ===", 'SUCCESS');
    logMessage("Total records deleted: {$totalCleaned}", 'SUCCESS');
    logMessage("  - Queue items: {$cleanedQueue}", 'INFO');
    logMessage("  - Notifications: {$cleanedNotifications}", 'INFO');
    logMessage("  - Log entries: {$cleanedLogs}", 'INFO');
    logMessage("Rate limit counters reset: {$resetCounters}", 'INFO');
    logMessage("Execution time: {$duration}s", 'INFO');
    
    // Get current table sizes
    logMessage("", 'INFO');
    logMessage("Current table statistics:", 'INFO');
    foreach ($tables as $table) {
        try {
            $countRow = $db->fetch("SELECT COUNT(*) as total FROM {$table}");
            $count = $countRow['total'] ?? 0;
            logMessage("  - {$table}: " . number_format($count) . " records", 'INFO');
        } catch (Throwable $e) {
            logMessage("  - {$table}: Unable to count", 'WARNING');
        }
    }
    
    // Log cleanup action
    NotificationLog::log('cleanup', 'success', [
        'script' => 'cleanup_notifications.php',
        'queue_cleaned' => $cleanedQueue,
        'notifications_cleaned' => $cleanedNotifications,
        'logs_cleaned' => $cleanedLogs,
        'rate_limits_reset' => $resetCounters,
        'duration' => $duration,
        'retention_days' => [
            'queue' => $queueRetentionDays,
            'notifications' => $notificationRetentionDays,
            'logs' => $logRetentionDays
        ]
    ]);
    
    logMessage("=== CLEANUP FINISHED (EXIT CODE: 0) ===", 'SUCCESS');
    exit(0);
    
} catch (Throwable $e) {
    logMessage("FATAL ERROR: " . $e->getMessage(), 'ERROR');
    logMessage("Stack trace:", 'ERROR');
    logMessage($e->getTraceAsString(), 'ERROR');
    
    // Log error
    try {
        NotificationLog::log('cleanup', 'error', [
            'script' => 'cleanup_notifications.php',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], null, $e->getMessage());
    } catch (Throwable $logError) {
        logMessage("Failed to log error to database: " . $logError->getMessage(), 'ERROR');
    }
    
    logMessage("=== CLEANUP TERMINATED (EXIT CODE: 2) ===", 'ERROR');
    exit(2);
}
