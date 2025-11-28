<?php
/**
 * scripts/process_notifications_queue.php
 * 
 * CRON JOB: Rulează la fiecare 5 minute pentru procesarea queue-ului de notificări
 * Schedule: "* /5 * * * *" php /path/to/scripts/process_notifications_queue.php
 * 
 * Procesează notificări pending din notification_queue:
 * - Verifică preferințe utilizator (canale active, quiet hours)
 * - Aplică rate limiting per company/channel
 * - Trimite prin Notifier service (email/SMS/push)
 * - Actualizează status în queue și notification
 */

// Rulează doar din CLI
if (php_sapi_name() !== 'cli') {
    die("Acest script poate fi rulat doar din linia de comandă.\n");
}

// Autoload dependencies
$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/config.php';
require_once $rootDir . '/config/database.php';
require_once $rootDir . '/core/Database.php';
require_once $rootDir . '/modules/notifications/models/NotificationLog.php';
require_once $rootDir . '/modules/notifications/services/NotificationQueueProcessor.php';

// Output formatting
function logMessage($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $colors = [
        'INFO' => "\033[0;32m",    // Green
        'WARNING' => "\033[0;33m", // Yellow
        'ERROR' => "\033[0;31m",   // Red
        'SUCCESS' => "\033[1;32m", // Bold Green
        'RESET' => "\033[0m"
    ];
    
    $color = $colors[$type] ?? $colors['INFO'];
    $reset = $colors['RESET'];
    
    echo "{$color}[{$timestamp}] [{$type}]{$reset} {$message}\n";
}

// Start processing
logMessage("=== NOTIFICATION QUEUE PROCESSOR START ===", 'INFO');
logMessage("Script: process_notifications_queue.php", 'INFO');

try {
    // Initialize processor
    $processor = new NotificationQueueProcessor();
    
    // Get initial stats
    $initialStats = $processor->getProcessingStats();
    logMessage("Queue backlog: {$initialStats['total_pending']} items", 'INFO');
    
    if ($initialStats['total_pending'] > 0) {
        logMessage("Oldest item: " . ($initialStats['oldest_pending'] ?? 'N/A'), 'INFO');
        
        // Log per-channel breakdown
        if (!empty($initialStats['pending_per_channel'])) {
            logMessage("Breakdown by channel:", 'INFO');
            foreach ($initialStats['pending_per_channel'] as $channel => $count) {
                logMessage("  - {$channel}: {$count} items", 'INFO');
            }
        }
    }
    
    // Process queue (max 100 items per run to avoid timeout)
    $limit = (int)($argv[1] ?? 100);
    logMessage("Processing up to {$limit} items...", 'INFO');
    
    $startTime = microtime(true);
    $result = $processor->processQueue($limit);
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    // Log results
    logMessage("Processing completed in {$duration}s", 'INFO');
    logMessage("Sent: {$result['sent']}", 'SUCCESS');
    logMessage("Failed: {$result['failed']}", ($result['failed'] > 0 ? 'WARNING' : 'INFO'));
    logMessage("Skipped: {$result['skipped']}", 'INFO');
    
    // Log errors if any
    if (!empty($result['errors'])) {
        logMessage("Errors encountered:", 'ERROR');
        foreach ($result['errors'] as $error) {
            logMessage("  - Queue ID {$error['id']}: {$error['error']}", 'ERROR');
        }
    }
    
    // Final stats
    $finalStats = $processor->getProcessingStats();
    logMessage("Remaining in queue: {$finalStats['total_pending']} items", 'INFO');
    
    // Performance metrics
    $itemsPerSecond = $duration > 0 ? round(($result['sent'] + $result['failed']) / $duration, 2) : 0;
    logMessage("Performance: {$itemsPerSecond} items/second", 'INFO');
    
    // Success rate
    $totalProcessed = $result['sent'] + $result['failed'];
    if ($totalProcessed > 0) {
        $successRate = round(($result['sent'] / $totalProcessed) * 100, 2);
        logMessage("Success rate: {$successRate}%", ($successRate >= 95 ? 'SUCCESS' : 'WARNING'));
    }
    
    // Exit with appropriate code
    $exitCode = ($result['failed'] > 0) ? 1 : 0;
    logMessage("=== QUEUE PROCESSOR FINISHED (EXIT CODE: {$exitCode}) ===", 'INFO');
    exit($exitCode);
    
} catch (Throwable $e) {
    logMessage("FATAL ERROR: " . $e->getMessage(), 'ERROR');
    logMessage("Stack trace:", 'ERROR');
    logMessage($e->getTraceAsString(), 'ERROR');
    
    // Log to database if possible
    try {
        NotificationLog::log('queue_processor', 'error', [
            'script' => 'process_notifications_queue.php',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], null, $e->getMessage());
    } catch (Throwable $logError) {
        logMessage("Failed to log error to database: " . $logError->getMessage(), 'ERROR');
    }
    
    logMessage("=== QUEUE PROCESSOR TERMINATED (EXIT CODE: 2) ===", 'ERROR');
    exit(2);
}
