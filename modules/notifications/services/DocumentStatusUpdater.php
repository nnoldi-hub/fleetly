<?php
// modules/notifications/services/DocumentStatusUpdater.php

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../models/NotificationLog.php';

/**
 * Service pentru actualizare automată status documente/asigurări/mentenanță
 * Rulează zilnic via cron pentru a marca itemele ca expired/expiring_soon
 */
class DocumentStatusUpdater {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Actualizează toate statusurile (documente, asigurări, mentenanță)
     * @return array Statistici cu ce s-a actualizat
     */
    public function updateAllStatuses() {
        $stats = [
            'documents' => $this->updateDocumentsStatus(),
            'insurance' => $this->updateInsuranceStatus(),
            'maintenance' => $this->updateMaintenanceStatus()
        ];
        
        // Log summary
        NotificationLog::log('status_update', 'success', [
            'documents_updated' => $stats['documents']['updated'] ?? 0,
            'insurance_updated' => $stats['insurance']['updated'] ?? 0,
            'maintenance_updated' => $stats['maintenance']['updated'] ?? 0,
            'total_updated' => 
                ($stats['documents']['updated'] ?? 0) + 
                ($stats['insurance']['updated'] ?? 0) + 
                ($stats['maintenance']['updated'] ?? 0)
        ], null);
        
        return $stats;
    }
    
    /**
     * Actualizează status documente (expired/expiring_soon/active)
     * @return array ['updated' => N, 'expired' => N, 'expiring_soon' => N]
     */
    public function updateDocumentsStatus() {
        try {
            // Check dacă coloana expiry_status există
            $hasColumn = $this->checkColumnExists('documents', 'expiry_status');
            
            if (!$hasColumn) {
                // Adaugă coloana dacă lipsește
                $this->db->query("ALTER TABLE documents ADD COLUMN expiry_status VARCHAR(20) DEFAULT 'active'");
                $this->db->query("CREATE INDEX IF NOT EXISTS idx_documents_expiry_status ON documents(expiry_status)");
            }
            
            // Calculăm days_before din preferințele globale (default 30)
            $daysBefore = 30; // Default
            try {
                $prefsRow = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'global_days_before_expiry'");
                if ($prefsRow && !empty($prefsRow['setting_value'])) {
                    $daysBefore = (int)$prefsRow['setting_value'];
                }
            } catch (Throwable $e) {
                // Ignore, folosim default
            }
            
            // UPDATE status pentru documente
            $sql = "UPDATE documents 
                    SET expiry_status = 
                        CASE 
                            WHEN expiry_date < CURDATE() THEN 'expired'
                            WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY) THEN 'expiring_soon'
                            ELSE 'active'
                        END
                    WHERE expiry_date IS NOT NULL";
            
            $this->db->query($sql, [$daysBefore]);
            $updated = $this->db->rowCount();
            
            // Count per status
            $counts = $this->db->fetch("
                SELECT 
                    SUM(CASE WHEN expiry_status = 'expired' THEN 1 ELSE 0 END) as expired,
                    SUM(CASE WHEN expiry_status = 'expiring_soon' THEN 1 ELSE 0 END) as expiring_soon,
                    SUM(CASE WHEN expiry_status = 'active' THEN 1 ELSE 0 END) as active
                FROM documents
                WHERE expiry_date IS NOT NULL
            ");
            
            return [
                'success' => true,
                'updated' => $updated,
                'expired' => (int)($counts['expired'] ?? 0),
                'expiring_soon' => (int)($counts['expiring_soon'] ?? 0),
                'active' => (int)($counts['active'] ?? 0)
            ];
            
        } catch (Throwable $e) {
            NotificationLog::log('status_update', 'error', [
                'type' => 'documents',
                'error' => $e->getMessage()
            ], null, $e->getMessage());
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Actualizează status asigurări
     * @return array ['updated' => N, 'expired' => N, 'expiring_soon' => N]
     */
    public function updateInsuranceStatus() {
        try {
            // Check dacă tabelul insurance există
            $hasTable = $this->checkTableExists('insurance');
            if (!$hasTable) {
                return ['success' => true, 'updated' => 0, 'note' => 'Table insurance does not exist'];
            }
            
            // Check coloană expiry_status
            $hasColumn = $this->checkColumnExists('insurance', 'expiry_status');
            if (!$hasColumn) {
                $this->db->query("ALTER TABLE insurance ADD COLUMN expiry_status VARCHAR(20) DEFAULT 'active'");
                $this->db->query("CREATE INDEX IF NOT EXISTS idx_insurance_expiry_status ON insurance(expiry_status)");
            }
            
            $daysBefore = 30; // Default
            try {
                $prefsRow = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'global_days_before_expiry'");
                if ($prefsRow && !empty($prefsRow['setting_value'])) {
                    $daysBefore = (int)$prefsRow['setting_value'];
                }
            } catch (Throwable $e) {}
            
            // UPDATE status
            $sql = "UPDATE insurance 
                    SET expiry_status = 
                        CASE 
                            WHEN expiry_date < CURDATE() THEN 'expired'
                            WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY) THEN 'expiring_soon'
                            ELSE 'active'
                        END
                    WHERE expiry_date IS NOT NULL";
            
            $this->db->query($sql, [$daysBefore]);
            $updated = $this->db->rowCount();
            
            // Counts
            $counts = $this->db->fetch("
                SELECT 
                    SUM(CASE WHEN expiry_status = 'expired' THEN 1 ELSE 0 END) as expired,
                    SUM(CASE WHEN expiry_status = 'expiring_soon' THEN 1 ELSE 0 END) as expiring_soon,
                    SUM(CASE WHEN expiry_status = 'active' THEN 1 ELSE 0 END) as active
                FROM insurance
                WHERE expiry_date IS NOT NULL
            ");
            
            return [
                'success' => true,
                'updated' => $updated,
                'expired' => (int)($counts['expired'] ?? 0),
                'expiring_soon' => (int)($counts['expiring_soon'] ?? 0),
                'active' => (int)($counts['active'] ?? 0)
            ];
            
        } catch (Throwable $e) {
            NotificationLog::log('status_update', 'error', [
                'type' => 'insurance',
                'error' => $e->getMessage()
            ], null, $e->getMessage());
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Actualizează status mentenanță (pentru scadențe)
     * Notă: maintenance nu are expiry_date standard, dar verificăm next_service_date sau due_date
     * @return array ['updated' => N]
     */
    public function updateMaintenanceStatus() {
        try {
            // Check dacă tabelul maintenance există
            $hasTable = $this->checkTableExists('maintenance');
            if (!$hasTable) {
                return ['success' => true, 'updated' => 0, 'note' => 'Table maintenance does not exist'];
            }
            
            // Check dacă există coloana next_service_date sau due_date
            $hasNextService = $this->checkColumnExists('maintenance', 'next_service_date');
            $hasDueDate = $this->checkColumnExists('maintenance', 'due_date');
            
            if (!$hasNextService && !$hasDueDate) {
                return ['success' => true, 'updated' => 0, 'note' => 'No date column found in maintenance'];
            }
            
            $dateColumn = $hasNextService ? 'next_service_date' : 'due_date';
            
            // Verificăm dacă există status column (pentru a marca ca 'due' sau 'overdue')
            $hasStatus = $this->checkColumnExists('maintenance', 'status');
            
            if (!$hasStatus) {
                // Dacă nu există coloană status, nu putem marca
                return ['success' => true, 'updated' => 0, 'note' => 'No status column in maintenance'];
            }
            
            // Marchează ca 'due' sau 'overdue' (nu suprascrie 'completed')
            $daysBefore = 7; // Pentru mentenanță, alert mai devreme
            
            $sql = "UPDATE maintenance 
                    SET status = 
                        CASE 
                            WHEN $dateColumn < CURDATE() AND status != 'completed' THEN 'overdue'
                            WHEN $dateColumn <= DATE_ADD(CURDATE(), INTERVAL ? DAY) AND status != 'completed' THEN 'due'
                            ELSE status
                        END
                    WHERE $dateColumn IS NOT NULL AND status != 'completed'";
            
            $this->db->query($sql, [$daysBefore]);
            $updated = $this->db->rowCount();
            
            return [
                'success' => true,
                'updated' => $updated
            ];
            
        } catch (Throwable $e) {
            NotificationLog::log('status_update', 'error', [
                'type' => 'maintenance',
                'error' => $e->getMessage()
            ], null, $e->getMessage());
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Helper: Check dacă o coloană există într-un tabel
     */
    private function checkColumnExists($table, $column) {
        try {
            $result = $this->db->fetch("SHOW COLUMNS FROM $table LIKE ?", [$column]);
            return !empty($result);
        } catch (Throwable $e) {
            return false;
        }
    }
    
    /**
     * Helper: Check dacă un tabel există
     */
    private function checkTableExists($table) {
        try {
            $result = $this->db->fetch("SHOW TABLES LIKE ?", [$table]);
            return !empty($result);
        } catch (Throwable $e) {
            return false;
        }
    }
    
    /**
     * Get raport detaliat cu itemele expirate/în expirare
     * @return array
     */
    public function getExpiryReport() {
        $report = [
            'documents' => [],
            'insurance' => [],
            'maintenance' => []
        ];
        
        // Documents expiring soon
        try {
            $report['documents'] = $this->db->fetchAll("
                SELECT id, type, expiry_date, expiry_status, vehicle_id
                FROM documents
                WHERE expiry_status IN ('expired', 'expiring_soon')
                ORDER BY expiry_date ASC
                LIMIT 100
            ");
        } catch (Throwable $e) {}
        
        // Insurance expiring soon
        try {
            if ($this->checkTableExists('insurance')) {
                $report['insurance'] = $this->db->fetchAll("
                    SELECT id, type, expiry_date, expiry_status, vehicle_id
                    FROM insurance
                    WHERE expiry_status IN ('expired', 'expiring_soon')
                    ORDER BY expiry_date ASC
                    LIMIT 100
                ");
            }
        } catch (Throwable $e) {}
        
        // Maintenance due/overdue
        try {
            if ($this->checkTableExists('maintenance')) {
                $dateCol = $this->checkColumnExists('maintenance', 'next_service_date') ? 'next_service_date' : 'due_date';
                $report['maintenance'] = $this->db->fetchAll("
                    SELECT id, type, $dateCol as due_date, status, vehicle_id
                    FROM maintenance
                    WHERE status IN ('due', 'overdue')
                    ORDER BY $dateCol ASC
                    LIMIT 100
                ");
            }
        } catch (Throwable $e) {}
        
        return $report;
    }
}
?>
