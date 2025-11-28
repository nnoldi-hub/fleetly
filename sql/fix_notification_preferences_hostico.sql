-- ========================================
-- FIX: Drop and recreate notification_preferences
-- Run this in phpMyAdmin for tenant database
-- ========================================

-- PASUL 1: Șterge tabelele vechi (cu tot cu constraint-uri)
DROP TABLE IF EXISTS notification_queue;
DROP TABLE IF EXISTS notification_preferences;

-- PASUL 2: Recreează notification_preferences (FĂRĂ foreign keys)
CREATE TABLE notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    
    -- Canale activate
    email_enabled TINYINT(1) DEFAULT 1,
    sms_enabled TINYINT(1) DEFAULT 0,
    push_enabled TINYINT(1) DEFAULT 0,
    in_app_enabled TINYINT(1) DEFAULT 1,
    
    -- Tipuri de notificări (JSON array: ["insurance_expiry", "document_expiry", "maintenance_due"])
    enabled_types JSON NULL,
    
    -- Frecvență trimitere
    frequency ENUM('immediate', 'daily', 'weekly') DEFAULT 'immediate',
    
    -- Contact info
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    push_token VARCHAR(512) NULL,
    
    -- Setări
    min_priority ENUM('low', 'medium', 'high') DEFAULT 'low',
    broadcast_to_company TINYINT(1) DEFAULT 0,
    days_before_expiry INT DEFAULT 30,
    
    -- Quiet hours (JSON: {"start":"22:00", "end":"08:00"})
    quiet_hours JSON NULL,
    timezone VARCHAR(50) DEFAULT 'Europe/Bucharest',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_prefs (user_id),
    KEY idx_company (company_id),
    KEY idx_frequency (frequency)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PASUL 3: Recreează notification_queue (FĂRĂ foreign keys)
CREATE TABLE notification_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    
    channel ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    
    recipient_email VARCHAR(255) NULL,
    recipient_phone VARCHAR(20) NULL,
    recipient_push_token VARCHAR(512) NULL,
    
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    
    status ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    
    scheduled_at TIMESTAMP NULL,
    processed_at TIMESTAMP NULL,
    
    error_message TEXT NULL,
    last_attempt_at TIMESTAMP NULL,
    metadata JSON DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_status_scheduled (status, scheduled_at),
    KEY idx_notification (notification_id),
    KEY idx_company (company_id),
    KEY idx_user (user_id),
    KEY idx_channel (channel),
    KEY idx_processed_at (processed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PASUL 4: Verificare
SELECT 'Tables recreated successfully!' as status;
SHOW TABLES LIKE 'notification%';
SELECT COUNT(*) as row_count FROM notification_preferences;
