<?php
/**
 * TEST CRON: Simulare completă generare + trimitere notificări
 * Rulează: php scripts/test_full_notification_flow.php
 * Sau browser: http://management.nks-cables.ro/scripts/test_full_notification_flow.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.step { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #4CAF50; }
.error { border-color: #f44336; }
.success { color: #4CAF50; font-weight: bold; }
.warning { color: #ff9800; }
.info { color: #2196F3; }
pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🧪 Test Complet Flow Notificări</h1>";
echo "<p><strong>Data/Ora:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

$db = Database::getInstance();

// ============================================================
// STEP 1: Verifică documente care expiră în 30 zile
// ============================================================
echo "<div class='step'>";
echo "<h2>📋 STEP 1: Căutare documente care expiră</h2>";

$today = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+30 days'));

echo "<p>🔍 Căutăm documente între <strong>$today</strong> și <strong>$endDate</strong></p>";

try {
    $sql = "SELECT 
                d.id,
                d.vehicle_id,
                d.document_type,
                d.issue_date,
                d.expiry_date,
                DATEDIFF(d.expiry_date, CURDATE()) as days_until_expiry,
                v.registration_number,
                v.company_id
            FROM documents d
            JOIN vehicles v ON d.vehicle_id = v.id
            WHERE d.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            AND d.status = 'active'
            ORDER BY d.expiry_date ASC
            LIMIT 10";
    
    $documents = $db->fetchAllOn('documents', $sql);
    
    if (empty($documents)) {
        echo "<p class='warning'>⚠️ Nu s-au găsit documente care expiră în următoarele 30 zile.</p>";
        echo "<p>💡 <strong>Sugestie:</strong> Introdu un document cu data de expirare peste 5-10 zile pentru test.</p>";
    } else {
        echo "<p class='success'>✅ Găsite " . count($documents) . " documente:</p>";
        echo "<pre>";
        foreach ($documents as $doc) {
            echo sprintf(
                "ID: %d | Vehicul: %s | Tip: %s | Expiră: %s (%d zile)\n",
                $doc['id'],
                $doc['registration_number'],
                $doc['document_type'],
                $doc['expiry_date'],
                $doc['days_until_expiry']
            );
        }
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Eroare SQL: " . $e->getMessage() . "</p>";
}

echo "</div>";

// ============================================================
// STEP 2: Verifică utilizatori care au preferințe
// ============================================================
echo "<div class='step'>";
echo "<h2>👥 STEP 2: Verifică utilizatori și preferințe</h2>";

try {
    // Verifică câți useri au preferințe setate
    $prefsCount = $db->fetchOn('notification_preferences', 
        "SELECT COUNT(*) as count FROM notification_preferences"
    );
    
    echo "<p>📊 Utilizatori cu preferințe salvate: <strong>{$prefsCount['count']}</strong></p>";
    
    if ($prefsCount['count'] > 0) {
        $prefs = $db->fetchAllOn('notification_preferences',
            "SELECT 
                np.id, 
                np.user_id, 
                np.email, 
                np.phone,
                np.email_enabled,
                np.sms_enabled,
                np.in_app_enabled,
                np.enabled_types,
                u.username,
                u.email as user_email
            FROM notification_preferences np
            LEFT JOIN users u ON np.user_id = u.id
            LIMIT 5"
        );
        
        echo "<pre>";
        foreach ($prefs as $pref) {
            $types = json_decode($pref['enabled_types'], true);
            echo sprintf(
                "User: %s | Email: %s (%s) | Phone: %s (%s) | Tipuri: %s\n",
                $pref['username'] ?? 'N/A',
                $pref['email'] ?? $pref['user_email'] ?? 'none',
                $pref['email_enabled'] ? 'ON' : 'OFF',
                $pref['phone'] ?? 'none',
                $pref['sms_enabled'] ? 'ON' : 'OFF',
                is_array($types) ? implode(', ', $types) : 'none'
            );
        }
        echo "</pre>";
    } else {
        echo "<p class='warning'>⚠️ Niciun utilizator nu are preferințe setate.</p>";
        echo "<p>💡 Setează preferințele din: <a href='/notifications/preferences'>/notifications/preferences</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Eroare: " . $e->getMessage() . "</p>";
}

echo "</div>";

// ============================================================
// STEP 3: Simulare generare notificări
// ============================================================
echo "<div class='step'>";
echo "<h2>🔔 STEP 3: Simulare generare notificări</h2>";

if (!empty($documents)) {
    echo "<p class='info'>🚀 Ar trebui să se genereze notificări pentru:</p>";
    echo "<ul>";
    
    foreach ($documents as $doc) {
        echo "<li>";
        echo "Document <strong>{$doc['document_type']}</strong> ";
        echo "pentru vehicul <strong>{$doc['registration_number']}</strong> ";
        echo "(expiră în <strong>{$doc['days_until_expiry']}</strong> zile)";
        
        // Check dacă există deja notificare
        $existingNotif = $db->fetchOn('notifications',
            "SELECT id, created_at FROM notifications 
             WHERE reference_type = 'document' 
             AND reference_id = ? 
             AND notification_type = 'document_expiry'
             ORDER BY created_at DESC LIMIT 1",
            [$doc['id']]
        );
        
        if ($existingNotif) {
            echo " <span class='warning'>⚠️ Notificare deja există (ID: {$existingNotif['id']}, creată: {$existingNotif['created_at']})</span>";
        } else {
            echo " <span class='success'>✅ Notificare nouă va fi generată</span>";
        }
        
        echo "</li>";
    }
    echo "</ul>";
    
    echo "<p><strong>📝 Pentru a genera efectiv notificările, rulează:</strong></p>";
    echo "<pre>php scripts/cron_generate_notifications.php</pre>";
    
} else {
    echo "<p class='warning'>⚠️ Nu există documente pentru care să se genereze notificări.</p>";
}

echo "</div>";

// ============================================================
// STEP 4: Verifică notificări existente
// ============================================================
echo "<div class='step'>";
echo "<h2>📬 STEP 4: Notificări existente în DB</h2>";

try {
    $recentNotifs = $db->fetchAllOn('notifications',
        "SELECT 
            id,
            user_id,
            notification_type,
            priority,
            title,
            message,
            reference_type,
            reference_id,
            is_read,
            created_at
        FROM notifications
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY created_at DESC
        LIMIT 10"
    );
    
    if (empty($recentNotifs)) {
        echo "<p class='warning'>⚠️ Nu există notificări generate în ultimele 7 zile.</p>";
    } else {
        echo "<p class='success'>✅ Găsite " . count($recentNotifs) . " notificări recente:</p>";
        echo "<pre>";
        foreach ($recentNotifs as $notif) {
            echo sprintf(
                "ID: %d | Tip: %s | User: %d | %s | %s | Citită: %s\n",
                $notif['id'],
                $notif['notification_type'],
                $notif['user_id'],
                substr($notif['title'], 0, 40),
                $notif['created_at'],
                $notif['is_read'] ? 'DA' : 'NU'
            );
        }
        echo "</pre>";
    }
    
    // Check notification queue
    $queueCount = $db->fetchOn('notification_queue',
        "SELECT COUNT(*) as count FROM notification_queue 
         WHERE status = 'pending'"
    );
    
    if ($queueCount && $queueCount['count'] > 0) {
        echo "<p class='info'>📧 <strong>{$queueCount['count']}</strong> notificări în coadă pentru trimitere.</p>";
        echo "<p><strong>Pentru a le trimite, rulează:</strong></p>";
        echo "<pre>php scripts/process_notifications_queue.php</pre>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Eroare: " . $e->getMessage() . "</p>";
}

echo "</div>";

// ============================================================
// STEP 5: Verifică configurare email
// ============================================================
echo "<div class='step'>";
echo "<h2>✉️ STEP 5: Verifică configurare email</h2>";

if (file_exists(__DIR__ . '/../config/mail.php')) {
    require_once __DIR__ . '/../config/mail.php';
    
    if (defined('MAIL_ENABLED') && MAIL_ENABLED) {
        echo "<p class='success'>✅ Email activat</p>";
        echo "<ul>";
        echo "<li>Provider: " . (defined('MAIL_PROVIDER') ? MAIL_PROVIDER : 'N/A') . "</li>";
        echo "<li>From: " . (defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'N/A') . "</li>";
        echo "<li>From Name: " . (defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'N/A') . "</li>";
        echo "</ul>";
    } else {
        echo "<p class='warning'>⚠️ Email dezactivat în config</p>";
    }
} else {
    echo "<p class='error'>❌ Fișier config/mail.php lipsește</p>";
}

echo "</div>";

// ============================================================
// REZUMAT
// ============================================================
echo "<div class='step'>";
echo "<h2>📊 REZUMAT & PAȘI URMĂTORI</h2>";

echo "<ol>";
echo "<li><strong>Adaugă un document de test:</strong> cu data expirare peste 5-10 zile</li>";
echo "<li><strong>Setează preferințele:</strong> <a href='/notifications/preferences' target='_blank'>/notifications/preferences</a></li>";
echo "<li><strong>Generează notificări:</strong> <code>php scripts/cron_generate_notifications.php</code></li>";
echo "<li><strong>Procesează coada:</strong> <code>php scripts/process_notifications_queue.php</code></li>";
echo "<li><strong>Verifică email-urile:</strong> în inbox-ul configurat</li>";
echo "</ol>";

echo "<p style='margin-top: 20px;'><strong>🔄 Refresh această pagină după ce adaugi documentul și rulezi scripturile.</strong></p>";

echo "</div>";

echo "</body></html>";
?>
