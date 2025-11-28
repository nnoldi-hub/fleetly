<?php
/**
 * Web Cron Runner - Rulează cron jobs direct din browser
 * Accesează: https://management.nks-cables.ro/run_cron.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minute timeout

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.step { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #4CAF50; }
.error { border-color: #f44336; color: #f44336; }
.success { color: #4CAF50; font-weight: bold; }
.warning { color: #ff9800; }
pre { background: #f9f9f9; padding: 10px; overflow-x: auto; white-space: pre-wrap; }
</style></head><body>";

echo "<h1>🚀 Web Cron Runner</h1>";
echo "<p><strong>Data/Ora:</strong> " . date('Y-m-d H:i:s') . "</p><hr>";

// ============================================================
// STEP 1: Generate Notifications
// ============================================================
echo "<div class='step'>";
echo "<h2>📝 STEP 1: Generare Notificări</h2>";

try {
    ob_start();
    require __DIR__ . '/scripts/cron_generate_notifications.php';
    $output = ob_get_clean();
    
    echo "<div class='success'>✅ Script executat cu succes!</div>";
    echo "<pre>$output</pre>";
    
} catch (Throwable $e) {
    $error = ob_get_clean();
    echo "<div class='error'>❌ Eroare: " . $e->getMessage() . "</div>";
    echo "<pre>File: " . $e->getFile() . "\nLine: " . $e->getLine() . "\n\n" . $e->getTraceAsString() . "</pre>";
    if ($error) echo "<pre>Output: $error</pre>";
}

echo "</div>";

// ============================================================
// STEP 2: Process Queue
// ============================================================
echo "<div class='step'>";
echo "<h2>📧 STEP 2: Procesare Coadă Email</h2>";

try {
    ob_start();
    require __DIR__ . '/scripts/process_notifications_queue.php';
    $output = ob_get_clean();
    
    echo "<div class='success'>✅ Script executat cu succes!</div>";
    echo "<pre>$output</pre>";
    
} catch (Throwable $e) {
    $error = ob_get_clean();
    echo "<div class='error'>❌ Eroare: " . $e->getMessage() . "</div>";
    echo "<pre>File: " . $e->getFile() . "\nLine: " . $e->getLine() . "\n\n" . $e->getTraceAsString() . "</pre>";
    if ($error) echo "<pre>Output: $error</pre>";
}

echo "</div>";

// ============================================================
// STEP 3: Summary
// ============================================================
echo "<div class='step'>";
echo "<h2>📊 STEP 3: Verificare Rezultate</h2>";

try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/core/Database.php';
    
    $db = Database::getInstance();
    
    // Check notifications
    $result = $db->fetchOn('notifications', 
        "SELECT COUNT(*) as count FROM notifications 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    );
    echo "<p>📬 Notificări generate în ultima oră: <strong>{$result['count']}</strong></p>";
    
    // Check queue
    $result = $db->fetchOn('notification_queue',
        "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
         FROM notification_queue
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    );
    
    echo "<p>📧 Email-uri trimise: <strong>{$result['sent']}</strong> / {$result['total']}</p>";
    echo "<p>⏳ Email-uri pending: <strong>{$result['pending']}</strong></p>";
    echo "<p>❌ Email-uri failed: <strong>{$result['failed']}</strong></p>";
    
    if ($result['sent'] > 0) {
        echo "<div class='success'>✅ Email-uri trimise cu succes!</div>";
    }
    
} catch (Throwable $e) {
    echo "<div class='error'>❌ Eroare la verificare: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<hr>";
echo "<p><a href='run_cron.php'>🔄 Rulează din nou</a> | ";
echo "<a href='scripts/test_simple_notifications.php'>📊 Vezi Statistici</a> | ";
echo "<a href='notifications'>📬 Vezi Notificări</a></p>";

echo "</body></html>";
?>
