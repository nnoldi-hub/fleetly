<?php
/**
 * Script de testare configurare cron jobs
 * Verifică toate cerințele și rulează teste pentru ambele joburi
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST CONFIGURARE CRON JOBS ===\n\n";

// 1. Verifică PHP CLI
echo "1. Verificare PHP CLI:\n";
echo "   - PHP Version: " . PHP_VERSION . "\n";
echo "   - SAPI: " . PHP_SAPI . "\n";
echo "   - Memory Limit: " . ini_get('memory_limit') . "\n";
echo "   - Max Execution Time: " . ini_get('max_execution_time') . "s\n\n";

// 2. Verifică paths
echo "2. Verificare Paths:\n";
$rootDir = dirname(__DIR__);
echo "   - Root: $rootDir\n";
echo "   - Config: " . ($configExists = file_exists("$rootDir/config/config.php") ? "✓" : "✗") . "\n";
echo "   - Database: " . ($dbExists = file_exists("$rootDir/config/database.php") ? "✓" : "✗") . "\n";
echo "   - Mail: " . ($mailExists = file_exists("$rootDir/config/mail.php") ? "✓" : "✗") . "\n\n";

if (!$configExists || !$dbExists || !$mailExists) {
    die("ERROR: Lipsesc fișiere de configurare necesare!\n");
}

// 3. Încarcă configurația
echo "3. Încărcare Configurație:\n";
require_once "$rootDir/config/config.php";
require_once "$rootDir/config/database.php";

$dbConfig = getDatabaseConfig();
echo "   - Core DB: {$dbConfig['host']}:{$dbConfig['port']}/{$dbConfig['database']}\n";

// Test conexiune DB
try {
    $corePdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "   - Conexiune Core DB: ✓\n\n";
} catch (PDOException $e) {
    die("ERROR: Nu se poate conecta la Core DB: " . $e->getMessage() . "\n");
}

// 4. Verifică tabele necesare
echo "4. Verificare Structură DB:\n";
$requiredTables = [
    'companies' => 'Core',
    'users' => 'Core',
];

foreach ($requiredTables as $table => $db) {
    $stmt = $corePdo->query("SHOW TABLES LIKE '$table'");
    $exists = $stmt->rowCount() > 0;
    echo "   - $table ($db): " . ($exists ? "✓" : "✗") . "\n";
    if (!$exists) {
        die("ERROR: Tabelul $table lipsește din $db database!\n");
    }
}

// Găsește o companie activă pentru test
$stmt = $corePdo->query("SELECT id, tenant_db FROM companies WHERE is_active = 1 LIMIT 1");
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    die("ERROR: Nu există companii active în sistem!\n");
}

echo "   - Companie test: ID={$company['id']}, DB={$company['tenant_db']}\n";

// Verifică DB tenant
try {
    $tenantPdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$company['tenant_db']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "   - Conexiune Tenant DB: ✓\n";
} catch (PDOException $e) {
    die("ERROR: Nu se poate conecta la Tenant DB {$company['tenant_db']}: " . $e->getMessage() . "\n");
}

$tenantTables = [
    'notifications',
    'notification_templates',
    'notification_preferences',
    'notification_queue',
    'notification_logs',
    'vehicles',
    'insurance',
    'documents'
];

foreach ($tenantTables as $table) {
    $stmt = $tenantPdo->query("SHOW TABLES LIKE '$table'");
    $exists = $stmt->rowCount() > 0;
    echo "   - $table (Tenant): " . ($exists ? "✓" : "✗") . "\n";
    if (!$exists) {
        echo "      WARNING: Tabelul $table lipsește! Notificările pentru acest tip nu vor funcționa.\n";
    }
}
echo "\n";

// 5. Test script generare notificări
echo "5. Test Generare Notificări:\n";
echo "   Rulare: scripts/cron_generate_notifications.php\n";

$startTime = microtime(true);
ob_start();
include "$rootDir/scripts/cron_generate_notifications.php";
$output = ob_get_clean();
$duration = round(microtime(true) - $startTime, 2);

echo "   - Durată: {$duration}s\n";
echo "   - Output:\n";
foreach (explode("\n", trim($output)) as $line) {
    if (!empty($line)) {
        echo "     $line\n";
    }
}

// Verifică rezultate
$stmt = $tenantPdo->query("SELECT COUNT(*) as total FROM notifications WHERE DATE(created_at) = CURDATE()");
$notificationsCreated = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "   - Notificări create astăzi: $notificationsCreated\n\n";

// 6. Test procesare queue
echo "6. Test Procesare Queue:\n";
echo "   Rulare: scripts/process_notifications_queue.php\n";

// Verifică queue înainte
$stmt = $tenantPdo->query("SELECT COUNT(*) as pending FROM notification_queue WHERE sent = 0 AND attempts < max_attempts");
$pendingBefore = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
echo "   - Items în queue înainte: $pendingBefore\n";

if ($pendingBefore == 0) {
    echo "   - WARNING: Queue gol, nu există nimic de procesat!\n";
    echo "   - Sugestie: Rulează din nou testul după generare notificări.\n\n";
} else {
    $startTime = microtime(true);
    ob_start();
    
    // Simulează CLI pentru script
    $_SERVER['argc'] = 1;
    $_SERVER['argv'] = ['process_notifications_queue.php'];
    
    include "$rootDir/scripts/process_notifications_queue.php";
    $output = ob_get_clean();
    $duration = round(microtime(true) - $startTime, 2);
    
    echo "   - Durată: {$duration}s\n";
    echo "   - Output:\n";
    foreach (explode("\n", trim($output)) as $line) {
        if (!empty($line)) {
            echo "     $line\n";
        }
    }
    
    // Verifică queue după
    $stmt = $tenantPdo->query("SELECT COUNT(*) as pending FROM notification_queue WHERE sent = 0 AND attempts < max_attempts");
    $pendingAfter = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
    $processed = $pendingBefore - $pendingAfter;
    echo "   - Items procesate: $processed\n";
    echo "   - Items rămase: $pendingAfter\n\n";
}

// 7. Raport final
echo "=== RAPORT FINAL ===\n\n";

// Stats generale
$stmt = $tenantPdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count,
        SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count
    FROM notifications
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Notificări (ultimele 7 zile):\n";
echo "  - Total: {$stats['total']}\n";
echo "  - Citite: {$stats['read_count']}\n";
echo "  - Necitite: {$stats['unread_count']}\n\n";

// Stats queue
$stmt = $tenantPdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN sent = 1 THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN sent = 0 AND attempts >= max_attempts THEN 1 ELSE 0 END) as failed,
        SUM(CASE WHEN sent = 0 AND attempts < max_attempts THEN 1 ELSE 0 END) as pending
    FROM notification_queue
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$queueStats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Queue Email (ultimele 7 zile):\n";
echo "  - Total: {$queueStats['total']}\n";
echo "  - Trimise: {$queueStats['sent']}\n";
echo "  - Eșuate: {$queueStats['failed']}\n";
echo "  - În așteptare: {$queueStats['pending']}\n";

if ($queueStats['total'] > 0) {
    $successRate = round(($queueStats['sent'] / $queueStats['total']) * 100, 2);
    echo "  - Rata succes: $successRate%\n";
}
echo "\n";

// Recomandări
echo "=== RECOMANDĂRI ===\n\n";

if ($notificationsCreated == 0) {
    echo "⚠ Nu s-au generat notificări astăzi.\n";
    echo "  Cauze posibile:\n";
    echo "  - Nu există vehicule cu asigurări/documente ce expiră în următoarele 30 zile\n";
    echo "  - Nu există vehicule active în sistem\n";
    echo "  - Datele de expirare sunt în afara ferestrei de alertare\n\n";
}

if ($queueStats['pending'] > 10) {
    echo "⚠ Queue acumulează items ({$queueStats['pending']} pending).\n";
    echo "  Sugestii:\n";
    echo "  - Verifică configurare SendGrid în config/mail.php\n";
    echo "  - Verifică logs pentru erori: notification_logs table\n";
    echo "  - Rulează manual: php scripts/process_notifications_queue.php\n\n";
}

if ($queueStats['failed'] > 0) {
    echo "⚠ Există {$queueStats['failed']} email-uri eșuate definitiv.\n";
    echo "  Verifică notification_queue pentru error_message detalii.\n\n";
}

if (isset($successRate) && $successRate < 90) {
    echo "⚠ Rata de succes email scăzută ($successRate%).\n";
    echo "  Acțiuni:\n";
    echo "  - Verifică validitatea adreselor email destinatari\n";
    echo "  - Verifică limita SendGrid API\n";
    echo "  - Verifică logs pentru pattern-uri de eroare\n\n";
}

echo "✓ Toate testele completate!\n";
echo "✓ Sistemul este pregătit pentru configurare cron jobs.\n";
echo "✓ Consultă CRON_SETUP.md pentru instrucțiuni detaliate.\n";
