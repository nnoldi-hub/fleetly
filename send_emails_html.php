<?php
/**
 * Send Emails - HTML Output
 * Simple email sender with HTML output
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>📧 Trimitere Email-uri din Coadă</h1>";
echo "<p><strong>Data/Ora:</strong> " . date('Y-m-d H:i:s') . "</p><hr>";

try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/core/Database.php';
    require_once __DIR__ . '/core/Mailer.php';
    
    echo "<p>✅ Config și database încărcate</p>";
    
    $db = Database::getInstance();
    echo "<p>✅ Database conectată</p>";
    
    // Găsește notificările pending
    $sql = "SELECT * FROM notification_queue 
            WHERE status = 'pending' 
            AND attempts < max_attempts
            ORDER BY created_at ASC 
            LIMIT 50";
    
    $queueItems = $db->fetchAllOn('notification_queue', $sql);
    
    echo "<p>📊 Găsite în coadă: <strong>" . count($queueItems) . "</strong> email-uri pending</p>";
    
    if (empty($queueItems)) {
        echo "<p class='warning'>⚠️ Nu există email-uri de trimis</p>";
        exit;
    }
    
    echo "<hr><h2>Procesare...</h2>";
    
    $sent = 0;
    $failed = 0;
    $mailer = new Mailer();
    
    foreach ($queueItems as $item) {
        echo "<p>📧 Procesez Queue ID: {$item['id']} → {$item['recipient_email']}...</p>";
        
        try {
            if (empty($item['recipient_email'])) {
                throw new Exception('Email lipsește');
            }
            
            $result = $mailer->send(
                $item['recipient_email'],
                $item['subject'] ?? 'Notificare Fleet Management',
                $item['message']
            );
            
            if ($result) {
                $db->queryOn('notification_queue',
                    "UPDATE notification_queue 
                     SET status = 'sent', last_attempt_at = NOW() 
                     WHERE id = ?",
                    [$item['id']]
                );
                echo "<p style='color: green'>✅ Trimis cu succes!</p>";
                $sent++;
            } else {
                throw new Exception('Mailer returned false');
            }
            
        } catch (Throwable $e) {
            $errorMsg = $e->getMessage();
            echo "<p style='color: red'>❌ Eroare: $errorMsg</p>";
            
            $db->queryOn('notification_queue',
                "UPDATE notification_queue 
                 SET attempts = attempts + 1, 
                     error_message = ?,
                     last_attempt_at = NOW(),
                     status = IF(attempts + 1 >= max_attempts, 'failed', 'pending')
                 WHERE id = ?",
                [$errorMsg, $item['id']]
            );
            $failed++;
        }
    }
    
    echo "<hr>";
    echo "<h2>📊 Rezultate Finale</h2>";
    echo "<p>✅ <strong>Trimise:</strong> $sent</p>";
    echo "<p>❌ <strong>Eșuate:</strong> $failed</p>";
    echo "<p>📦 <strong>Total procesate:</strong> " . count($queueItems) . "</p>";
    
    if ($sent > 0) {
        echo "<p style='color: green; font-size: 18px; font-weight: bold'>🎉 EMAIL-URI TRIMISE CU SUCCES!</p>";
        echo "<p>Verifică inbox-ul la: <strong>{$queueItems[0]['recipient_email']}</strong></p>";
    }
    
} catch (Throwable $e) {
    echo "<p style='color: red'><strong>❌ EROARE:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>File: " . $e->getFile() . "\nLine: " . $e->getLine() . "</pre>";
}

echo "<hr>";
echo "<p><a href='notifications'>← Înapoi la Notificări</a></p>";
?>
