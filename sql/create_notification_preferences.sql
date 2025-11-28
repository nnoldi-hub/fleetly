-- ========================================
-- Create notification_preferences table
-- Rulează acest script în baza de date TENANT
-- ========================================

USE wclsgzyf_fm_tenant_1;

-- 1. Tabel notification_preferences
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    
    -- Canale activate
    email_enabled TINYINT(1) DEFAULT 1,
    sms_enabled TINYINT(1) DEFAULT 0,
    push_enabled TINYINT(1) DEFAULT 0,
    in_app_enabled TINYINT(1) DEFAULT 1,
    
    -- Tipuri de notificări activate (JSON array)
    enabled_types JSON NULL,
    
    -- Frecvență trimitere
    frequency ENUM('immediate', 'daily', 'weekly') DEFAULT 'immediate',
    
    -- Contact info (override pentru user.email/phone)
    email VARCHAR(255) NULL COMMENT 'Override email (dacă diferit de users.email)',
    phone VARCHAR(20) NULL COMMENT 'Override telefon pentru SMS',
    push_token VARCHAR(512) NULL COMMENT 'Firebase/OneSignal token pentru push',
    
    -- Prioritate minimă pentru notificări
    min_priority ENUM('low', 'medium', 'high') DEFAULT 'low',
    
    -- Broadcast la toată compania (doar pentru admin/manager)
    broadcast_to_company TINYINT(1) DEFAULT 0,
    
    -- Zile înainte de expirare pentru alertă
    days_before_expiry INT DEFAULT 30,
    
    -- Quiet hours (JSON: {"start":"22:00", "end":"08:00"})
    quiet_hours JSON NULL,
    
    -- Timezone pentru schedulare
    timezone VARCHAR(50) DEFAULT 'Europe/Bucharest',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_prefs (user_id),
    KEY idx_company (company_id),
    KEY idx_frequency (frequency),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel notification_queue (pentru procesare asincronă - opțional, dar recomandat)
CREATE TABLE IF NOT EXISTS notification_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL COMMENT 'FK la notifications.id',
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    
    -- Canal de trimitere
    channel ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    
    -- Date necesare pentru trimitere
    recipient_email VARCHAR(255) NULL,
    recipient_phone VARCHAR(20) NULL,
    recipient_push_token VARCHAR(512) NULL,
    
    subject VARCHAR(255) NULL COMMENT 'Pentru email/push',
    message TEXT NOT NULL,
    
    -- Status procesare
    status ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    
    -- Schedulare (pentru frequency=daily/weekly)
    scheduled_at TIMESTAMP NULL COMMENT 'Când să fie trimisă',
    processed_at TIMESTAMP NULL COMMENT 'Când a fost procesată',
    
    -- Errori
    error_message TEXT NULL,
    last_attempt_at TIMESTAMP NULL,
    
    -- Metadata (JSON pentru date custom)
    metadata JSON DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_status_scheduled (status, scheduled_at),
    KEY idx_notification (notification_id),
    KEY idx_company (company_id),
    KEY idx_user (user_id),
    KEY idx_channel (channel),
    KEY idx_processed_at (processed_at),
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificare
SELECT 'notification_preferences created' as status, COUNT(*) as row_count FROM notification_preferences;
SELECT 'notification_queue created' as status, COUNT(*) as row_count FROM notification_queue;
