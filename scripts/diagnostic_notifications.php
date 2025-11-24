<?php
/**
 * Script de diagnostic pentru sistemul de notificări
 * Verifică tabele, date, configurații
 */

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<pre>';
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

function check($label, $condition, $successMsg = '✓ OK', $errorMsg = '✗ FAIL') {
    echo str_pad($label . ':', 50, ' ');
    if ($condition) {
        echo "\033[32m" . $successMsg . "\033[0m\n";
        return true;
    } else {
        echo "\033[31m" . $errorMsg . "\033[0m\n";
        return false;
    }
}

function hr() {
    echo str_repeat('=', 80) . "\n";
}

echo "\n";
hr();
echo "🔔  DIAGNOSTIC SISTEM NOTIFICĂRI - Fleet Management\n";
hr();

// 1. CHECK TABELE
echo "\n📊 VERIFICARE TABELE:\n";
echo str_repeat('-', 80) . "\n";

$tables = [
    'notifications' => 'Tabel notificări principale',
    'notification_preferences' => 'Tabel preferințe utilizatori (V2)',
    'notification_queue' => 'Tabel queue procesare asincronă (V2)',
    'notification_templates' => 'Tabel template-uri mesaje (V2)',
    'notification_logs' => 'Tabel log-uri evenimente',
    'system_settings' => 'Tabel setări sistem (legacy)'
];

$missingTables = [];
foreach ($tables as $table => $desc) {
    try {
        $db->query("SELECT 1 FROM $table LIMIT 1");
        check($desc, true, "✓ EXISTS");
    } catch (Throwable $e) {
        check($desc, false, '', "✗ MISSING");
        $missingTables[] = $table;
    }
}

// 2. CHECK COLOANE CRITICE
echo "\n📋 VERIFICARE COLOANE CRITICE:\n";
echo str_repeat('-', 80) . "\n";

$columns = [
    'notifications' => ['user_id', 'company_id', 'type', 'is_read', 'template_id'],
    'notification_preferences' => ['user_id', 'company_id', 'email_enabled', 'enabled_types', 'frequency'],
    'notification_queue' => ['notification_id', 'user_id', 'channel', 'status', 'scheduled_at'],
    'notification_templates' => ['slug', 'email_subject', 'email_body', 'enabled']
];

foreach ($columns as $table => $cols) {
    if (in_array($table, $missingTables)) continue;
    
    foreach ($cols as $col) {
        try {
            $db->query("SELECT $col FROM $table LIMIT 1");
            check("  $table.$col", true, "✓");
        } catch (Throwable $e) {
            check("  $table.$col", false, '', "✗ MISSING");
        }
    }
}

// 3. CHECK DATE
echo "\n📦 VERIFICARE DATE:\n";
echo str_repeat('-', 80) . "\n";

try {
    // Notifications count
    $notifCount = $db->fetch("SELECT COUNT(*) as cnt FROM notifications");
    echo "  Notificări în DB: " . number_format($notifCount['cnt']) . "\n";
    
    // Template-uri
    if (!in_array('notification_templates', $missingTables)) {
        $templatesCount = $db->fetch("SELECT COUNT(*) as cnt FROM notification_templates");
        echo "  Template-uri: " . $templatesCount['cnt'] . "\n";
        
        $templates = $db->fetchAll("SELECT slug, name, enabled FROM notification_templates ORDER BY slug");
        foreach ($templates as $t) {
            $status = $t['enabled'] ? '✓ ACTIVAT' : '✗ DEZACTIVAT';
            echo "    - {$t['slug']}: {$t['name']} ($status)\n";
        }
    }
    
    // Preferințe utilizatori
    if (!in_array('notification_preferences', $missingTables)) {
        $prefsCount = $db->fetch("SELECT COUNT(*) as cnt FROM notification_preferences");
        echo "  Preferințe utilizatori: " . $prefsCount['cnt'] . "\n";
    }
    
    // Queue
    if (!in_array('notification_queue', $missingTables)) {
        $queueStats = $db->fetch("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status='sent' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed
            FROM notification_queue");
        
        echo "  Queue statistici:\n";
        echo "    - Total items: {$queueStats['total']}\n";
        echo "    - Pending: {$queueStats['pending']}\n";
        echo "    - Sent: {$queueStats['sent']}\n";
        echo "    - Failed: {$queueStats['failed']}\n";
    }
    
} catch (Throwable $e) {
    echo "  ✗ Eroare la citire date: " . $e->getMessage() . "\n";
}

// 4. CHECK MODELE PHP
echo "\n🔧 VERIFICARE MODELE PHP:\n";
echo str_repeat('-', 80) . "\n";

$models = [
    'Notification' => __DIR__ . '/../modules/notifications/models/Notification.php',
    'NotificationPreference' => __DIR__ . '/../modules/notifications/models/NotificationPreference.php',
    'NotificationQueue' => __DIR__ . '/../modules/notifications/models/NotificationQueue.php',
    'NotificationTemplate' => __DIR__ . '/../modules/notifications/models/NotificationTemplate.php',
    'NotificationLog' => __DIR__ . '/../modules/notifications/models/NotificationLog.php'
];

foreach ($models as $name => $path) {
    check("  Model $name", file_exists($path));
}

// 5. CHECK SCRIPTURI CRON
echo "\n⏰ VERIFICARE SCRIPTURI CRON:\n";
echo str_repeat('-', 80) . "\n";

$scripts = [
    'cron_generate_notifications.php' => 'Generare notificări automate (zilnic 06:00)',
    'process_notifications_queue.php' => 'Procesare queue (la 5 minute)',
    'retry_failed_notifications.php' => 'Retry failed (la 1 oră)',
    'cleanup_notifications.php' => 'Curățare date vechi (zilnic 04:00)'
];

foreach ($scripts as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    check("  $file", $exists);
    if ($exists) {
        echo "      → $desc\n";
    }
}

// 6. CHECK CONFIGURAȚII
echo "\n⚙️  VERIFICARE CONFIGURAȚII:\n";
echo str_repeat('-', 80) . "\n";

try {
    $smtpSettings = $db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'smtp_settings'");
    if ($smtpSettings && !empty($smtpSettings['setting_value'])) {
        $smtp = json_decode($smtpSettings['setting_value'], true);
        echo "  SMTP configurat:\n";
        echo "    - Host: " . ($smtp['host'] ?? 'N/A') . "\n";
        echo "    - Port: " . ($smtp['port'] ?? 'N/A') . "\n";
        echo "    - Encryption: " . ($smtp['encryption'] ?? 'N/A') . "\n";
        echo "    - From: " . ($smtp['from_email'] ?? 'N/A') . "\n";
    } else {
        echo "  ⚠️  SMTP neconfgurat\n";
    }
    
    $smsSettings = $db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'sms_settings'");
    if ($smsSettings && !empty($smsSettings['setting_value'])) {
        $sms = json_decode($smsSettings['setting_value'], true);
        echo "  SMS configurat:\n";
        echo "    - Provider: " . ($sms['provider'] ?? 'N/A') . "\n";
        echo "    - From: " . ($sms['from'] ?? 'N/A') . "\n";
    } else {
        echo "  ⚠️  SMS neconfigurat\n";
    }
} catch (Throwable $e) {
    echo "  ✗ Eroare la citire configurații: " . $e->getMessage() . "\n";
}

// 7. RECOMANDĂRI
echo "\n💡 RECOMANDĂRI:\n";
echo str_repeat('-', 80) . "\n";

if (!empty($missingTables)) {
    echo "  ⚠️  ATENȚIE: Lipsesc tabele critice!\n";
    echo "  → Rulează migrarea: sql/migrations/2025_01_12_001_notification_system_v2.sql\n";
    echo "  → Comandă: php scripts/run_migration.php --file=sql/migrations/2025_01_12_001_notification_system_v2.sql\n\n";
}

if (!in_array('notification_templates', $missingTables)) {
    $templateCount = $db->fetch("SELECT COUNT(*) as cnt FROM notification_templates");
    if ($templateCount['cnt'] == 0) {
        echo "  ⚠️  Nu există template-uri în DB!\n";
        echo "  → Template-urile default se populează automat la rularea migrării\n\n";
    }
}

if (!in_array('notification_preferences', $missingTables)) {
    $prefsCount = $db->fetch("SELECT COUNT(*) as cnt FROM notification_preferences");
    if ($prefsCount['cnt'] == 0) {
        echo "  ℹ️  Nu există preferințe utilizatori (normal pentru prima rulare)\n";
        echo "  → Utilizatorii vor avea valori default până la salvarea preferințelor\n\n";
    }
}

hr();
echo "✅ Diagnostic finalizat!\n";
hr();

if (php_sapi_name() !== 'cli') {
    echo '</pre>';
}
?>
