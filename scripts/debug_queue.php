<?php
/**
 * DEBUG SCRIPT - Detailed queue processing debug
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$rootDir = dirname(__DIR__);

try {
    require_once $rootDir . '/config/config.php';
    require_once $rootDir . '/core/Database.php';
} catch (Throwable $e) {
    die("Failed to load config: " . $e->getMessage());
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Queue Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .step { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #0066cc; }
        .success { border-left-color: #00aa00; }
        .error { border-left-color: #cc0000; background: #fee; }
        .warning { border-left-color: #ff9900; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        h2 { color: #333; margin-top: 30px; }
    </style>
</head>
<body>
    <h1>🔍 Queue Processing Debug</h1>

<?php
$db = Database::getInstance();

// Step 1: Check database connection
echo "<div class='step success'>";
echo "<strong>✓ Step 1:</strong> Database connected<br>";
echo "Database: " . $db->getConnection()->query("SELECT DATABASE()")->fetchColumn();
echo "</div>";

// Step 2: Check notification_queue table
echo "<div class='step'>";
echo "<strong>Step 2:</strong> Checking notification_queue table<br>";
$items = $db->fetchAll("SELECT * FROM notification_queue ORDER BY created_at DESC LIMIT 3");
echo "Found " . count($items) . " items in queue<br>";
echo "<pre>" . print_r($items, true) . "</pre>";
echo "</div>";

// Step 3: Test getPending query manually
echo "<div class='step'>";
echo "<strong>Step 3:</strong> Testing getPending query<br>";
$sql = "SELECT * FROM notification_queue 
        WHERE status = 'pending' 
          AND (scheduled_at IS NULL OR scheduled_at <= NOW())
          AND attempts < max_attempts
        ORDER BY created_at ASC LIMIT 10";
echo "SQL: <pre>{$sql}</pre>";
$pending = $db->fetchAll($sql);
echo "Found " . count($pending) . " pending items<br>";
echo "<pre>" . print_r($pending, true) . "</pre>";
echo "</div>";

// Step 4: Check if NotificationQueue model exists
echo "<div class='step'>";
echo "<strong>Step 4:</strong> Loading NotificationQueue model<br>";
require_once $rootDir . '/modules/notifications/models/NotificationQueue.php';
$queue = new NotificationQueue();
echo "✓ Model loaded<br>";

$pendingFromModel = $queue->getPending(10);
echo "getPending() returned " . count($pendingFromModel) . " items<br>";
echo "<pre>" . print_r($pendingFromModel, true) . "</pre>";
echo "</div>";

// Step 5: Check NotificationPreference
echo "<div class='step'>";
echo "<strong>Step 5:</strong> Checking user preferences<br>";
require_once $rootDir . '/modules/notifications/models/NotificationPreference.php';
$prefModel = new NotificationPreference();

if (!empty($pending)) {
    $firstItem = $pending[0];
    echo "Checking preferences for user_id: {$firstItem['user_id']}<br>";
    
    $userPrefs = $prefModel->getByUserId($firstItem['user_id']);
    echo "Preferences found: " . ($userPrefs ? 'YES' : 'NO') . "<br>";
    echo "<pre>" . print_r($userPrefs, true) . "</pre>";
    
    // Check if email is enabled
    if ($userPrefs && isset($userPrefs['email_enabled'])) {
        $emailEnabled = $userPrefs['email_enabled'];
        echo "<div class='" . ($emailEnabled ? "success" : "error") . "'>";
        echo "Email notifications: " . ($emailEnabled ? "✓ ENABLED" : "✗ DISABLED");
        echo "</div>";
    } else {
        echo "<div class='warning'>⚠ No preferences found, using defaults</div>";
    }
}
echo "</div>";

// Step 6: Check Notifier service
echo "<div class='step'>";
echo "<strong>Step 6:</strong> Testing Notifier service<br>";
require_once $rootDir . '/modules/notifications/services/Notifier.php';
$notifier = new Notifier();
echo "✓ Notifier loaded<br>";

// Check mail config
require_once $rootDir . '/config/mail.php';
global $mailConfig;
echo "SMTP Config:<br>";
echo "- Host: " . ($mailConfig['smtp_host'] ?? 'NOT SET') . "<br>";
echo "- Port: " . ($mailConfig['smtp_port'] ?? 'NOT SET') . "<br>";
echo "- Username: " . ($mailConfig['smtp_username'] ?? 'NOT SET') . "<br>";
echo "- From: " . ($mailConfig['from_email'] ?? 'NOT SET') . "<br>";
echo "</div>";

// Step 7: Attempt to process ONE item
if (!empty($pending)) {
    echo "<h2>🚀 Attempting to process first item</h2>";
    
    $item = $pending[0];
    echo "<div class='step'>";
    echo "<strong>Processing item ID:</strong> {$item['id']}<br>";
    echo "<strong>Channel:</strong> {$item['channel']}<br>";
    echo "<strong>Recipient:</strong> {$item['recipient_email']}<br>";
    echo "<strong>Subject:</strong> {$item['subject']}<br>";
    
    try {
        // Load processor
        require_once $rootDir . '/modules/notifications/services/NotificationQueueProcessor.php';
        $processor = new NotificationQueueProcessor();
        
        echo "<br><strong>🔄 Processing...</strong><br>";
        $result = $processor->processQueue(1); // Process only 1 item
        
        echo "<div class='" . ($result['sent'] > 0 ? "success" : "error") . "'>";
        echo "Result:<br>";
        echo "- Sent: {$result['sent']}<br>";
        echo "- Failed: {$result['failed']}<br>";
        echo "- Skipped: {$result['skipped']}<br>";
        
        if (!empty($result['errors'])) {
            echo "<br><strong>Errors:</strong><br>";
            foreach ($result['errors'] as $error) {
                echo "- Queue ID {$error['id']}: {$error['error']}<br>";
            }
        }
        echo "</div>";
        
        // Check updated status
        echo "<br><strong>Checking updated status:</strong><br>";
        $updated = $db->fetch("SELECT * FROM notification_queue WHERE id = ?", [$item['id']]);
        echo "Status: {$updated['status']}<br>";
        echo "Attempts: {$updated['attempts']}<br>";
        if ($updated['error_message']) {
            echo "Error: {$updated['error_message']}<br>";
        }
        
    } catch (Throwable $e) {
        echo "<div class='error'>";
        echo "❌ EXCEPTION: " . $e->getMessage() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
    }
    
    echo "</div>";
} else {
    echo "<div class='warning'>";
    echo "⚠ No pending items to process";
    echo "</div>";
}

?>

<hr>
<p><a href="javascript:location.reload()">🔄 Run Again</a></p>

</body>
</html>
