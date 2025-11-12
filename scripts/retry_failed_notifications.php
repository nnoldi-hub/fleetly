<?php
/**
 * scripts/retry_failed_notifications.php
 * 
 * CRON JOB: Rulează la fiecare oră pentru reîncercarea notificărilor eșuate
 * Schedule: 0 * * * * php /path/to/scripts/retry_failed_notifications.php
 * 
 * Procesează notificări din queue cu status='failed':
 * - Verifică dacă attempts < max_attempts
 * - Verifică dacă au trecut cel puțin 1h de la ultima încercare
 * - Re-încearcă trimiterea cu exponential backoff
 * - Marchează ca 'cancelled' dacă max_attempts atins
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
require_once $rootDir . '/modules/notifications/services/NotificationQueueProcessor.php';

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

// Start retry process
logMessage("=== NOTIFICATION RETRY PROCESSOR START ===", 'INFO');
logMessage("Script: retry_failed_notifications.php", 'INFO');

try {
    $queueModel = new NotificationQueue();
    $processor = new NotificationQueueProcessor();
    
    // Get failed items count
    $db = Database::getInstance();
    $countRow = $db->fetch("SELECT COUNT(*) as total FROM notification_queue WHERE status = 'failed' AND attempts < max_attempts");
    $totalRetryable = $countRow['total'] ?? 0;
    
    logMessage("Found {$totalRetryable} failed items eligible for retry", 'INFO');
    
    if ($totalRetryable === 0) {
        logMessage("No items to retry. Exiting.", 'INFO');
        logMessage("=== RETRY PROCESSOR FINISHED ===", 'INFO');
        exit(0);
    }
    
    // Retry failed items (max 50 per run)
    $limit = (int)($argv[1] ?? 50);
    logMessage("Retrying up to {$limit} items...", 'INFO');
    
    $startTime = microtime(true);
    $retriedCount = $queueModel->retryFailed($limit);
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    logMessage("Retry batch completed in {$duration}s", 'INFO');
    logMessage("Items moved to pending: {$retriedCount}", 'SUCCESS');
    
    // Now process the retried items immediately
    if ($retriedCount > 0) {
        logMessage("Processing retried items immediately...", 'INFO');
        
        $processResult = $processor->processQueue($retriedCount);
        
        logMessage("Processing results:", 'INFO');
        logMessage("  - Sent: {$processResult['sent']}", 'SUCCESS');
        logMessage("  - Failed again: {$processResult['failed']}", ($processResult['failed'] > 0 ? 'WARNING' : 'INFO'));
        logMessage("  - Skipped: {$processResult['skipped']}", 'INFO');
        
        // Log retry errors
        if (!empty($processResult['errors'])) {
            logMessage("Errors during retry processing:", 'ERROR');
            foreach ($processResult['errors'] as $error) {
                logMessage("  - Queue ID {$error['id']}: {$error['error']}", 'ERROR');
            }
        }
        
        // Calculate retry success rate
        $totalRetried = $processResult['sent'] + $processResult['failed'];
        if ($totalRetried > 0) {
            $retrySuccessRate = round(($processResult['sent'] / $totalRetried) * 100, 2);
            logMessage("Retry success rate: {$retrySuccessRate}%", ($retrySuccessRate >= 50 ? 'SUCCESS' : 'WARNING'));
        }
    }
    
    // Check for items that exceeded max_attempts and need cancellation
    $maxAttemptsRow = $db->fetch("SELECT COUNT(*) as total FROM notification_queue WHERE status = 'failed' AND attempts >= max_attempts");
    $maxedOut = $maxAttemptsRow['total'] ?? 0;
    
    if ($maxedOut > 0) {
        logMessage("Found {$maxedOut} items that exceeded max attempts", 'WARNING');
        logMessage("Cancelling items with max_attempts reached...", 'INFO');
        
        // Update to cancelled
        $db->query("UPDATE notification_queue SET status = 'cancelled', processed_at = NOW() 
                    WHERE status = 'failed' AND attempts >= max_attempts");
        $cancelled = $db->affectedRows();
        
        logMessage("Cancelled {$cancelled} items", 'WARNING');
        
        // Log for admin review
        NotificationLog::log('queue_retry', 'max_attempts_reached', [
            'script' => 'retry_failed_notifications.php',
            'cancelled_count' => $cancelled,
            'message' => 'Items cancelled after reaching max_attempts. Manual review recommended.'
        ]);
    }
    
    // Final statistics
    $remainingFailed = $db->fetch("SELECT COUNT(*) as total FROM notification_queue WHERE status = 'failed'");
    $stillFailed = $remainingFailed['total'] ?? 0;
    
    logMessage("Remaining failed items: {$stillFailed}", ($stillFailed > 0 ? 'WARNING' : 'INFO'));
    
    // Exit with appropriate code
    $exitCode = ($stillFailed > 100) ? 1 : 0; // Warning if too many failures
    logMessage("=== RETRY PROCESSOR FINISHED (EXIT CODE: {$exitCode}) ===", 'INFO');
    exit($exitCode);
    
} catch (Throwable $e) {
    logMessage("FATAL ERROR: " . $e->getMessage(), 'ERROR');
    logMessage("Stack trace:", 'ERROR');
    logMessage($e->getTraceAsString(), 'ERROR');
    
    // Log to database
    try {
        NotificationLog::log('queue_retry', 'error', [
            'script' => 'retry_failed_notifications.php',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], null, $e->getMessage());
    } catch (Throwable $logError) {
        logMessage("Failed to log error to database: " . $logError->getMessage(), 'ERROR');
    }
    
    logMessage("=== RETRY PROCESSOR TERMINATED (EXIT CODE: 2) ===", 'ERROR');
    exit(2);
}
