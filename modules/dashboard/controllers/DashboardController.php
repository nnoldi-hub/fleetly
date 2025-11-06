<?php
// modules/dashboard/controllers/DashboardController.php

class DashboardController extends Controller {
    
    public function index() {
        // Require authentication
        Auth::getInstance()->requireAuth();
        
        // Gather tenant-aware stats
        $db = Database::getInstance();
        // Vehicles (exclude deleted)
        try {
            $v = $db->fetchOn('vehicles', "SELECT COUNT(*) AS c FROM vehicles WHERE status <> 'deleted'");
            $totalVehicles = isset($v['c']) ? (int)$v['c'] : 0;
        } catch (Throwable $e) { $totalVehicles = 0; }

        // Active drivers
        try {
            $d = $db->fetchOn('drivers', "SELECT COUNT(*) AS c FROM drivers WHERE status = 'active'");
            $activeDrivers = isset($d['c']) ? (int)$d['c'] : 0;
        } catch (Throwable $e) { $activeDrivers = 0; }

        // Scheduled maintenance
        try {
            $m = $db->fetchOn('maintenance', "SELECT COUNT(*) AS c FROM maintenance WHERE status = 'scheduled'");
            $scheduledMaintenance = isset($m['c']) ? (int)$m['c'] : 0;
        } catch (Throwable $e) { $scheduledMaintenance = 0; }

        // Active alerts (pending)
        try {
            $n = $db->fetchOn('notifications', "SELECT COUNT(*) AS c FROM notifications WHERE status = 'pending'");
            $activeAlerts = isset($n['c']) ? (int)$n['c'] : 0;
        } catch (Throwable $e) { $activeAlerts = 0; }

        // Company plan and limits
        $subscription = null;
        try {
            $current = Auth::getInstance()->user();
            if ($current && isset($current->company_id)) {
                $company = (new Company())->getById($current->company_id);
                $usedUsers = (new User())->countByCompany($current->company_id);
                $subscription = [
                    'type' => $company->subscription_type ?? 'basic',
                    'max_users' => (int)($company->max_users ?? 0),
                    'used_users' => (int)$usedUsers,
                    'max_vehicles' => (int)($company->max_vehicles ?? 0),
                    'used_vehicles' => (int)$totalVehicles,
                ];
            }
        } catch (Throwable $e) { /* ignore */ }

        $data = [
            'title' => 'Dashboard - Fleet Management',
            'page' => 'dashboard',
            'stats' => [
                'total_vehicles' => $totalVehicles,
                'active_drivers' => $activeDrivers,
                'scheduled_maintenance' => $scheduledMaintenance,
                'active_alerts' => $activeAlerts,
            ],
            'subscription' => $subscription
        ];
        
        $this->render('index', $data);
    }
}
