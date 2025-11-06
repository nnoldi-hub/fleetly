<?php
// api/notifications.php - API simplu pentru notificări

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Simulare notificări pentru demo
$notifications = [
    [
        'id' => 1,
        'title' => 'Permis Expirat',
        'message' => 'Permisul de conducere pentru Popescu Ion expiră în 5 zile',
        'type' => 'license_expiry',
        'priority' => 'high',
        'created_at' => date('d.m.Y H:i', strtotime('-2 hours'))
    ],
    [
        'id' => 2,
        'title' => 'Întreținere Programată',
        'message' => 'Vehiculul B-123-ABC necesită service în următoarele 7 zile',
        'type' => 'maintenance_due',
        'priority' => 'medium',
        'created_at' => date('d.m.Y H:i', strtotime('-1 day'))
    ]
];

// Returnează datele JSON
echo json_encode([
    'success' => true,
    'count' => count($notifications),
    'notifications' => $notifications
]);
