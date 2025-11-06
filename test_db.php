<?php
// Test database connection and check data
require_once 'config/database.php';
require_once 'core/database.php';

echo "<h2>Fleet Management - Database Test</h2>";

try {
    $db = Database::getInstance();
    echo "<p style='color: green;'>✓ Conexiune reușită la baza de date!</p>";
    
    // Check vehicle_types
    $types = $db->fetchAll("SELECT * FROM vehicle_types");
    echo "<h3>Vehicle Types (" . count($types) . "):</h3>";
    if (empty($types)) {
        echo "<p style='color: orange;'>⚠ Nu există tipuri de vehicule. Rulați sql/sample_data.sql</p>";
    } else {
        echo "<ul>";
        foreach ($types as $type) {
            echo "<li>{$type['name']} (ID: {$type['id']})</li>";
        }
        echo "</ul>";
    }
    
    // Check vehicles
    $vehicles = $db->fetchAll("SELECT * FROM vehicles ORDER BY id");
    echo "<h3>Vehicles (" . count($vehicles) . "):</h3>";
    if (empty($vehicles)) {
        echo "<p style='color: orange;'>⚠ Nu există vehicule. Rulați sql/sample_data.sql</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Nr. Înmatriculare</th><th>Marca</th><th>Model</th><th>An</th><th>Status</th></tr>";
        foreach ($vehicles as $v) {
            echo "<tr>";
            echo "<td>{$v['id']}</td>";
            echo "<td><strong>{$v['registration_number']}</strong></td>";
            echo "<td>{$v['brand']}</td>";
            echo "<td>{$v['model']}</td>";
            echo "<td>{$v['year']}</td>";
            echo "<td>{$v['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<p><a href='index.php'>← Înapoi la aplicație</a> | <a href='vehicles'>Vezi lista vehicule</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Eroare: " . $e->getMessage() . "</p>";
    echo "<p>Verificați:</p>";
    echo "<ul>";
    echo "<li>Configurația din config/database.php</li>";
    echo "<li>Dacă MySQL/MariaDB rulează</li>";
    echo "<li>Dacă baza de date 'fleet_management' există</li>";
    echo "<li>Dacă tabelele au fost create (sql/schema.sql)</li>";
    echo "</ul>";
}
?>
