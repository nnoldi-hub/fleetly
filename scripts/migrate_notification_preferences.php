<?php
/**
 * Migration Script: Notification Preferences from system_settings to notification_preferences
 * 
 * Usage: php scripts/migrate_notification_preferences.php
 * 
 * This script migrates legacy notification preferences stored in system_settings
 * (key: notifications_prefs_user_{id}, value: JSON) to the new notification_preferences table.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../modules/notifications/models/NotificationPreference.php';

echo "\n";
echo "========================================\n";
echo "  Notification Preferences Migration\n";
echo "========================================\n\n";

// Check dacă tabelul notification_preferences există
$db = Database::getInstance();
try {
    $check = $db->fetch("SELECT 1 FROM notification_preferences LIMIT 1");
} catch (Throwable $e) {
    echo "❌ ERROR: Tabelul notification_preferences nu există!\n";
    echo "   Rulează mai întâi: sql/migrations/2025_01_12_001_notification_system_v2.sql\n\n";
    exit(1);
}

echo "✅ Tabelul notification_preferences există\n\n";

// Get all active users cu company_id
echo "📊 Căutare utilizatori activi...\n";
try {
    $users = $db->fetchAll("SELECT id, company_id, username, email FROM users WHERE status = 'active' AND company_id IS NOT NULL ORDER BY id ASC");
    echo "   Găsiți " . count($users) . " utilizatori activi\n\n";
} catch (Throwable $e) {
    echo "❌ ERROR la interogare users: " . $e->getMessage() . "\n\n";
    exit(1);
}

if (empty($users)) {
    echo "⚠️  Nu există utilizatori activi pentru migrare\n\n";
    exit(0);
}

// Statistici
$stats = [
    'total' => count($users),
    'migrated' => 0,
    'skipped_no_legacy' => 0,
    'skipped_already_exists' => 0,
    'errors' => []
];

// Procesare utilizatori
echo "🔄 Începe migrarea...\n";
echo str_repeat("─", 80) . "\n\n";

foreach ($users as $user) {
    $userId = $user['id'];
    $companyId = $user['company_id'];
    $username = $user['username'] ?? 'unknown';
    
    echo sprintf("[%03d] User: %-20s ", $userId, $username);
    
    // Check dacă deja există preferences în noul tabel
    $prefModel = new NotificationPreference();
    $existing = $prefModel->getByUserId($userId);
    
    if ($existing) {
        echo "⏭️  SKIP (already migrated)\n";
        $stats['skipped_already_exists']++;
        continue;
    }
    
    // Caută legacy preferences în system_settings
    $key = 'notifications_prefs_user_' . $userId;
    try {
        $row = $db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$key]);
    } catch (Throwable $e) {
        echo "❌ ERROR (DB read): " . $e->getMessage() . "\n";
        $stats['errors'][] = "User $userId ($username): DB read error";
        continue;
    }
    
    if (!$row || empty($row['setting_value'])) {
        echo "⏭️  SKIP (no legacy data)\n";
        $stats['skipped_no_legacy']++;
        continue;
    }
    
    // Decode JSON legacy
    $legacy = json_decode($row['setting_value'], true);
    if (!is_array($legacy)) {
        echo "❌ ERROR (invalid JSON)\n";
        $stats['errors'][] = "User $userId ($username): Invalid JSON in system_settings";
        continue;
    }
    
    // Mapping din format vechi → nou
    $newPrefs = [
        'user_id' => $userId,
        'company_id' => $companyId,
        'email_enabled' => !empty($legacy['methods']['email']) ? 1 : 0,
        'sms_enabled' => !empty($legacy['methods']['sms']) ? 1 : 0,
        'push_enabled' => 0, // Legacy nu avea push
        'in_app_enabled' => !empty($legacy['methods']['in_app']) ? 1 : 0,
        'enabled_types' => $legacy['enabledCategories'] ?? ['document_expiry', 'insurance_expiry', 'maintenance_due'],
        'frequency' => 'immediate', // Legacy nu avea frecvență, setăm default
        'email' => null, // Legacy nu avea override email
        'phone' => null, // Legacy nu avea override phone
        'push_token' => null,
        'min_priority' => $legacy['minPriority'] ?? 'low',
        'broadcast_to_company' => !empty($legacy['broadcastToCompany']) ? 1 : 0,
        'days_before_expiry' => $legacy['daysBefore'] ?? 30,
        'quiet_hours' => null, // Legacy nu avea
        'timezone' => 'Europe/Bucharest'
    ];
    
    // Insert în noul tabel
    $result = $prefModel->createOrUpdate($userId, $companyId, $newPrefs);
    
    if ($result['success']) {
        echo "✅ MIGRATED\n";
        $stats['migrated']++;
    } else {
        echo "❌ ERROR: " . ($result['message'] ?? 'Unknown error') . "\n";
        $stats['errors'][] = "User $userId ($username): " . ($result['message'] ?? 'Unknown error');
    }
}

echo "\n" . str_repeat("─", 80) . "\n";
echo "📊 REZULTATE FINALE\n";
echo str_repeat("─", 80) . "\n\n";

echo "Total utilizatori:              {$stats['total']}\n";
echo "✅ Migrați cu succes:           {$stats['migrated']}\n";
echo "⏭️  Skipped (no legacy):        {$stats['skipped_no_legacy']}\n";
echo "⏭️  Skipped (already exists):   {$stats['skipped_already_exists']}\n";
echo "❌ Erori:                        " . count($stats['errors']) . "\n\n";

if (!empty($stats['errors'])) {
    echo "Detalii erori:\n";
    echo str_repeat("─", 80) . "\n";
    foreach ($stats['errors'] as $idx => $error) {
        echo sprintf("%2d. %s\n", $idx + 1, $error);
    }
    echo "\n";
}

// Verificare finală: count rows în ambele tabele
try {
    $legacyCount = $db->fetch("SELECT COUNT(*) as count FROM system_settings WHERE setting_key LIKE 'notifications_prefs_user_%'");
    $newCount = $db->fetch("SELECT COUNT(*) as count FROM notification_preferences");
    
    echo "Verificare integritate:\n";
    echo "  • Legacy entries (system_settings): {$legacyCount['count']}\n";
    echo "  • New entries (notification_preferences): {$newCount['count']}\n";
    
    if ($newCount['count'] >= $legacyCount['count']) {
        echo "  ✅ Migrare completă! Toate preferințele au fost transferate.\n\n";
    } else {
        $diff = $legacyCount['count'] - $newCount['count'];
        echo "  ⚠️  Atenție: Lipsesc $diff înregistrări! Verifică erorile de mai sus.\n\n";
    }
    
} catch (Throwable $e) {
    echo "⚠️  Nu s-a putut verifica integritatea: " . $e->getMessage() . "\n\n";
}

// Success rate
$successRate = $stats['total'] > 0 ? round(($stats['migrated'] / $stats['total']) * 100, 2) : 0;
echo "Success Rate: {$successRate}%\n\n";

if ($successRate >= 90) {
    echo "🎉 Migrare finalizată cu succes!\n\n";
    echo "Următorii pași:\n";
    echo "1. Verifică manual câteva înregistrări în phpMyAdmin\n";
    echo "2. Testează UI preferences: /modules/notifications/views/preferences.php\n";
    echo "3. Generează notificări test și verifică că folosesc noile preferințe\n";
    echo "4. (Opțional) După validare, șterge cheile vechi din system_settings:\n";
    echo "   DELETE FROM system_settings WHERE setting_key LIKE 'notifications_prefs_user_%';\n\n";
    exit(0);
} else {
    echo "⚠️  Migrare parțială! Verifică erorile și re-rulează scriptul.\n\n";
    exit(1);
}
?>
