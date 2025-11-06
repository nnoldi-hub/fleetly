<?php
// Simple smoke test to create a few notifications and exercise immediate + background send
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../modules/notifications/models/notification.php';

$db = Database::getInstance();

function println($m){ echo $m . PHP_EOL; }

println("== Notifications smoke test ==");

$examples = [
    [
        'user_id' => 1,
        'type' => 'system',
        'title' => 'Test sistem',
        'message' => 'Acesta este un mesaj de test din scripts/test_notifications.php',
        'priority' => 'low',
        'action_url' => '/notifications'
    ],
    [
        'user_id' => 1,
        'type' => 'maintenance_due',
        'title' => 'Revizie programată',
        'message' => 'Revizie programată în curând',
        'priority' => 'medium',
        'related_type' => 'vehicle',
        'related_id' => 1,
        'action_url' => '/maintenance'
    ]
];

$notif = new Notification();
foreach ($examples as $data) {
    $id = $notif->create($data);
    println("Created notification #{$id} ({$data['type']})");
}

println("Done. If email/SMS is configured and allowed by user prefs, you should receive messages. Otherwise run: php scripts/process_notifications.php");
