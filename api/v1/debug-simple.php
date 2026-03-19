<?php
/**
 * Simple debug test - no dependencies
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== SIMPLE DEBUG TEST ===\n\n";

echo "1. PHP Version: " . phpversion() . "\n";
echo "2. Script path: " . __FILE__ . "\n";
echo "3. Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "\n\n";

// Check paths
$appRoot = dirname(dirname(__DIR__));
echo "4. App root: " . $appRoot . "\n";

// Check vendor
$vendorPath = $appRoot . '/vendor/autoload.php';
echo "5. Vendor path: " . $vendorPath . "\n";
echo "   Exists: " . (file_exists($vendorPath) ? 'YES' : 'NO') . "\n\n";

// Check config
$configPath = $appRoot . '/config/database.php';
echo "6. Config path: " . $configPath . "\n";
echo "   Exists: " . (file_exists($configPath) ? 'YES' : 'NO') . "\n\n";

// Check override
$overridePath = $appRoot . '/config/database.override.php';
echo "7. Override path: " . $overridePath . "\n";
echo "   Exists: " . (file_exists($overridePath) ? 'YES' : 'NO') . "\n";

if (file_exists($overridePath)) {
    echo "   Content preview:\n";
    $content = file_get_contents($overridePath);
    // Show first 500 chars, hide password
    $preview = substr($content, 0, 500);
    $preview = preg_replace("/'pass'\s*=>\s*'[^']*'/", "'pass' => '***HIDDEN***'", $preview);
    echo $preview . "\n\n";
}

// Try to load config
echo "8. Trying to load database.php...\n";
try {
    require_once $configPath;
    echo "   Loaded successfully!\n";
    
    if (class_exists('DatabaseConfig')) {
        echo "   DatabaseConfig class exists!\n";
        echo "   Host: " . DatabaseConfig::getHost() . "\n";
        echo "   DB: " . DatabaseConfig::getDbName() . "\n";
        echo "   User: " . DatabaseConfig::getUsername() . "\n";
    } else {
        echo "   DatabaseConfig class NOT found!\n";
    }
} catch (Throwable $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n9. Trying vendor autoload...\n";
if (file_exists($vendorPath)) {
    try {
        require_once $vendorPath;
        echo "   Loaded successfully!\n";
        
        if (class_exists('Firebase\\JWT\\JWT')) {
            echo "   Firebase JWT: Available!\n";
        } else {
            echo "   Firebase JWT: NOT FOUND!\n";
        }
    } catch (Throwable $e) {
        echo "   ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "   Vendor autoload not found!\n";
}

echo "\n10. Trying database connection...\n";
if (class_exists('DatabaseConfig')) {
    try {
        $host = DatabaseConfig::getHost();
        $db = DatabaseConfig::getDbName();
        $user = DatabaseConfig::getUsername();
        $pass = DatabaseConfig::getPassword();
        
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "   Connected to $db @ $host\n";
    } catch (PDOException $e) {
        echo "   DB ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n=== END DEBUG ===\n";
