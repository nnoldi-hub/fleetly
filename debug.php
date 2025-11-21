<?php
// debug.php - Fișier temporar pentru debugging pe Hostico
// ȘTERGE ACEST FIȘIER după ce ai rezolvat problema!

// Activează afișarea tuturor erorilor
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "<h2>Fleet Management - Debug Info</h2>";
echo "<hr>";

// 1. Verifică versiunea PHP
echo "<h3>1. PHP Version:</h3>";
echo phpversion();
echo "<br><br>";

// 2. Verifică extensiile PHP necesare
echo "<h3>2. Required PHP Extensions:</h3>";
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✓ Loaded' : '✗ NOT LOADED';
    echo "$ext: $status<br>";
}
echo "<br>";

// 3. Verifică fișierele de configurare
echo "<h3>3. Config Files Check:</h3>";
$config_files = [
    'config/config.php',
    'config/database.php',
    'config/database.override.php',
    'config/routes.php'
];
foreach ($config_files as $file) {
    $exists = file_exists($file) ? '✓ Exists' : '✗ MISSING';
    $readable = is_readable($file) ? ' (readable)' : ' (NOT READABLE)';
    echo "$file: $exists" . ($exists === '✓ Exists' ? $readable : '') . "<br>";
}
echo "<br>";

// 4. Testează încărcarea configurației
echo "<h3>4. Config Loading Test:</h3>";
try {
    require_once 'config/config.php';
    echo "✓ config/config.php loaded successfully<br>";
    echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "<br>";
    echo "APP_NAME: " . (defined('APP_NAME') ? APP_NAME : 'NOT DEFINED') . "<br>";
} catch (Exception $e) {
    echo "✗ ERROR loading config/config.php: " . $e->getMessage() . "<br>";
}
echo "<br>";

// 5. Testează clasa DatabaseConfig
echo "<h3>5. Database Config Test:</h3>";
try {
    require_once 'config/database.php';
    echo "✓ config/database.php loaded successfully<br>";
    echo "DB Host: " . DatabaseConfig::getHost() . "<br>";
    echo "DB Name: " . DatabaseConfig::getDbName() . "<br>";
    echo "DB User: " . DatabaseConfig::getUsername() . "<br>";
    echo "DB Pass: " . (DatabaseConfig::getPassword() ? '***SET***' : 'EMPTY') . "<br>";
    echo "Tenancy Mode: " . DatabaseConfig::getTenancyMode() . "<br>";
} catch (Exception $e) {
    echo "✗ ERROR loading database config: " . $e->getMessage() . "<br>";
}
echo "<br>";

// 6. Testează conexiunea la DB
echo "<h3>6. Database Connection Test:</h3>";
try {
    $pdo = DatabaseConfig::getConnection();
    echo "✓ Database connection successful!<br>";
    
    // Verifică dacă tabelele există
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($tables) . " tables in database<br>";
    if (count($tables) > 0) {
        echo "Sample tables: " . implode(', ', array_slice($tables, 0, 5)) . "...<br>";
    }
} catch (Exception $e) {
    echo "✗ ERROR connecting to database: " . $e->getMessage() . "<br>";
}
echo "<br>";

// 7. Verifică directoarele și permisiunile
echo "<h3>7. Directory Permissions:</h3>";
$dirs = [
    'uploads' => 'uploads/',
    'logs' => 'logs/',
    'modules' => 'modules/',
    'core' => 'core/'
];
foreach ($dirs as $name => $path) {
    if (is_dir($path)) {
        $writable = is_writable($path) ? ' (writable)' : ' (NOT WRITABLE)';
        echo "$name: ✓ Exists" . $writable . "<br>";
    } else {
        echo "$name: ✗ MISSING<br>";
    }
}
echo "<br>";

// 8. Testează încărcarea claselor core
echo "<h3>8. Core Classes Loading Test:</h3>";
$core_files = [
    'core/Database.php',
    'core/Model.php',
    'core/Controller.php',
    'core/Router.php',
    'core/Auth.php'
];
foreach ($core_files as $file) {
    try {
        if (file_exists($file)) {
            require_once $file;
            echo "✓ $file loaded<br>";
        } else {
            echo "✗ $file MISSING<br>";
        }
    } catch (Exception $e) {
        echo "✗ ERROR loading $file: " . $e->getMessage() . "<br>";
    }
}
echo "<br>";

// 9. Listează toate fișierele din modules/service
echo "<h3>9. Service Module Files:</h3>";
if (is_dir('modules/service')) {
    echo "✓ modules/service/ exists<br>";
    
    // Verifică controllerele
    if (is_dir('modules/service/controllers')) {
        $controllers = glob('modules/service/controllers/*.php');
        echo "Controllers: " . count($controllers) . " found<br>";
        foreach ($controllers as $c) {
            echo "  - " . basename($c) . "<br>";
        }
    } else {
        echo "✗ modules/service/controllers/ MISSING<br>";
    }
    
    // Verifică modelele
    if (is_dir('modules/service/models')) {
        $models = glob('modules/service/models/*.php');
        echo "Models: " . count($models) . " found<br>";
        foreach ($models as $m) {
            echo "  - " . basename($m) . "<br>";
        }
    } else {
        echo "✗ modules/service/models/ MISSING<br>";
    }
} else {
    echo "✗ modules/service/ MISSING<br>";
}
echo "<br>";

// 10. Verifică ultimele modificări Git
echo "<h3>10. Git Status:</h3>";
if (file_exists('.git')) {
    echo "✓ Git repository detected<br>";
    $lastCommit = shell_exec('git log -1 --oneline 2>&1');
    echo "Last commit: " . htmlspecialchars($lastCommit ?? 'Unable to read') . "<br>";
} else {
    echo "ℹ Not a git repository<br>";
}

echo "<hr>";
echo "<p><strong>Debug complete! Share this info to identify the issue.</strong></p>";
echo "<p style='color:red;'><strong>IMPORTANT: DELETE debug.php after fixing the issue for security!</strong></p>";
