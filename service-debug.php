<?php
// Debug Service Module
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Service Module Debug</h1>";

// Start session (required for Auth)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate logged in user (for testing)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['tenant_id'] = 1;
    echo "<div style='background:yellow;padding:10px;margin-bottom:20px;'>⚠️ Simulated login session created for testing</div>";
}

// Load dependencies
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Model.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/User.php';
require_once __DIR__ . '/core/Company.php';
echo "✓ Core classes loaded<br><br>";

echo "<h2>Step 1: Load Service Models</h2>";
try {
    require_once __DIR__ . '/modules/service/models/Service.php';
    echo "✓ Service.php loaded<br>";
    
    require_once __DIR__ . '/modules/service/models/WorkOrder.php';
    echo "✓ WorkOrder.php loaded<br>";
} catch (Exception $e) {
    echo "✗ Error loading models: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    die();
}

echo "<h2>Step 2: Load Controllers</h2>";
try {
    require_once __DIR__ . '/modules/service/controllers/ServiceController.php';
    echo "✓ ServiceController.php loaded<br>";
    
    require_once __DIR__ . '/modules/service/controllers/WorkOrderController.php';
    echo "✓ WorkOrderController.php loaded<br>";
} catch (Exception $e) {
    echo "✗ Error loading controllers: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    die();
}

echo "<h2>Step 3: Test WorkOrderController instantiation</h2>";
try {
    echo "Creating WorkOrderController instance...<br>";
    $controller = new WorkOrderController();
    echo "✓ WorkOrderController instantiated<br>";
} catch (Throwable $e) {
    echo "✗ Error creating controller: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    die();
}

echo "<h2>Step 4: Test dashboard method</h2>";
try {
    // This will try to call the dashboard method
    echo "Attempting to call dashboard()...<br>";
    ob_start();
    $controller->dashboard();
    $output = ob_get_clean();
    echo "✓ dashboard() executed successfully<br>";
    echo "<h3>Output preview (first 500 chars):</h3>";
    echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
} catch (Throwable $e) {
    echo "✗ Error in dashboard(): " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><p>Debug complete!</p>";
