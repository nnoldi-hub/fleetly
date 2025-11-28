<?php
// Debug: Test salvare preferințe
// Rulează: http://localhost/fleet-management/test_save_preferences.php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/modules/notifications/models/NotificationPreference.php';

// Simulare user logat
session_start();
$auth = Auth::getInstance();
$currentUser = $auth->user();

if (!$currentUser) {
    die("❌ Nu ești autentificat! Loghează-te întâi.");
}

$userId = $currentUser->id;
$companyId = $currentUser->company_id;

echo "<h2>🔍 Debug Salvare Preferințe Notificări</h2>";
echo "<pre>";
echo "User ID: $userId\n";
echo "Company ID: $companyId\n";
echo "Email: " . ($currentUser->email ?? 'N/A') . "\n\n";

// Test data
$testData = [
    'email_enabled' => 1,
    'sms_enabled' => 1,
    'push_enabled' => 0,
    'in_app_enabled' => 1,
    'enabled_types' => ['insurance_expiry', 'document_expiry', 'maintenance_due'],
    'frequency' => 'immediate',
    'email' => 'noldi.nyikora@nks-cables.ro',
    'phone' => '+40712345678',
    'push_token' => null,
    'min_priority' => 'low',
    'broadcast_to_company' => 0,
    'days_before_expiry' => 30,
    'quiet_hours' => ['start' => '22:00', 'end' => '08:00'],
    'timezone' => 'Europe/Bucharest'
];

echo "📋 Date de salvat:\n";
print_r($testData);
echo "\n";

// Verifică dacă există deja preferințe
$prefsModel = new NotificationPreference();
$existing = $prefsModel->getByUserId($userId);

echo "📊 Preferințe existente:\n";
if ($existing) {
    print_r($existing);
} else {
    echo "Niciuna (va fi INSERT)\n";
}
echo "\n";

// Încearcă salvarea
echo "💾 Încerc să salvez...\n";
try {
    $result = $prefsModel->createOrUpdate($userId, $companyId, $testData);
    
    echo "✅ Rezultat salvare:\n";
    print_r($result);
    echo "\n";
    
    // Verifică în baza de date
    $db = Database::getInstance();
    $saved = $db->fetchOn('notification_preferences', 
        "SELECT * FROM notification_preferences WHERE user_id = ?", 
        [$userId]
    );
    
    echo "📊 Date salvate în DB:\n";
    if ($saved) {
        print_r($saved);
    } else {
        echo "❌ NU S-A SALVAT NIMIC!\n";
    }
    
} catch (Throwable $e) {
    echo "❌ EROARE: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
