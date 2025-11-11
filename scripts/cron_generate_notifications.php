<?php
// scripts/cron_generate_notifications.php
// Rulează generarea automată de notificări pentru toate companiile (multi-tenant) + fallback single-tenant.
// De programat zilnic (ex: 06:00) prin cron / Task Scheduler.

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../modules/notifications/services/NotificationGenerator.php';
require_once __DIR__ . '/../modules/notifications/models/NotificationLog.php';

$db = Database::getInstance();

function out($m) { echo '['.date('Y-m-d H:i:s').'] '.$m.PHP_EOL; }

out('Pornire generare automată notificări');

$companies = [];
try {
    // Tabela companies sau distinct company_id din users (fallback dacă nu există tabela companies)
    try {
        $companies = $db->fetchAll("SELECT id FROM companies WHERE status = 'active'", []);
    } catch (Throwable $e) {
        $companies = $db->fetchAll("SELECT DISTINCT company_id AS id FROM users WHERE company_id IS NOT NULL", []);
    }
} catch (Throwable $e) {
    out('Eroare la încărcarea companiilor: '.$e->getMessage());
}

// Dacă nu există companii (single-tenant sau superadmin only), rulăm fallback pe companyId = null
if (empty($companies)) {
    $companies = [['id' => null]];
}

$generator = new NotificationGenerator($db);
$totalEvents = 0; $companiesProcessed = 0;

foreach ($companies as $c) {
    $companyId = $c['id'];
    $companiesProcessed++;
    // Căutăm un user admin/manager pentru preferințe (daysBefore + broadcast)
    $adminUserId = null;
    try {
        // Schema nouă cu roles
        try {
            $adminRow = $db->fetch("SELECT u.id FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE 
                u.company_id = ? AND r.slug IN ('admin','manager','fleet_manager','superadmin') AND u.status='active' ORDER BY r.level ASC LIMIT 1", [$companyId]);
        } catch (Throwable $e) {
            $adminRow = $db->fetch("SELECT id FROM users WHERE company_id = ? AND role IN ('admin','manager') AND status='active' LIMIT 1", [$companyId]);
        }
        if ($adminRow && isset($adminRow['id'])) { $adminUserId = (int)$adminRow['id']; }
    } catch (Throwable $e) { /* ignorăm */ }

    if ($adminUserId) { $_SESSION['user_id'] = $adminUserId; } // pentru fabricile ce citesc $_SESSION

    $count = 0;
    try {
        $count = $generator->runForCompany($companyId, $adminUserId);
        $totalEvents += $count;
        out('Companie '.($companyId ?? 'N/A').': evenimente generate='.$count);
        NotificationLog::log('cron_generation', 'created', ['company_id' => $companyId, 'events' => $count], null);
    } catch (Throwable $e) {
        out('Eroare companie '.($companyId ?? 'N/A').': '.$e->getMessage());
        NotificationLog::log('cron_generation', 'error', ['company_id' => $companyId], null, $e->getMessage());
    }
}

out('Finalizat. Companii procesate='.$companiesProcessed.' Total evenimente='.$totalEvents);

// Output simplu HTTP
if (php_sapi_name() !== 'cli') {
    echo "\nFinalizat. Companii procesate=$companiesProcessed Total evenimente=$totalEvents";    
}
?>