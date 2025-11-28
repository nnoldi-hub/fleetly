<?php
/**
 * Send Emails from Queue - Script simplu
 * Accesează: https://management.nks-cables.ro/send_emails_now.php
 */

// Prevent any output before JSON
ob_start();

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/core/Database.php';
    require_once __DIR__ . '/core/Mailer.php';
    
    $db = Database::getInstance();
    
    // Găsește notificările pending în coadă
    $sql = "SELECT * FROM notification_queue 
            WHERE status = 'pending' 
            AND attempts < max_attempts
            ORDER BY created_at ASC 
            LIMIT 50";
    
    $queueItems = $db->fetchAllOn('notification_queue', $sql);
    
    if (empty($queueItems)) {
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Nu există email-uri în coadă pentru trimitere.',
            'sent' => 0,
            'pending' => 0
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    $sent = 0;
    $failed = 0;
    $errors = [];
    $mailer = new Mailer();
    
    foreach ($queueItems as $item) {
        try {
            if (empty($item['recipient_email'])) {
                throw new Exception('Email recipient lipsește');
            }
            
            // Trimite email
            $result = $mailer->send(
                $item['recipient_email'],
                $item['subject'] ?? 'Notificare Fleet Management',
                $item['message']
            );
            
            if ($result) {
                // Marchează ca trimis
                $db->queryOn('notification_queue',
                    "UPDATE notification_queue 
                     SET status = 'sent', processed_at = NOW() 
                     WHERE id = ?",
                    [$item['id']]
                );
                $sent++;
            } else {
                throw new Exception('Mailer a returnat false');
            }
            
        } catch (Throwable $e) {
            // Incrementează attempts
            $errorMsg = $e->getMessage();
            $errors[] = "Queue ID {$item['id']}: $errorMsg";
            
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
    
    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'message' => "Procesare completă: $sent trimise, $failed eșuate",
        'sent' => $sent,
        'failed' => $failed,
        'total' => count($queueItems),
        'errors' => $errors
    ], JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Eroare: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>
