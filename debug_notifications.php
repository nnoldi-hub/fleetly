<?php
// debug_notifications.php - Diagnosticare detaliată pentru notificări
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/debug_notifications.log');

echo "<h1>Diagnosticare Notificări</h1>";
echo "<pre>";

// 1. Test încărcare config
echo "=== STEP 1: Încărcare Config ===\n";
try {
    require_once 'config/config.php';
    echo "✓ Config încărcat\n";
    echo "BASE_URL: " . BASE_URL . "\n";
    echo "ROUTE_BASE: " . ROUTE_BASE . "\n";
} catch (Throwable $e) {
    echo "✗ Eroare config: " . $e->getMessage() . "\n";
    die();
}

// 2. Test încărcare core classes
echo "\n=== STEP 2: Încărcare Core Classes ===\n";
try {
    require_once 'core/Database.php';
    echo "✓ Database.php\n";
    require_once 'core/Model.php';
    echo "✓ Model.php\n";
    require_once 'core/Controller.php';
    echo "✓ Controller.php\n";
    require_once 'core/Auth.php';
    echo "✓ Auth.php\n";
    require_once 'core/User.php';
    echo "✓ User.php\n";
} catch (Throwable $e) {
    echo "✗ Eroare core: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    die();
}

// 3. Test încărcare Notification model
echo "\n=== STEP 3: Încărcare Notification Model ===\n";
try {
    require_once 'modules/notifications/models/Notification.php';
    echo "✓ Notification.php\n";
    
    $notifModel = new Notification();
    echo "✓ Instanță Notification creată\n";
} catch (Throwable $e) {
    echo "✗ Eroare model: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    die();
}

// 4. Test încărcare NotificationController
echo "\n=== STEP 4: Încărcare NotificationController ===\n";
try {
    require_once 'modules/notifications/controllers/NotificationController.php';
    echo "✓ NotificationController.php\n";
    
    $controller = new NotificationController();
    echo "✓ Instanță NotificationController creată\n";
} catch (Throwable $e) {
    echo "✗ Eroare controller: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    echo "\nLine: " . $e->getLine() . "\n";
    echo "File: " . $e->getFile() . "\n";
    die();
}

// 5. Test metodă alerts
echo "\n=== STEP 5: Test Metodă alerts() ===\n";
try {
    // Simulăm sesiune
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
    $_SESSION['company_id'] = $_SESSION['company_id'] ?? 1;
    
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "Company ID: " . $_SESSION['company_id'] . "\n";
    
    // Capturăm output
    ob_start();
    $controller->alerts();
    $output = ob_get_clean();
    
    echo "✓ Metodă alerts() executată fără erori\n";
    echo "Output length: " . strlen($output) . " bytes\n";
    
    // Verificăm dacă conține HTML valid
    if (stripos($output, '<!DOCTYPE') !== false || stripos($output, '<html') !== false) {
        echo "✓ Output conține HTML\n";
    } else {
        echo "⚠ Output nu pare HTML complet\n";
    }
    
} catch (Throwable $e) {
    echo "✗ Eroare alerts(): " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    echo "\nLine: " . $e->getLine() . "\n";
    echo "File: " . $e->getFile() . "\n";
}

// 6. Test database connection
echo "\n=== STEP 6: Test Database Connection ===\n";
try {
    $db = Database::getInstance();
    echo "✓ Database instance created\n";
    
    // Test query
    $result = $db->fetch("SELECT 1 as test");
    echo "✓ Test query successful\n";
    
    // Check notifications table
    $result = $db->fetch("SHOW TABLES LIKE 'notifications'");
    if ($result) {
        echo "✓ Tabela 'notifications' există\n";
        
        // Check columns
        $columns = $db->fetchAll("SHOW COLUMNS FROM notifications");
        echo "Coloane (" . count($columns) . "): ";
        echo implode(', ', array_column($columns, 'Field')) . "\n";
    } else {
        echo "✗ Tabela 'notifications' lipsește!\n";
    }
} catch (Throwable $e) {
    echo "✗ Eroare database: " . $e->getMessage() . "\n";
}

// 7. Test router path parsing
echo "\n=== STEP 7: Test Router Path Parsing ===\n";
$testUris = [
    '/index.php/notifications',
    '/notifications',
    '/notifications/alerts',
];

foreach ($testUris as $uri) {
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    
    // Simulăm logica din index.php
    $requestUri = parse_url($uri, PHP_URL_PATH) ?? '/';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $calcBase = rtrim(dirname($scriptName), '/');
    
    $path = $requestUri;
    if (!empty($calcBase) && $calcBase !== '/' && strpos($requestUri, $calcBase) === 0) {
        $path = substr($requestUri, strlen($calcBase));
    }
    if (strpos($path, '/index.php') === 0) {
        $path = substr($path, strlen('/index.php'));
    }
    if ($path === '' || $path[0] !== '/') { $path = '/' . ltrim($path, '/'); }
    
    echo "URI: $uri → Path: $path\n";
}

echo "\n=== DIAGNOSTICARE COMPLETĂ ===\n";
echo "Verifică log-ul: logs/debug_notifications.log\n";
echo "</pre>";
?>
