<?php
// test-simple.php - Test rapid pentru identificare eroare
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "<h2>Test Basic</h2>";

// 1. Test config
try {
    require_once 'config/config.php';
    echo "✓ Config loaded<br>";
} catch (Exception $e) {
    die("✗ Config error: " . $e->getMessage());
}

// 2. Test database
try {
    require_once 'config/database.php';
    $db = DatabaseConfig::getConnection();
    echo "✓ Database connected<br>";
} catch (Exception $e) {
    die("✗ Database error: " . $e->getMessage());
}

// 3. Test core classes
$coreFiles = ['Database', 'Model', 'Controller', 'Router', 'Auth', 'User', 'Company'];
foreach ($coreFiles as $class) {
    try {
        require_once "core/$class.php";
        echo "✓ core/$class.php loaded<br>";
    } catch (Exception $e) {
        die("✗ Error loading core/$class.php: " . $e->getMessage());
    }
}

// 4. Test autoload models
echo "<h3>Loading Models:</h3>";
$modelFiles = glob('modules/*/models/*.php');
foreach ($modelFiles as $file) {
    try {
        require_once $file;
        echo "✓ " . basename($file) . "<br>";
    } catch (Exception $e) {
        die("✗ ERROR loading $file: " . $e->getMessage() . "<br><pre>" . $e->getTraceAsString() . "</pre>");
    }
}

// 5. Test autoload controllers
echo "<h3>Loading Controllers:</h3>";
$controllerFiles = glob('modules/*/controllers/*.php');
foreach ($controllerFiles as $file) {
    try {
        require_once $file;
        echo "✓ " . basename($file) . "<br>";
    } catch (Exception $e) {
        die("✗ ERROR loading $file: " . $e->getMessage() . "<br><pre>" . $e->getTraceAsString() . "</pre>");
    }
}

echo "<hr>";
echo "<h2 style='color:green;'>✓ All tests passed! Application should work.</h2>";
echo "<p>Try accessing: <a href='index.php'>index.php</a></p>";
