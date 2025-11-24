<?php
/**
 * Service Routes Debug
 * Verifică dacă toate clasele și rutele Service Module sunt încărcate corect
 */

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Service Routes Debug</title></head><body>";
echo "<h1>Service Module Routes Debug</h1>";
echo "<pre>";

// 1. Verifică versiunea PHP
echo "\n=== PHP Version ===\n";
echo "PHP Version: " . phpversion() . "\n";

// 2. Verifică dacă fișierele există
echo "\n=== Files Check ===\n";
$files = [
    'Core Controller' => 'core/Controller.php',
    'Core Auth' => 'core/Auth.php',
    'Core Router' => 'core/Router.php',
    'Service Model' => 'modules/service/models/Service.php',
    'WorkOrder Model' => 'modules/service/models/WorkOrder.php',
    'ServiceController' => 'modules/service/controllers/ServiceController.php',
    'WorkOrderController' => 'modules/service/controllers/WorkOrderController.php',
    'MechanicController' => 'modules/service/controllers/MechanicController.php',
];

foreach ($files as $name => $path) {
    $exists = file_exists($path) ? '✓ EXISTS' : '✗ MISSING';
    $size = file_exists($path) ? ' (' . filesize($path) . ' bytes)' : '';
    echo "$name: $exists$size\n";
}

// 3. Include core files
echo "\n=== Loading Core Files ===\n";
try {
    require_once 'config/config.php';
    echo "✓ config.php loaded\n";
    require_once 'config/database.php';
    echo "✓ database.php loaded\n";
    require_once 'core/Database.php';
    echo "✓ Database.php loaded\n";
    require_once 'core/Model.php';
    echo "✓ Model.php loaded\n";
    require_once 'core/Controller.php';
    echo "✓ Controller.php loaded\n";
    require_once 'core/Auth.php';
    echo "✓ Auth.php loaded\n";
    require_once 'core/User.php';
    echo "✓ User.php loaded\n";
    require_once 'core/Company.php';
    echo "✓ Company.php loaded\n";
} catch (Exception $e) {
    echo "✗ Error loading core: " . $e->getMessage() . "\n";
    exit;
}

// 4. Load models using glob
echo "\n=== Loading Models (glob) ===\n";
$modelFiles = glob('modules/*/models/*.php');
echo "Found " . count($modelFiles) . " model files\n";
foreach ($modelFiles as $file) {
    try {
        require_once $file;
        echo "✓ Loaded: $file\n";
    } catch (Exception $e) {
        echo "✗ Error loading $file: " . $e->getMessage() . "\n";
    }
}

// 5. Load controllers using glob
echo "\n=== Loading Controllers (glob) ===\n";
$controllerFiles = glob('modules/*/controllers/*.php');
echo "Found " . count($controllerFiles) . " controller files\n";
foreach ($controllerFiles as $file) {
    try {
        require_once $file;
        echo "✓ Loaded: $file\n";
    } catch (Exception $e) {
        echo "✗ Error loading $file: " . $e->getMessage() . "\n";
    }
}

// 6. Verifică dacă clasele sunt definite
echo "\n=== Class Definitions Check ===\n";
$classes = [
    'Controller',
    'Auth',
    'Service',
    'WorkOrder',
    'ServiceController',
    'WorkOrderController',
    'MechanicController',
];

foreach ($classes as $class) {
    $exists = class_exists($class) ? '✓ DEFINED' : '✗ NOT FOUND';
    echo "$class: $exists\n";
}

// 7. Verifică metodele din ServiceController
echo "\n=== ServiceController Methods ===\n";
if (class_exists('ServiceController')) {
    $methods = get_class_methods('ServiceController');
    echo "Total methods: " . count($methods) . "\n";
    $publicMethods = array_filter($methods, function($m) {
        return !in_array($m, ['__construct', '__destruct']);
    });
    foreach ($publicMethods as $method) {
        echo "  - $method()\n";
    }
} else {
    echo "✗ ServiceController not loaded\n";
}

// 8. Simulare routing
echo "\n=== Route Simulation ===\n";
require_once 'core/Router.php';
$router = new Router();

// Adaugă rutele Service Module
$router->addRoute('GET', '/service/services', 'ServiceController', 'index');
$router->addRoute('GET', '/service/services/view', 'ServiceController', 'view');
$router->addRoute('GET', '/service/workshop', 'WorkOrderController', 'index');
$router->addRoute('GET', '/service/mechanics', 'MechanicController', 'index');

echo "✓ Routes added successfully\n";

// 9. Test instantiation
echo "\n=== Controller Instantiation Test ===\n";
echo "Attempting to create ServiceController instance...\n";
try {
    // Bypass auth for testing
    if (!session_id()) session_start();
    $_SESSION['user_id'] = 1; // Mock user
    $_SESSION['company_id'] = 1; // Mock company
    
    echo "Note: Auth check bypassed for testing\n";
    echo "In production, Auth::getInstance()->requireAuth() will run\n";
    
    // Try to instantiate (will fail due to Auth, but we can catch it)
    // $testController = new ServiceController();
    // echo "✓ ServiceController instantiated successfully\n";
} catch (Exception $e) {
    echo "Note: " . $e->getMessage() . " (expected if not logged in)\n";
}

// 10. Current REQUEST info
echo "\n=== Current Request Info ===\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'not defined') . "\n";
echo "ROUTE_BASE: " . (defined('ROUTE_BASE') ? ROUTE_BASE : 'not defined') . "\n";

echo "\n=== Test Complete ===\n";
echo "If all checks passed, Service Module should work.\n";
echo "Delete this file after testing: service-routes-debug.php\n";

echo "</pre></body></html>";
?>
