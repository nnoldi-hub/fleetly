<?php
/**
 * Test SMS Twilio Integration
 * 
 * Script pentru testarea configurării Twilio SMS
 * Rulați: php test_sms_twilio.php
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/SmsService.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';

echo "=== Test Integrare SMS Twilio ===\n\n";

// 1. Verifică dacă Twilio SDK este instalat
echo "1. Verificare Twilio SDK... ";
if (class_exists('Twilio\Rest\Client')) {
    echo "✓ Instalat\n";
} else {
    echo "✗ NU este instalat\n";
    echo "   Rulați: composer require twilio/sdk\n";
    exit(1);
}

// 2. Inițializare serviciu SMS
echo "2. Inițializare SmsService... ";
try {
    $smsService = new SmsService();
    echo "✓ OK\n";
} catch (Throwable $e) {
    echo "✗ Eroare: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Verifică configurarea
echo "3. Verificare configurare... ";
if ($smsService->isConfigured()) {
    echo "✓ Configurat\n";
    $settings = $smsService->getSettings();
    echo "   Provider: " . ($settings['provider'] ?? 'N/A') . "\n";
    echo "   From: " . ($settings['from'] ?? 'N/A') . "\n";
    echo "   Account SID: " . (empty($settings['account_sid']) ? 'NU' : 'DA (' . substr($settings['account_sid'], 0, 10) . '...)') . "\n";
} else {
    echo "✗ NU este configurat\n\n";
    echo "Pentru a configura Twilio:\n";
    echo "1. Creați un cont pe https://www.twilio.com/\n";
    echo "2. Obțineți:\n";
    echo "   - Account SID\n";
    echo "   - Auth Token\n";
    echo "   - Număr de telefon Twilio (From Number)\n";
    echo "3. Accesați în aplicație: Notificări > Setări > SMS\n";
    echo "4. Introduceți credențialele și salvați\n\n";
    
    // Oferim posibilitatea de configurare interactivă
    echo "Doriți să configurați acum? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    
    if (strtolower($line) === 'y') {
        echo "\n=== Configurare Twilio ===\n";
        
        echo "Account SID: ";
        $accountSid = trim(fgets($handle));
        
        echo "Auth Token: ";
        $authToken = trim(fgets($handle));
        
        echo "From Number (ex: +1234567890): ";
        $fromNumber = trim(fgets($handle));
        
        $newSettings = [
            'provider' => 'twilio',
            'enabled' => true,
            'account_sid' => $accountSid,
            'auth_token' => $authToken,
            'from' => $fromNumber
        ];
        
        if ($smsService->updateSettings($newSettings)) {
            echo "\n✓ Configurare salvată cu succes!\n";
        } else {
            echo "\n✗ Eroare la salvarea configurării\n";
            exit(1);
        }
    } else {
        exit(0);
    }
}

// 4. Test trimitere SMS (opțional)
echo "\n4. Test trimitere SMS\n";
echo "   Număr destinatar (format: +40712345678) sau ENTER pentru skip: ";
$handle = fopen("php://stdin", "r");
$testNumber = trim(fgets($handle));

if (!empty($testNumber)) {
    echo "   Trimit SMS de test către $testNumber... ";
    
    $testMessage = "Test SMS din Fleet Management - " . date('Y-m-d H:i:s');
    $result = $smsService->send($testNumber, $testMessage);
    
    if ($result['success']) {
        echo "✓ SMS trimis cu succes!\n";
        if (isset($result['sid'])) {
            echo "   Twilio SID: " . $result['sid'] . "\n";
        }
    } else {
        echo "✗ Eroare: " . ($result['error'] ?? 'Necunoscută') . "\n";
    }
} else {
    echo "   Test skip.\n";
}

echo "\n=== Test finalizat ===\n";
echo "\nPași următori:\n";
echo "1. Configurați preferințele utilizatorilor pentru SMS în aplicație\n";
echo "2. Activați canalul SMS în setările de notificări\n";
echo "3. Testați notificările din interfața web\n";
echo "4. Configurați cron job pentru procesarea cozii:\n";
echo "   */5 * * * * cd " . __DIR__ . " && php scripts/process_notifications_queue.php\n";
