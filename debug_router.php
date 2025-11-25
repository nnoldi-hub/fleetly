<?php
// debug_router.php - Verifică ce se întâmplă în Router

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🐛 Debug Router - /service/parts</h2>";

// 1. Simulează exact ce face index.php când primește request pentru /service/parts
echo "<h3>📋 Test Simulare Router</h3>";

// Include toate fișierele necesare
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Model.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/User.php';
require_once __DIR__ . '/core/Company.php';

// Include PartsController
require_once __DIR__ . '/modules/service/controllers/PartsController.php';

// Creează router și adaugă DOAR ruta de test
$testRouter = new Router();
$testRouter->addRoute('GET', '/service/parts', 'PartsController', 'index');

echo "<ul>";
echo "<li>✅ Router creat</li>";
echo "<li>✅ Ruta adăugată: GET /service/parts → PartsController::index</li>";
echo "<li>✅ Clasa PartsController încărcată: " . (class_exists('PartsController') ? 'DA' : 'NU') . "</li>";
echo "</ul>";

// 2. Testează normalizarea path-ului
echo "<h3>🔧 Test Normalizare Path</h3>";

$reflection = new ReflectionClass('Router');
$normalizeMethod = $reflection->getMethod('normalizePath');
$normalizeMethod->setAccessible(true);

$testPaths = [
    '/service/parts',
    '/service/parts/',
    'service/parts',
    '/index.php/service/parts',
    '/service/parts?page=1',
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Input</th><th>Normalized</th></tr>";

$router = new Router();
foreach ($testPaths as $path) {
    $normalized = $normalizeMethod->invoke($router, $path);
    echo "<tr><td><code>$path</code></td><td><code>$normalized</code></td></tr>";
}

echo "</table>";

// 3. Testează matchPath
echo "<h3>🎯 Test Match Path</h3>";

$matchMethod = $reflection->getMethod('matchPath');
$matchMethod->setAccessible(true);

$routePath = '/service/parts';
$testUris = [
    '/service/parts',
    '/service/parts/',
    'service/parts',
    '/index.php/service/parts',
    '/service/parts?page=1',
    '/service/part',
    '/service/parts/add',
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Route Path</th><th>Test URI</th><th>Match?</th></tr>";

foreach ($testUris as $uri) {
    $match = $matchMethod->invoke($router, $routePath, $uri);
    $color = $match ? 'green' : 'red';
    $result = $match ? '✅ MATCH' : '❌ NO MATCH';
    
    echo "<tr>";
    echo "<td><code>$routePath</code></td>";
    echo "<td><code>$uri</code></td>";
    echo "<td style='color: $color; font-weight: bold;'>$result</td>";
    echo "</tr>";
}

echo "</table>";

// 4. Verifică cum apare REQUEST_URI în realitate
echo "<h3>🌐 Request URI Real</h3>";
echo "<ul>";
echo "<li>REQUEST_URI: <code>" . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "</code></li>";
echo "<li>PATH_INFO: <code>" . ($_SERVER['PATH_INFO'] ?? 'N/A') . "</code></li>";
echo "<li>SCRIPT_NAME: <code>" . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</code></li>";
echo "<li>PHP_SELF: <code>" . ($_SERVER['PHP_SELF'] ?? 'N/A') . "</code></li>";
echo "</ul>";

// 5. Citește ultimele linii din error_log dacă există
echo "<h3>📄 Error Log (ultimele 50 linii cu ROUTER)</h3>";

$logFile = ini_get('error_log');
if (!$logFile || $logFile === 'syslog') {
    // Încearcă locații comune
    $possibleLogs = [
        __DIR__ . '/error_log',
        __DIR__ . '/../error_log',
        '/home/wclsgzyf/public_html/error_log',
        '/var/log/apache2/error.log',
    ];
    
    foreach ($possibleLogs as $log) {
        if (file_exists($log)) {
            $logFile = $log;
            break;
        }
    }
}

echo "<p>Error log location: <code>$logFile</code></p>";

if ($logFile && file_exists($logFile) && is_readable($logFile)) {
    $lines = file($logFile);
    $routerLines = array_filter($lines, function($line) {
        return stripos($line, '[ROUTER]') !== false;
    });
    
    $lastLines = array_slice($routerLines, -50);
    
    if (empty($lastLines)) {
        echo "<p>⚠️ Nu s-au găsit log-uri cu [ROUTER]</p>";
    } else {
        echo "<pre style='background: #f5f5f5; padding: 15px; overflow-x: auto; font-size: 12px;'>";
        foreach ($lastLines as $line) {
            echo htmlspecialchars($line);
        }
        echo "</pre>";
    }
} else {
    echo "<p>⚠️ Error log nu poate fi citit sau nu există la locația: <code>$logFile</code></p>";
    echo "<p>Caută manual în cPanel → Errors sau în director-ul root pentru fișiere error_log</p>";
}

echo "<hr>";
echo "<p><small>Executat la: " . date('Y-m-d H:i:s') . "</small></p>";

// 6. Link-uri de test
echo "<h3>🔗 Test Link-uri</h3>";
echo "<p>Click pe link-ul de mai jos să generăm traffic și să vedem în log ce se întâmplă:</p>";
echo "<ul>";
echo "<li><a href='/service/parts' target='_blank'>🔗 Testează /service/parts</a></li>";
echo "<li><a href='/debug_router.php' target='_self'>🔄 Refresh această pagină după test</a></li>";
echo "</ul>";
?>
