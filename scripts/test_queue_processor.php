<?php
/**
 * TEST SCRIPT - Web accessible version of queue processor
 * DELETE THIS FILE after testing!
 */

// Require auth (comment out for testing)
// session_start();
// if (!isset($_SESSION['user_id'])) {
//     die("Unauthorized");
// }

$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/config.php';
require_once $rootDir . '/core/Database.php';
require_once $rootDir . '/modules/notifications/services/NotificationQueueProcessor.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Queue Processor</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .log { background: white; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: #0066cc; }
        .success { color: #00aa00; font-weight: bold; }
        .warning { color: #ff9900; }
        .error { color: #cc0000; font-weight: bold; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>🔄 Notification Queue Processor Test</h1>
    
<?php
function logWeb($message, $type = 'info') {
    echo "<div class='log {$type}'>[" . date('H:i:s') . "] [{$type}] {$message}</div>";
    flush();
}

try {
    logWeb("=== STARTING QUEUE PROCESSOR ===", 'info');
    
    // Initialize processor
    $processor = new NotificationQueueProcessor();
    
    // Get initial stats
    $initialStats = $processor->getProcessingStats();
    logWeb("Queue backlog: {$initialStats['total_pending']} items", 'info');
    
    if ($initialStats['total_pending'] > 0) {
        if (isset($initialStats['oldest_pending'])) {
            logWeb("Oldest item: {$initialStats['oldest_pending']}", 'info');
        }
        
        if (!empty($initialStats['pending_per_channel'])) {
            logWeb("Breakdown by channel:", 'info');
            foreach ($initialStats['pending_per_channel'] as $channel => $count) {
                logWeb("&nbsp;&nbsp;- {$channel}: {$count} items", 'info');
            }
        }
    }
    
    // Process queue
    $limit = 100;
    logWeb("Processing up to {$limit} items...", 'info');
    
    $startTime = microtime(true);
    $result = $processor->processQueue($limit);
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    // Log results
    logWeb("Processing completed in {$duration}s", 'info');
    logWeb("✓ Sent: {$result['sent']}", 'success');
    
    if ($result['failed'] > 0) {
        logWeb("✗ Failed: {$result['failed']}", 'warning');
    } else {
        logWeb("✓ Failed: {$result['failed']}", 'info');
    }
    
    logWeb("○ Skipped: {$result['skipped']}", 'info');
    
    // Log errors
    if (!empty($result['errors'])) {
        logWeb("Errors encountered:", 'error');
        foreach ($result['errors'] as $error) {
            logWeb("&nbsp;&nbsp;- Queue ID {$error['id']}: {$error['error']}", 'error');
        }
    }
    
    // Final stats
    $finalStats = $processor->getProcessingStats();
    logWeb("Remaining in queue: {$finalStats['total_pending']} items", 'info');
    
    // Performance
    $itemsPerSecond = $duration > 0 ? round(($result['sent'] + $result['failed']) / $duration, 2) : 0;
    logWeb("Performance: {$itemsPerSecond} items/second", 'info');
    
    // Success rate
    $totalProcessed = $result['sent'] + $result['failed'];
    if ($totalProcessed > 0) {
        $successRate = round(($result['sent'] / $totalProcessed) * 100, 2);
        $rateType = $successRate >= 95 ? 'success' : 'warning';
        logWeb("Success rate: {$successRate}%", $rateType);
    }
    
    logWeb("=== QUEUE PROCESSOR FINISHED ===", 'success');
    
    echo "<hr>";
    echo "<h2>📊 Detailed Results</h2>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
} catch (Throwable $e) {
    logWeb("FATAL ERROR: " . $e->getMessage(), 'error');
    logWeb("Stack trace:", 'error');
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<hr>
<p><strong>⚠️ IMPORTANT:</strong> Delete this file after testing!</p>
<p><a href="javascript:location.reload()">🔄 Run Again</a></p>

</body>
</html>
