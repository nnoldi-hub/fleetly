<?php
/**
 * Reset Stuck Emails
 * Resetează email-urile blocate în status "processing"
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔄 Resetare Email-uri Blocate</h1>";
echo "<p><strong>Data/Ora:</strong> " . date('Y-m-d H:i:s') . "</p><hr>";

try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/core/Database.php';
    
    $db = Database::getInstance();
    
    // Găsește email-uri blocate în "processing"
    $sql = "SELECT id, recipient_email, subject, attempts, created_at, last_attempt_at
            FROM notification_queue 
            WHERE status = 'processing'";
    
    $stuck = $db->fetchAllOn('notification_queue', $sql);
    
    echo "<h2>📊 Email-uri Găsite în Status 'processing'</h2>";
    echo "<p>Total: <strong>" . count($stuck) . "</strong></p>";
    
    if (empty($stuck)) {
        echo "<p>✅ Nu există email-uri blocate!</p>";
        echo "<p><a href='check_email_history.php'>← Înapoi la Istoric</a></p>";
        exit;
    }
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background: #eee;'>
            <th>ID</th>
            <th>Email</th>
            <th>Subject</th>
            <th>Încercări</th>
            <th>Creat</th>
            <th>Ultima Încercare</th>
          </tr>";
    
    foreach ($stuck as $item) {
        echo "<tr>";
        echo "<td>{$item['id']}</td>";
        echo "<td>{$item['recipient_email']}</td>";
        echo "<td>{$item['subject']}</td>";
        echo "<td>{$item['attempts']}/3</td>";
        echo "<td>" . date('d.m H:i', strtotime($item['created_at'])) . "</td>";
        echo "<td>" . ($item['last_attempt_at'] ? date('d.m H:i', strtotime($item['last_attempt_at'])) : '-') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // RESETARE
    echo "<hr><h2>🔧 Resetare...</h2>";
    
    $updateSql = "UPDATE notification_queue 
                  SET status = 'pending',
                      error_message = 'Reset from processing - stuck queue'
                  WHERE status = 'processing'";
    
    $stmt = $db->queryOn('notification_queue', $updateSql);
    $affectedRows = $stmt->rowCount();
    
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ {$affectedRows} email-uri RESETATE la status 'pending'</p>";
    
    echo "<hr>";
    echo "<p><strong>🚀 Următorii Pași:</strong></p>";
    echo "<ol>";
    echo "<li><a href='send_emails_html.php' style='font-weight: bold; color: blue;'>TRIMITE EMAIL-URILE ACUM</a></li>";
    echo "<li><a href='check_email_history.php'>Vezi istoricul actualizat</a></li>";
    echo "</ol>";
    
} catch (Throwable $e) {
    echo "<p style='color: red'><strong>❌ EROARE:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>File: " . $e->getFile() . "\nLine: " . $e->getLine() . "</pre>";
}

echo "<hr>";
echo "<p><a href='notifications'>← Înapoi la Notificări</a></p>";
?>
