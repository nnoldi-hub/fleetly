<?php
class ApiController extends Controller {
    public function notifications() {
        // Return hardcoded notifications for now (production: fetch from DB)
        header('Content-Type: application/json');
        
        $notifications = [
            [
                'id' => 1,
                'title' => 'Permis Expirat',
                'message' => 'Permisul de conducere pentru Popescu Ion expira in 5 zile',
                'type' => 'license_expiry',
                'priority' => 'high',
                'created_at' => date('d.m.Y H:i', strtotime('-2 hours'))
            ],
            [
                'id' => 2,
                'title' => 'Intretinere Programata',
                'message' => 'Vehiculul B-123-ABC necesita service in urmatoarele 7 zile',
                'type' => 'maintenance_due',
                'priority' => 'medium',
                'created_at' => date('d.m.Y H:i', strtotime('-1 day'))
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'count' => count($notifications),
            'notifications' => $notifications
        ]);
        exit;
    }
}
