<?php
// modules/notifications/services/NotificationGenerator.php

require_once __DIR__ . '/../../notifications/models/Notification.php';
@require_once __DIR__ . '/../../insurance/models/Insurance.php';
@require_once __DIR__ . '/../../maintenance/models/Maintenance.php';
require_once __DIR__ . '/../../../core/Database.php';

class NotificationGenerator {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: Database::getInstance();
    }

    public function runForCompany($companyId, $userIdForPrefs = null) {
        // Setăm contextul tenant și citim preferințele
        if ($companyId) {
            try { $this->db->setTenantDatabaseByCompanyId((int)$companyId); } catch (Throwable $e) { /* ignore */ }
        }

        $daysBefore = 30;
        if ($userIdForPrefs) {
            try {
                $row = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = ?", ['notifications_prefs_user_' . $userIdForPrefs]);
                if ($row && !empty($row['setting_value'])) {
                    $prefs = json_decode($row['setting_value'], true);
                    if (isset($prefs['daysBefore'])) $daysBefore = max(0, (int)$prefs['daysBefore']);
                }
            } catch (Throwable $e) {}
        }

        $created = 0;
        // Insurance
        try {
            $insuranceModel = class_exists('Insurance') ? new Insurance() : null;
            $expiringInsurance = [];
            if ($insuranceModel && method_exists($insuranceModel, 'getExpiringInsurance')) {
                $expiringInsurance = $insuranceModel->getExpiringInsurance($daysBefore);
            } elseif ($insuranceModel && method_exists($insuranceModel, 'getExpiring')) {
                $expiringInsurance = $insuranceModel->getExpiring($daysBefore);
            } else {
                $expiringInsurance = $this->db->fetchAllOn('insurance',
                    "SELECT i.*, v.registration_number AS license_plate, DATEDIFF(i.expiry_date, CURDATE()) AS days_until_expiry, i.insurance_type
                     FROM insurance i LEFT JOIN vehicles v ON i.vehicle_id = v.id
                     WHERE i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$daysBefore} DAY)
                     ORDER BY i.expiry_date ASC", []);
            }
            foreach ($expiringInsurance as $ins) {
                $days = isset($ins['days_until_expiry']) ? (int)$ins['days_until_expiry'] : 30;
                $ok = Notification::createInsuranceExpiryNotification($ins['id'], $ins['license_plate'] ?? 'Vehicul', $ins['insurance_type'] ?? 'asigurare', $days, $companyId);
                if ($ok) { $created++; }
            }
        } catch (Throwable $e) {}

        // Maintenance
        try {
            $maintenanceModel = class_exists('Maintenance') ? new Maintenance() : null;
            $dueMaintenance = [];
            if ($maintenanceModel && method_exists($maintenanceModel, 'getDueMaintenance')) {
                $dueMaintenance = $maintenanceModel->getDueMaintenance();
            } else {
                $dueMaintenance = $this->db->fetchAllOn('maintenance',
                    "SELECT m.*, v.registration_number AS license_plate, 'mentenanță' AS maintenance_type
                     FROM maintenance m LEFT JOIN vehicles v ON m.vehicle_id = v.id
                     WHERE m.status IN ('scheduled','in_progress') AND (
                        (m.next_service_date IS NOT NULL AND m.next_service_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)) OR
                        (m.next_service_mileage IS NOT NULL AND v.current_mileage IS NOT NULL AND v.current_mileage >= (m.next_service_mileage - 2000))
                     ) ORDER BY COALESCE(m.next_service_date, '9999-12-31') ASC, m.next_service_mileage ASC", []);
            }
            foreach ($dueMaintenance as $m) {
                $ok = Notification::createMaintenanceNotification($m['vehicle_id'], $m['license_plate'] ?? 'Vehicul', $m['maintenance_type'] ?? 'mentenanță', $companyId);
                if ($ok) { $created++; }
            }
        } catch (Throwable $e) {}

        // Documents
        try {
            $docs = $this->db->fetchAllOn('documents',
                "SELECT d.*, v.registration_number AS license_plate, d.document_type, DATEDIFF(d.expiry_date, CURDATE()) AS days_until_expiry
                 FROM documents d LEFT JOIN vehicles v ON d.vehicle_id = v.id
                 WHERE d.status = 'active' AND d.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$daysBefore} DAY)
                 ORDER BY d.expiry_date ASC", []);
            foreach ($docs as $d) {
                $days = isset($d['days_until_expiry']) ? (int)$d['days_until_expiry'] : $daysBefore;
                $ok = Notification::createDocumentExpiryNotification($d['id'], $d['license_plate'] ?? 'Vehicul', $d['document_type'] ?? 'document', $days, $companyId);
                if ($ok) { $created++; }
            }
        } catch (Throwable $e) {}

        return $created;
    }
}
?>