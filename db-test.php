<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Database Test</title></head><body>";
echo "<h1>Database Connection Test</h1>";

// Step 1: Load config
echo "<h2>Step 1: Config Files</h2>";
try {
    require_once __DIR__ . '/config/config.php';
    echo "✓ config.php loaded<br>";
    echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "<br>";
    echo "FM_ENV: " . (defined('FM_ENV') ? FM_ENV : 'NOT DEFINED') . "<br><br>";
} catch (Exception $e) {
    echo "✗ Error loading config.php: " . $e->getMessage() . "<br>";
    die();
}

// Step 2: Load database config
echo "<h2>Step 2: Database Config</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    echo "✓ database.php loaded<br>";
    
    // Check if DatabaseConfig class exists
    if (class_exists('DatabaseConfig')) {
        echo "✓ DatabaseConfig class exists<br>";
        
        // Try to get config details
        try {
            $host = DatabaseConfig::getHost();
            echo "✓ Host: " . $host . "<br>";
            
            $dbName = DatabaseConfig::getDbName();
            echo "✓ Database: " . $dbName . "<br>";
            
            $user = DatabaseConfig::getUsername();
            echo "✓ Username: " . $user . "<br>";
            
            $tenancyMode = DatabaseConfig::getTenancyMode();
            echo "✓ Tenancy Mode: " . $tenancyMode . "<br>";
            
            $prefix = DatabaseConfig::getTenantDbPrefix();
            echo "✓ Tenant Prefix: " . ($prefix ?: '(none)') . "<br><br>";
            
        } catch (Exception $e) {
            echo "✗ Error getting config: " . $e->getMessage() . "<br>";
            echo "Stack: <pre>" . $e->getTraceAsString() . "</pre>";
        }
        
    } else {
        echo "✗ DatabaseConfig class NOT FOUND<br>";
    }
} catch (Exception $e) {
    echo "✗ Error loading database.php: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
    die();
}

// Step 3: Test Database class
echo "<h2>Step 3: Database Class</h2>";
try {
    require_once __DIR__ . '/core/Database.php';
    echo "✓ Database.php loaded<br>";
    
    echo "<strong>Attempting to create Database instance...</strong><br>";
    $db = new Database();
    echo "✓ Database object created<br>";
    
    echo "<strong>Attempting to get connection...</strong><br>";
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "✓ <strong style='color:green'>DATABASE CONNECTED SUCCESSFULLY!</strong><br>";
        echo "Connection type: " . get_class($conn) . "<br>";
        
        // Test query
        echo "<h3>Test Query</h3>";
        $stmt = $conn->query("SELECT DATABASE() as current_db, NOW() as current_time");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Current Database: " . $result['current_db'] . "<br>";
        echo "Server Time: " . $result['current_time'] . "<br>";
        
    } else {
        echo "✗ <strong style='color:red'>Connection returned NULL</strong><br>";
    }
    
} catch (PDOException $e) {
    echo "✗ <strong style='color:red'>PDO ERROR:</strong><br>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "Code: " . $e->getCode() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
} catch (Exception $e) {
    echo "✗ <strong style='color:red'>GENERAL ERROR:</strong><br>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";
