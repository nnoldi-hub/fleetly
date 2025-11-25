<?php
// clear_all.php - Curățare completă cache PHP

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🧹 Curățare Cache Completă</h2>";

echo "<h3>Rezultate:</h3>";
echo "<ul>";

// 1. Opcache reset
if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    echo "<li>OPcache reset: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "</li>";
} else {
    echo "<li>OPcache: ⚠️ Nu este disponibil</li>";
}

// 2. Opcache invalidate pentru fișiere critice
$criticalFiles = [
    __DIR__ . '/config/routes.php',
    __DIR__ . '/modules/service/controllers/PartsController.php',
    __DIR__ . '/core/Router.php',
    __DIR__ . '/index.php',
];

if (function_exists('opcache_invalidate')) {
    foreach ($criticalFiles as $file) {
        if (file_exists($file)) {
            opcache_invalidate($file, true);
            echo "<li>Invalidat cache: <code>" . basename($file) . "</code> ✅</li>";
        }
    }
}

// 3. Clearstatcache - curăță cache-ul PHP pentru stat-uri fișiere
clearstatcache(true);
echo "<li>Clearstatcache: ✅ SUCCESS</li>";

// 4. APC cache (dacă există)
if (function_exists('apc_clear_cache')) {
    apc_clear_cache('user');
    apc_clear_cache('opcode');
    echo "<li>APC cache cleared: ✅ SUCCESS</li>";
}

// 5. APCu cache (dacă există)
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "<li>APCu cache cleared: ✅ SUCCESS</li>";
}

// 6. Restart PHP-FPM (prin touch .user.ini)
$userIni = __DIR__ . '/.user.ini';
if (file_exists($userIni)) {
    touch($userIni);
    echo "<li>PHP-FPM restart trigger: ✅ Touched .user.ini</li>";
}

// 7. Test încărcare PartsController
echo "</ul>";
echo "<h3>🔍 Test PartsController:</h3>";
echo "<ul>";

require_once __DIR__ . '/modules/service/controllers/PartsController.php';

if (class_exists('PartsController')) {
    echo "<li>✅ Clasa PartsController există și este încărcată!</li>";
    
    $reflection = new ReflectionClass('PartsController');
    echo "<li>📁 Fișier: <code>" . $reflection->getFileName() . "</code></li>";
    echo "<li>📊 Metode: " . count($reflection->getMethods()) . "</li>";
    
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    echo "<li>🔧 Metode publice:</li>";
    echo "<ul>";
    foreach ($methods as $method) {
        if ($method->class === 'PartsController') {
            echo "<li><code>" . $method->name . "()</code></li>";
        }
    }
    echo "</ul>";
} else {
    echo "<li>❌ Clasa PartsController NU există!</li>";
}

echo "</ul>";

echo "<h3>✅ Gata!</h3>";
echo "<p><strong>Acum încearcă să accesezi:</strong></p>";
echo "<ul>";
echo "<li><a href='/service/parts' target='_blank'>📦 /service/parts</a></li>";
echo "<li><a href='/service/parts/add' target='_blank'>➕ /service/parts/add</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><small>Executat la: " . date('Y-m-d H:i:s') . "</small></p>";
?>
