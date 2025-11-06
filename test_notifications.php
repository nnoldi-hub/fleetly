<?php
// Quick dev test for notifications flows (insert, unread count, mark-read, cleanup)
// Usage: open in browser or run via PHP CLI

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/model.php';
require_once __DIR__ . '/modules/notifications/models/notification.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Check columns presence
    $cols = $pdo->query("SHOW COLUMNS FROM notifications")->fetchAll(PDO::FETCH_COLUMN);
    $needed = ['user_id','is_read','related_type','action_url'];
    $missing = array_diff($needed, $cols);
    if (!empty($missing)) {
        echo "Missing columns in notifications table: ".implode(', ', $missing)."\n";
        echo "Run migration in sql/migrations/2025_11_05_001_add_user_and_read_columns_to_notifications.sql\n";
        exit(1);
    }

    $model = new Notification();
    $userId = (int)$_SESSION['user_id'];

    $before = $model->getUnreadCount($userId);
    echo "Unread before: {$before}\n";

    // Create a test notification
    $id = $model->create([
        'user_id' => $userId,
        'type' => 'general',
        'title' => 'Test notification',
        'message' => 'This is a test notification inserted by test_notifications.php',
        'priority' => 'medium',
        'related_id' => null,
        'related_type' => null,
        'action_url' => null,
    ]);

    echo "Inserted notification id: {$id}\n";

    $afterInsert = $model->getUnreadCount($userId);
    echo "Unread after insert: {$afterInsert}\n";

    // Mark as read
    $model->markAsRead($id);

    $afterRead = $model->getUnreadCount($userId);
    echo "Unread after mark-as-read: {$afterRead}\n";

    // Cleanup
    $model->delete($id);
    echo "Deleted test notification.\n";

    // Stats simple probes
    $pdo = $db->getConnection();
    $cols = $pdo->query("SHOW COLUMNS FROM notifications")->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns: " . json_encode(array_column($cols,'Field')) . "\n";
    $probe1 = $pdo->query("SELECT SUM(CASE WHEN `is_read`=0 THEN 1 ELSE 0 END) AS total_unread FROM notifications WHERE `user_id`={$userId}")->fetch(PDO::FETCH_ASSOC);
    echo "Probe1: ".json_encode($probe1)."\n";
    $stats = $model->getStatistics($userId);
    echo "Stats: ".json_encode($stats)."\n";

    echo "OK\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage() . "\n";
}
