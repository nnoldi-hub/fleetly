<?php
/**
 * Script de diagnostic pentru rutare Service Module
 * Accesează: /index.php/test-route-debug
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'core/Database.php';
require_once 'core/Auth.php';

echo "<h1>Route Debug Info</h1>";
echo "<pre>";

echo "=== SERVER VARS ===\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "\n";
echo "QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'N/A') . "\n";

echo "\n=== CONFIG ===\n";
echo "BASE_URL: " . BASE_URL . "\n";
echo "ROUTE_BASE: " . ROUTE_BASE . "\n";

echo "\n=== PATH CALCULATION ===\n";
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
echo "requestUri (parsed): " . $requestUri . "\n";

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
echo "scriptName: " . $scriptName . "\n";

$calcBase = rtrim(dirname($scriptName), '/');
echo "calcBase (dirname scriptName): " . $calcBase . "\n";

$configBase = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/');
echo "configBase (from BASE_URL): " . $configBase . "\n";

$path = $requestUri;
echo "path (initial): " . $path . "\n";

// 1) Try with calculated base
if (!empty($calcBase) && $calcBase !== '/' && strpos($requestUri, $calcBase) === 0) {
    $path = substr($requestUri, strlen($calcBase));
    echo "path (after calcBase strip): " . $path . "\n";
}
// 2) Try with config base
elseif (!empty($configBase) && $configBase !== '/' && strpos($requestUri, $configBase) === 0) {
    $path = substr($requestUri, strlen($configBase));
    echo "path (after configBase strip): " . $path . "\n";
}

// 3) Remove /index.php prefix if present
if (strpos($path, '/index.php') === 0) {
    $path = substr($path, strlen('/index.php'));
    echo "path (after /index.php strip): " . $path . "\n";
}

// Ensure leading slash
if ($path === '' || $path[0] !== '/') { 
    $path = '/' . ltrim($path, '/'); 
    echo "path (after leading slash): " . $path . "\n";
}

// Remove double slashes
$path = preg_replace('#/+#','/',$path);
echo "path (FINAL after cleanup): " . $path . "\n";

echo "\n=== AUTH CHECK ===\n";
try {
    $auth = Auth::getInstance();
    $userId = $auth->getUserId();
    $tenantId = $auth->getTenantId();
    $companyId = $auth->effectiveCompanyId();
    echo "User ID: " . ($userId ?? 'NULL') . "\n";
    echo "Tenant ID: " . ($tenantId ?? 'NULL') . "\n";
    echo "Company ID: " . ($companyId ?? 'NULL') . "\n";
    echo "Is Authenticated: " . ($auth->isAuthenticated() ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "Auth Error: " . $e->getMessage() . "\n";
}

echo "\n=== DATABASE CHECK ===\n";
try {
    $db = Database::getInstance();
    echo "DB Connection: OK\n";
    
    // Check if tenant DB selected
    $currentDb = $db->getCurrentDatabase();
    echo "Current Database: " . ($currentDb ?? 'core') . "\n";
    
    // Try to select tenant DB
    if (isset($companyId) && $companyId) {
        $db->setTenantDatabaseByCompanyId($companyId);
        $tenantDb = $db->getCurrentDatabase();
        echo "Tenant Database (after selection): " . ($tenantDb ?? 'N/A') . "\n";
        
        // Check if services table exists
        $tables = $db->query("SHOW TABLES LIKE 'services'")->fetchAll();
        echo "Services table exists: " . (count($tables) > 0 ? 'YES' : 'NO') . "\n";
    }
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}

echo "\n=== ROUTES DEFINED ===\n";
require_once 'core/Router.php';
require_once 'core/Controller.php';
require_once 'core/Model.php';

// Load controllers
$controllerFiles = glob('modules/*/controllers/*.php');
foreach ($controllerFiles as $file) {
    require_once $file;
}

$router = new Router();
// Just add service routes for testing
$router->addRoute('GET', '/service/services', 'ServiceController', 'index');
$router->addRoute('GET', '/service/workshop', 'WorkOrderController', 'index');
$router->addRoute('GET', '/service/mechanics', 'MechanicController', 'index');

echo "Testing path: " . $path . "\n\n";

// Simulate what router does
class TestRouter {
    public static function normalizePath($p) {
        if ($p === null) return '/';
        $p = parse_url($p, PHP_URL_PATH) ?? '/';
        if ($p === '') $p = '/';
        if ($p[0] !== '/') $p = '/' . $p;
        if (strlen($p) > 1) {
            $p = rtrim($p, '/');
        }
        return $p;
    }
    
    public static function matchPath($routePath, $uri) {
        $r = self::normalizePath($routePath);
        $u = self::normalizePath($uri);
        $r = str_replace('/index.php', '', $r);
        $u = str_replace('/index.php', '', $u);
        
        echo "  Comparing: route='$r' vs uri='$u' => " . ($r === $u ? 'MATCH!' : 'no match') . "\n";
        
        return $r === $u;
    }
}

$routes = [
    '/service/services',
    '/service/workshop', 
    '/service/mechanics'
];

foreach ($routes as $route) {
    TestRouter::matchPath($route, $path);
}

echo "\n=== TEST LINKS ===\n";
echo "Mechanics (working): " . ROUTE_BASE . "service/mechanics\n";
echo "Services (not working?): " . ROUTE_BASE . "service/services\n";
echo "Workshop (not working?): " . ROUTE_BASE . "service/workshop\n";

echo "</pre>";
