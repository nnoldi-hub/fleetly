<?php
// check_routes.php - Verifică rutele efectiv încărcate

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🛣️ Verificare Rute Încărcate</h2>";

// Include fișierele necesare
require_once __DIR__ . '/core/Router.php';

// Creează router
$router = new Router();

// Include fișierul de rute
require __DIR__ . '/config/routes.php';

// Folosește reflection pentru a accesa array-ul privat $routes
$reflection = new ReflectionClass($router);
$routesProperty = $reflection->getProperty('routes');
$routesProperty->setAccessible(true);
$routes = $routesProperty->getValue($router);

echo "<h3>📊 Total rute încărcate: <strong>" . count($routes) . "</strong></h3>";

// Caută rute PartsController
$partsRoutes = array_filter($routes, function($route) {
    return $route['controller'] === 'PartsController';
});

echo "<h3>🔍 Rute PartsController găsite: <strong>" . count($partsRoutes) . "</strong></h3>";

if (empty($partsRoutes)) {
    echo "<div style='background: #ffebee; color: #c62828; padding: 20px; margin: 20px 0; border-left: 5px solid #c62828;'>";
    echo "<h4>❌ NU EXISTĂ RUTE PartsController!</h4>";
    echo "<p>Rutele PartsController nu au fost încărcate din config/routes.php</p>";
    echo "</div>";
} else {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>#</th><th>Method</th><th>Path</th><th>Controller</th><th>Action</th></tr>";
    
    $i = 1;
    foreach ($partsRoutes as $route) {
        echo "<tr>";
        echo "<td>$i</td>";
        echo "<td><code>{$route['method']}</code></td>";
        echo "<td><code>{$route['path']}</code></td>";
        echo "<td><code>{$route['controller']}</code></td>";
        echo "<td><code>{$route['action']}</code></td>";
        echo "</tr>";
        $i++;
    }
    
    echo "</table>";
}

// Arată ultimele 10 rute încărcate
echo "<h3>📋 Ultimele 20 rute încărcate (pentru debug):</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
echo "<tr><th>#</th><th>Method</th><th>Path</th><th>Controller</th></tr>";

$lastRoutes = array_slice($routes, -20);
$start = count($routes) - 20;

foreach ($lastRoutes as $idx => $route) {
    $num = $start + $idx + 1;
    $highlight = ($route['controller'] === 'PartsController') ? 'background: #c8e6c9;' : '';
    
    echo "<tr style='$highlight'>";
    echo "<td>$num</td>";
    echo "<td><code>{$route['method']}</code></td>";
    echo "<td><code>{$route['path']}</code></td>";
    echo "<td><code>{$route['controller']}</code></td>";
    echo "</tr>";
}

echo "</table>";

// Verifică conținutul fișierului routes.php
echo "<h3>📄 Verificare Fișier routes.php</h3>";

$routesFile = __DIR__ . '/config/routes.php';
$routesContent = file_get_contents($routesFile);

echo "<ul>";
echo "<li>Mărime fișier: <strong>" . number_format(strlen($routesContent)) . " bytes</strong></li>";
echo "<li>Linii: <strong>" . count(file($routesFile)) . "</strong></li>";

$partsCount = substr_count($routesContent, 'PartsController');
echo "<li>Apariții 'PartsController': <strong>$partsCount</strong></li>";

// Verifică encoding
$encoding = mb_detect_encoding($routesContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
echo "<li>Encoding: <strong>" . ($encoding ?: 'Unknown') . "</strong></li>";

// Caută caractere problematice
$hasWeirdChars = preg_match('/[^\x20-\x7E\r\n\t]/u', $routesContent);
if ($hasWeirdChars) {
    echo "<li style='color: red;'>⚠️ <strong>ATENȚIE: Conține caractere non-ASCII problematice!</strong></li>";
}

// Arată ultimele 30 linii din routes.php
$lines = file($routesFile);
$lastLines = array_slice($lines, -30);

echo "</ul>";

echo "<h4>📝 Ultimele 30 linii din config/routes.php:</h4>";
echo "<pre style='background: #f5f5f5; padding: 15px; overflow-x: auto; border: 1px solid #ddd;'>";
echo "<code>";

foreach ($lastLines as $idx => $line) {
    $lineNum = count($lines) - 30 + $idx + 1;
    $line = rtrim($line);
    
    // Highlight PartsController
    if (strpos($line, 'PartsController') !== false) {
        echo "<strong style='background: yellow;'>";
    }
    
    printf("%3d: %s\n", $lineNum, htmlspecialchars($line));
    
    if (strpos($line, 'PartsController') !== false) {
        echo "</strong>";
    }
}

echo "</code>";
echo "</pre>";

echo "<hr>";
echo "<p><small>Executat la: " . date('Y-m-d H:i:s') . "</small></p>";
?>
