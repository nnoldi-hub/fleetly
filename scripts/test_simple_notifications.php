<?php
/**
 * Simple Test - Debug notification system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Simple Notification Test</h1>";
echo "<p>Data/Ora: " . date('Y-m-d H:i:s') . "</p><hr>";

// Test 1: Include config
echo "<h2>Test 1: Config</h2>";
try {
    require_once __DIR__ . '/../config/config.php';
    echo "✅ Config loaded<br>";
    echo "ROOT_PATH: " . (defined('ROOT_PATH') ? ROOT_PATH : 'NOT DEFINED') . "<br>";
    echo "ROUTE_BASE: " . (defined('ROUTE_BASE') ? ROUTE_BASE : 'NOT DEFINED') . "<br>";
} catch (Exception $e) {
    die("❌ Config error: " . $e->getMessage());
}

// Test 2: Database
echo "<h2>Test 2: Database</h2>";
try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../core/Database.php';
    $db = Database::getInstance();
    echo "✅ Database connected<br>";
} catch (Exception $e) {
    die("❌ Database error: " . $e->getMessage());
}

// Test 3: Query documents
echo "<h2>Test 3: Documents Query</h2>";
try {
    $sql = "SELECT COUNT(*) as count FROM documents";
    $result = $db->fetchOn('documents', $sql);
    echo "✅ Documents in DB: " . $result['count'] . "<br>";
    
    // Query expiring documents
    $sql = "SELECT COUNT(*) as count 
            FROM documents 
            WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            AND status = 'active'";
    $result = $db->fetchOn('documents', $sql);
    echo "✅ Expiring documents (30 days): " . $result['count'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Query error: " . $e->getMessage() . "<br>";
}

// Test 4: Notifications
echo "<h2>Test 4: Notifications</h2>";
try {
    $sql = "SELECT COUNT(*) as count FROM notifications";
    $result = $db->fetchOn('notifications', $sql);
    echo "✅ Notifications in DB: " . $result['count'] . "<br>";
    
    $sql = "SELECT COUNT(*) as count 
            FROM notifications 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $result = $db->fetchOn('notifications', $sql);
    echo "✅ Notifications (last 7 days): " . $result['count'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Query error: " . $e->getMessage() . "<br>";
}

// Test 5: Preferences
echo "<h2>Test 5: Notification Preferences</h2>";
try {
    $sql = "SELECT COUNT(*) as count FROM notification_preferences";
    $result = $db->fetchOn('notification_preferences', $sql);
    echo "✅ Users with preferences: " . $result['count'] . "<br>";
    
    if ($result['count'] > 0) {
        $sql = "SELECT user_id, email, email_enabled, sms_enabled, enabled_types 
                FROM notification_preferences LIMIT 3";
        $prefs = $db->fetchAllOn('notification_preferences', $sql);
        echo "<pre>";
        print_r($prefs);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "❌ Query error: " . $e->getMessage() . "<br>";
}

// Test 6: Queue
echo "<h2>Test 6: Notification Queue</h2>";
try {
    $sql = "SELECT COUNT(*) as count FROM notification_queue";
    $result = $db->fetchOn('notification_queue', $sql);
    echo "✅ Queue total: " . $result['count'] . "<br>";
    
    $sql = "SELECT COUNT(*) as count 
            FROM notification_queue 
            WHERE status = 'pending'";
    $result = $db->fetchOn('notification_queue', $sql);
    echo "✅ Queue pending: " . $result['count'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Query error: " . $e->getMessage() . "<br>";
}

echo "<hr><h2>✅ All tests completed!</h2>";
?>
