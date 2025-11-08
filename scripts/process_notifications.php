<?php
// scripts/process_notifications.php
// CLI/HTTP script to process pending notifications and send email/SMS based on user preferences

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/notifications/models/notification.php';
require_once __DIR__ . '/../modules/notifications/services/Notifier.php';

$db = Database::getInstance();
$notifier = new Notifier();

function log_line($m) { echo '['.date('Y-m-d H:i:s')."] $m\n"; }

// Fetch pending notifications (limit to 100 per run to avoid overload)
$rows = $db->fetchAll("SELECT n.* FROM notifications n WHERE n.status = 'pending' ORDER BY n.created_at ASC LIMIT 100", []);
$processed = 0; $sent = 0; $errors = 0;

foreach ($rows as $n) {
    $processed++;
    $userId = (int)($n['user_id'] ?? 0);
    if ($userId <= 0) { continue; }

    // Load user email
    $user = $db->fetch("SELECT email FROM users WHERE id = ?", [$userId]);
    $emailTo = $user['email'] ?? '';

    // Load user prefs
    $prefsKey = 'notifications_prefs_user_' . $userId;
    $prefsRow = $db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$prefsKey]);
    $prefs = ['methods' => ['in_app'=>1,'email'=>0,'sms'=>0], 'minPriority'=>'low'];
    if ($prefsRow && !empty($prefsRow['setting_value'])) {
        $dec = json_decode($prefsRow['setting_value'], true);
        if (is_array($dec)) { $prefs = array_replace_recursive($prefs, $dec); }
    }

    $subject = ($n['title'] ?? 'Notificare') . ' - ' . (defined('APP_NAME') ? APP_NAME : 'Fleet Management');
    $body = ($n['message'] ?? '') . (isset($n['action_url']) && $n['action_url'] ? "\n\nVezi detalii: " . rtrim(BASE_URL, '/') . $n['action_url'] : '');

    $ok_email = true; $ok_sms = true;

    if (!empty($prefs['methods']['email']) && $emailTo) {
        [$ok_email, $err] = $notifier->sendEmail($emailTo, $subject, $body);
        if (!$ok_email) { $errors++; log_line('Email fail: '.$err); }
    }

    if (!empty($prefs['methods']['sms'])) {
        // Determine phone number: per-user override or default from sms_settings
        $userPhoneRow = $db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = ?", ['user_'.$userId.'_sms_to']);
        $smsSettingsRow = $db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'sms_settings'");
        $smsSettings = $smsSettingsRow && $smsSettingsRow['setting_value'] ? json_decode($smsSettingsRow['setting_value'], true) : [];
        $toPhone = '';
        if ($userPhoneRow && !empty($userPhoneRow['setting_value'])) { $toPhone = trim($userPhoneRow['setting_value']); }
        elseif (!empty($smsSettings['sms_default_to'])) { $toPhone = trim($smsSettings['sms_default_to']); }
        if ($toPhone) {
            [$ok_sms, $err] = $notifier->sendSms($toPhone, $n['message'] ?? 'Notificare');
            if (!$ok_sms) { $errors++; log_line('SMS fail: '.$err); }
        }
    }

    // Mark as sent if at least one channel attempted (or if no channels enabled, keep pending)
    if (($prefs['methods']['email'] && $ok_email) || ($prefs['methods']['sms'] && $ok_sms) || (!$prefs['methods']['email'] && !$prefs['methods']['sms'])) {
        $db->query("UPDATE notifications SET status='sent', sent_at = NOW() WHERE id = ?", [$n['id']]);
        $sent++;
    }
}

log_line("Processed: $processed, Marked sent: $sent, Errors: $errors");

// If called from browser, show a small page
if (php_sapi_name() !== 'cli') {
    echo '<div style="padding:16px;font-family:system-ui,Segoe UI,Arial">';
    echo '<h3>Procesare notificări</h3>';
    echo '<p>Processed: '.(int)$processed.' | Marked sent: '.(int)$sent.' | Errors: '.(int)$errors.'</p>';
    echo '<a href="'.BASE_URL.'notifications" style="display:inline-block;margin-top:8px">Înapoi</a>';
    echo '</div>';
}
