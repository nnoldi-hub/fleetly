<?php
/**
 * run-migration.php - Script pentru rularea migrației SQL pe Hostico
 * INSTRUCȚIUNI:
 * 1. Accesează https://fleetly.ro/run-migration.php în browser
 * 2. Confirmă executarea migrației
 * 3. ȘTERGE acest fișier după utilizare pentru securitate!
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Verifică dacă utilizatorul a confirmat executarea
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Run SQL Migration</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 5px; }
            .danger { background: #f8d7da; border: 1px solid #dc3545; padding: 20px; margin: 20px 0; border-radius: 5px; }
            .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px; text-decoration: none; border-radius: 5px; }
            .btn-primary { background: #007bff; color: white; }
            .btn-danger { background: #dc3545; color: white; }
            pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <h1>🚀 Service Module - SQL Migration</h1>
        
        <div class="warning">
            <h3>⚠️ Atenție!</h3>
            <p>Acest script va crea următoarele tabele în baza de date:</p>
            <ul>
                <li><strong>services</strong> - Servicii auto (parteneri + atelier intern)</li>
                <li><strong>service_appointments</strong> - Programări service</li>
                <li><strong>service_history</strong> - Istoric intervenții</li>
                <li><strong>maintenance_rules</strong> - Reguli întreținere preventivă</li>
                <li><strong>work_orders</strong> - Ordine de lucru (atelier intern)</li>
                <li><strong>service_mechanics</strong> - Mecanici atelier</li>
                <li><strong>work_order_parts</strong> - Piese utilizate</li>
                <li><strong>work_order_labor</strong> - Manoperă (tracking timp)</li>
                <li><strong>work_order_checklist</strong> - Checklist-uri verificare</li>
                <li><strong>service_notifications</strong> - Notificări service</li>
            </ul>
            <p>Plus 8 triggere automate și 2 view-uri SQL.</p>
        </div>

        <div class="danger">
            <h3>🔒 Securitate</h3>
            <p><strong>IMPORTANT:</strong> După rularea cu succes a migrației, <strong>ȘTERGE acest fișier</strong> din server pentru securitate!</p>
        </div>

        <h3>Informații Bază de Date:</h3>
        <pre><?php
        require_once 'config/database.php';
        echo "Host: " . DatabaseConfig::getHost() . "\n";
        echo "Database: " . DatabaseConfig::getDbName() . "\n";
        echo "User: " . DatabaseConfig::getUsername() . "\n";
        echo "Tenancy Mode: " . DatabaseConfig::getTenancyMode() . "\n";
        ?></pre>

        <h3>Fișier Migrare:</h3>
        <pre>sql/migrations/service_module_schema.sql (<?php echo file_exists('sql/migrations/service_module_schema.sql') ? '✓ Exists' : '✗ NOT FOUND'; ?>)</pre>

        <div style="margin-top: 30px;">
            <a href="?confirm=yes" class="btn btn-primary" onclick="return confirm('Ești sigur că vrei să execuți migrația?');">
                ✓ Da, execută migrația
            </a>
            <a href="index.php" class="btn btn-danger">✗ Anulează</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Executarea migrației
echo "<h1>🔄 Executare Migrare SQL...</h1>";
echo "<pre>";

try {
    // Încarcă configurația
    require_once 'config/database.php';
    
    // Conectare la baza de date
    $pdo = DatabaseConfig::getConnection();
    echo "✓ Conexiune la baza de date stabilită\n\n";
    
    // Citește fișierul SQL
    $sqlFile = 'sql/migrations/service_module_schema.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Fișierul SQL nu a fost găsit: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "✓ Fișier SQL încărcat (" . number_format(strlen($sql)) . " bytes)\n\n";
    
    // Împarte SQL în comenzi individuale
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            // Elimină comentariile și liniile goale
            $stmt = trim($stmt);
            return !empty($stmt) && 
                   strpos($stmt, '--') !== 0 && 
                   strpos($stmt, '/*') !== 0;
        }
    );
    
    echo "📋 Găsite " . count($statements) . " comenzi SQL de executat\n\n";
    echo str_repeat("=", 80) . "\n\n";
    
    // Execută fiecare comandă
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $pdo->exec($statement);
            $successCount++;
            
            // Afișează progresul pentru comenzi importante
            if (preg_match('/CREATE\s+TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Tabelă creată: " . $matches[1] . "\n";
            } elseif (preg_match('/CREATE\s+TRIGGER\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Trigger creat: " . $matches[1] . "\n";
            } elseif (preg_match('/CREATE\s+(?:OR\s+REPLACE\s+)?VIEW\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ View creat: " . $matches[1] . "\n";
            }
        } catch (PDOException $e) {
            $errorCount++;
            // Ignoră erorile "already exists" (dacă rulezi din nou scriptul)
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "✗ Eroare: " . $e->getMessage() . "\n";
                echo "   SQL: " . substr($statement, 0, 100) . "...\n\n";
            }
        }
    }
    
    echo "\n" . str_repeat("=", 80) . "\n\n";
    echo "<strong>✅ MIGRARE COMPLETĂ!</strong>\n\n";
    echo "Rezultate:\n";
    echo "  - Comenzi executate cu succes: $successCount\n";
    echo "  - Erori: $errorCount\n\n";
    
    // Verifică tabelele create
    echo "Verificare tabele create:\n";
    $tables = [
        'services', 'service_appointments', 'service_history', 'maintenance_rules',
        'work_orders', 'service_mechanics', 'work_order_parts', 'work_order_labor',
        'work_order_checklist', 'service_notifications'
    ];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo ($exists ? "  ✓" : "  ✗") . " $table\n";
    }
    
    echo "\n" . str_repeat("=", 80) . "\n\n";
    echo "<h2 style='color: green;'>🎉 Succes! Modulul Service este gata de utilizare!</h2>\n\n";
    echo "<p><strong>IMPORTANT:</strong> <span style='color: red;'>ȘTERGE ACUM fișierul run-migration.php pentru securitate!</span></p>\n\n";
    echo "<p>Poți accesa aplicația: <a href='index.php'>index.php</a></p>\n";
    
} catch (Exception $e) {
    echo "\n\n";
    echo "<strong style='color: red;'>❌ EROARE:</strong>\n";
    echo $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<hr>";
echo "<p><a href='index.php'>← Înapoi la aplicație</a></p>";
?>
