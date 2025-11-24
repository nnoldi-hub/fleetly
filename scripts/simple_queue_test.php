<?php
/**
 * SIMPLE QUEUE TEST - No dependencies
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Direct database connection
$host = 'localhost';
$dbname = 'wclsgyf_fleetly';
$username = 'wclsgyf_fleetly';
$password = 'fleety2024!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>✓ Database Connected</h1>";
    echo "<p>Database: $dbname</p>";
    
    // Check queue
    echo "<h2>Queue Items:</h2>";
    $stmt = $pdo->query("SELECT * FROM notification_queue ORDER BY created_at DESC LIMIT 5");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($items) . " items</p>";
    echo "<pre>" . print_r($items, true) . "</pre>";
    
    // Check pending query
    echo "<h2>Pending Items Query:</h2>";
    $sql = "SELECT * FROM notification_queue 
            WHERE status = 'pending' 
              AND (scheduled_at IS NULL OR scheduled_at <= NOW())
              AND attempts < max_attempts
            ORDER BY created_at ASC LIMIT 10";
    
    echo "<pre>$sql</pre>";
    
    $stmt = $pdo->query($sql);
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($pending) . " pending items</p>";
    echo "<pre>" . print_r($pending, true) . "</pre>";
    
    if (count($pending) > 0) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0;'>";
        echo "<strong>✓ Found items to process!</strong>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0;'>";
        echo "<strong>✗ No pending items found. Check:</strong><br>";
        echo "- status = 'pending'<br>";
        echo "- attempts (" . ($items[0]['attempts'] ?? 'N/A') . ") < max_attempts (" . ($items[0]['max_attempts'] ?? 'N/A') . ")<br>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<h1 style='color: red;'>✗ Database Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
