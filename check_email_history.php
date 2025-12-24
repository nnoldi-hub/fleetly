<?php
/**
 * Check Email History
 * Verifică toate email-urile trimise/eșuate din coadă
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>📋 Istoric Email-uri din Coadă</h1>";
echo "<p><strong>Data/Ora:</strong> " . date('Y-m-d H:i:s') . "</p><hr>";

try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/core/Database.php';
    
    $db = Database::getInstance();
    
    // Toate email-urile din coadă
    $sql = "SELECT 
                id,
                notification_id,
                recipient_email,
                recipient_phone,
                subject,
                LEFT(message, 100) as message_preview,
                status,
                attempts,
                max_attempts,
                error_message,
                created_at,
                last_attempt_at
            FROM notification_queue 
            ORDER BY created_at DESC 
            LIMIT 50";
    
    $items = $db->fetchAllOn('notification_queue', $sql);
    
    echo "<h2>📊 Statistici</h2>";
    $stats = [
        'total' => count($items),
        'pending' => 0,
        'sent' => 0,
        'failed' => 0
    ];
    
    foreach ($items as $item) {
        $stats[$item['status']]++;
    }
    
    echo "<ul>";
    echo "<li>✅ <strong>Trimise:</strong> {$stats['sent']}</li>";
    echo "<li>⏳ <strong>Pending:</strong> {$stats['pending']}</li>";
    echo "<li>❌ <strong>Eșuate:</strong> {$stats['failed']}</li>";
    echo "<li>📦 <strong>Total:</strong> {$stats['total']}</li>";
    echo "</ul><hr>";
    
    if (empty($items)) {
        echo "<p>⚠️ Nu există email-uri în coadă</p>";
        exit;
    }
    
    echo "<h2>📧 Detalii Email-uri</h2>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #eee;'>
            <th>ID</th>
            <th>Status</th>
            <th>Email</th>
            <th>Subject</th>
            <th>Încercări</th>
            <th>Creat</th>
            <th>Ultima Încercare</th>
            <th>Eroare</th>
          </tr>";
    
    foreach ($items as $item) {
        $statusColor = [
            'sent' => 'green',
            'pending' => 'orange',
            'failed' => 'red'
        ][$item['status']] ?? 'gray';
        
        $statusIcon = [
            'sent' => '✅',
            'pending' => '⏳',
            'failed' => '❌'
        ][$item['status']] ?? '❓';
        
        echo "<tr>";
        echo "<td>{$item['id']}</td>";
        echo "<td style='color: $statusColor; font-weight: bold;'>$statusIcon {$item['status']}</td>";
        echo "<td>{$item['recipient_email']}</td>";
        echo "<td>{$item['subject']}</td>";
        echo "<td>{$item['attempts']}/{$item['max_attempts']}</td>";
        echo "<td>" . date('d.m H:i', strtotime($item['created_at'])) . "</td>";
        echo "<td>" . ($item['last_attempt_at'] ? date('d.m H:i', strtotime($item['last_attempt_at'])) : '-') . "</td>";
        echo "<td style='color: red; font-size: 12px;'>" . ($item['error_message'] ?: '-') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Verifică și notificările
    echo "<hr><h2>🔔 Notificări Generate</h2>";
    
    $notifSql = "SELECT 
                    id,
                    user_id,
                    type,
                    priority,
                    title,
                    LEFT(message, 80) as message_preview,
                    is_read,
                    created_at
                FROM notifications 
                ORDER BY created_at DESC 
                LIMIT 10";
    
    $notifs = $db->fetchAllOn('notifications', $notifSql);
    
    if ($notifs) {
        echo "<p>Total notificări: <strong>" . count($notifs) . "</strong></p>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #eee;'>
                <th>ID</th>
                <th>User</th>
                <th>Tip</th>
                <th>Prioritate</th>
                <th>Titlu</th>
                <th>Creat</th>
                <th>Citit</th>
              </tr>";
        
        foreach ($notifs as $n) {
            echo "<tr>";
            echo "<td>{$n['id']}</td>";
            echo "<td>{$n['user_id']}</td>";
            echo "<td>{$n['type']}</td>";
            echo "<td>{$n['priority']}</td>";
            echo "<td>{$n['title']}</td>";
            echo "<td>" . date('d.m H:i', strtotime($n['created_at'])) . "</td>";
            echo "<td>" . ($n['is_read'] ? '✅' : '❌') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (Throwable $e) {
    echo "<p style='color: red'><strong>❌ EROARE:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>File: " . $e->getFile() . "\nLine: " . $e->getLine() . "</pre>";
}

echo "<hr>";
echo "<p><a href='send_emails_html.php'>🔄 Încearcă să trimiți din nou</a> | ";
echo "<a href='notifications'>← Înapoi la Notificări</a></p>";
?>
