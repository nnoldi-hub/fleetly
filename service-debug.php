<?php
// Debug Service Module
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Service Module Debug</h1>";

// Load dependencies
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Model.php';
require_once __DIR__ . '/core/Controller.php';

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
    $controller = new WorkOrderController();
    echo "✓ WorkOrderController instantiated<br>";
} catch (Exception $e) {
    echo "✗ Error creating controller: " . $e->getMessage() . "<br>";
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
} catch (Exception $e) {
    echo "✗ Error in dashboard(): " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><p>Debug complete!</p>";
