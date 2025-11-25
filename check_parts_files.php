<?php
// check_parts_files.php - Diagnostic pentru verificare fișiere modul Piese

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🔍 Diagnostic Modul Piese</h2>";

$requiredFiles = [
    'modules/service/controllers/PartsController.php' => 'Controller',
    'modules/service/models/Part.php' => 'Model',
    'modules/service/views/parts/index.php' => 'View Index',
    'modules/service/views/parts/form.php' => 'View Form',
    'modules/service/views/parts/view.php' => 'View Detail',
    'config/routes.php' => 'Routes Config',
];

echo "<h3>✅ Verificare Fișiere</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Fișier</th><th>Descriere</th><th>Status</th><th>Size</th></tr>";

$allExist = true;

foreach ($requiredFiles as $file => $desc) {
    $exists = file_exists($file);
    $size = $exists ? filesize($file) : 0;
    $status = $exists ? '✅ EXISTĂ' : '❌ LIPSĂ';
    $color = $exists ? 'green' : 'red';
    
    if (!$exists) {
        $allExist = false;
    }
    
    echo "<tr>";
    echo "<td><code>$file</code></td>";
    echo "<td>$desc</td>";
    echo "<td style='color: $color; font-weight: bold;'>$status</td>";
    echo "<td>" . ($exists ? number_format($size) . ' bytes' : '-') . "</td>";
    echo "</tr>";
}

echo "</table>";

// Verificare dacă PartsController este încărcat în memorie
echo "<h3>🔍 Verificare Clasă</h3>";
echo "<ul>";

// Forțează încărcarea autoloader-ului
if (file_exists('modules/service/controllers/PartsController.php')) {
    require_once 'modules/service/controllers/PartsController.php';
    $classExists = class_exists('PartsController', false);
    echo "<li>Clasă <code>PartsController</code>: " . ($classExists ? '✅ ÎNCĂRCATĂ' : '❌ NU ESTE ÎNCĂRCATĂ') . "</li>";
    
    if ($classExists) {
        $reflection = new ReflectionClass('PartsController');
        echo "<li>Fișier sursă: <code>" . $reflection->getFileName() . "</code></li>";
        echo "<li>Metode: " . count($reflection->getMethods()) . "</li>";
    }
} else {
    echo "<li>❌ Fișierul <code>PartsController.php</code> nu există pe server!</li>";
}

echo "</ul>";

// Verificare rute
echo "<h3>🛣️ Verificare Rute</h3>";
if (file_exists('config/routes.php')) {
    $routesContent = file_get_contents('config/routes.php');
    $partsRoutesCount = substr_count($routesContent, 'PartsController');
    
    echo "<ul>";
    echo "<li>Rute PartsController găsite: <strong>$partsRoutesCount</strong></li>";
    
    if (preg_match_all('/addRoute\([^)]+PartsController[^)]+\)/', $routesContent, $matches)) {
        echo "<li>Rute detectate:</li>";
        echo "<ul>";
        foreach ($matches[0] as $route) {
            echo "<li><code>" . htmlspecialchars($route) . "</code></li>";
        }
        echo "</ul>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ Fișierul routes.php nu există!</p>";
}

// Verificare bază de date
echo "<h3>💾 Verificare Bază de Date</h3>";
echo "<ul>";

require_once 'config/database.php';
require_once 'core/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Verificare tabele
    $tables = ['service_parts', 'service_parts_usage', 'service_parts_transactions'];
    
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = $stmt->rowCount() > 0;
        
        if ($exists) {
            // Număr înregistrări
            $stmt = $conn->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<li>Tabel <code>$table</code>: ✅ EXISTĂ ($count înregistrări)</li>";
        } else {
            echo "<li>Tabel <code>$table</code>: ❌ LIPSĂ</li>";
        }
    }
} catch (Exception $e) {
    echo "<li>❌ Eroare conexiune BD: " . htmlspecialchars($e->getMessage()) . "</li>";
}

echo "</ul>";

// Concluzie
echo "<h3>📊 Concluzie</h3>";

if ($allExist) {
    echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✅ TOATE FIȘIERELE EXISTĂ!</p>";
    echo "<p>Problema 404 ar putea fi:</p>";
    echo "<ul>";
    echo "<li>Cache PHP (opcache) - încearcă <a href='/clear.php'>clear.php</a></li>";
    echo "<li>Router-ul nu se potrivește - verifică exact URL-ul accesat</li>";
    echo "<li>Permisiuni fișiere (chmod)</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red; font-weight: bold; font-size: 18px;'>❌ LIPSESC FIȘIERE!</p>";
    echo "<p>Trebuie să:</p>";
    echo "<ol>";
    echo "<li>Verifici dacă Git a tras toate fișierele pe server</li>";
    echo "<li>Re-deploy din cPanel Git Version Control</li>";
    echo "<li>Eventual urcă manual fișierele lipsă via FTP/File Manager</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><small>Generat la: " . date('Y-m-d H:i:s') . "</small></p>";
?>
