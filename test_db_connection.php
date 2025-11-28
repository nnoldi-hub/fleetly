<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Database Connection</h1>";

try {
    echo "<p>Step 1: Loading config...</p>";
    require_once __DIR__ . '/config/config.php';
    echo "<p>✅ Config loaded</p>";
    
    echo "<p>Step 2: Loading Database class...</p>";
    require_once __DIR__ . '/core/Database.php';
    echo "<p>✅ Database class loaded</p>";
    
    echo "<p>Step 3: Getting Database instance...</p>";
    $db = Database::getInstance();
    echo "<p>✅ Database instance created</p>";
    
    echo "<p>Step 4: Testing simple query...</p>";
    $result = $db->fetch("SELECT 1 as test");
    echo "<p>✅ Query executed: " . print_r($result, true) . "</p>";
    
    echo "<h2>🎉 All OK!</h2>";
    
} catch (Throwable $e) {
    echo "<div style='background: #ffebee; padding: 20px; border-left: 4px solid red;'>";
    echo "<h2>❌ ERROR</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>
