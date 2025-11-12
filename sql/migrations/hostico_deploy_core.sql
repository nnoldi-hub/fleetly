-- ============================================
-- HOSTICO DEPLOYMENT: CORE DATABASE
-- Notification System V2 - Core Tables
-- Database: fleet_management (CORE)
-- ============================================
-- IMPORTANT: Select your CORE database in phpMyAdmin LEFT panel BEFORE running this!
-- Database name on Hostico is usually: cpses_XXXXX_fleet (or similar with prefix)
-- DO NOT include USE statement - phpMyAdmin handles this automatically

-- USE fleet_management;  -- COMMENTED OUT for Hostico compatibility

-- ============================================
-- 1. notification_preferences (User Settings)
-- ============================================
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    
    -- Channels enabled
    in_app_enabled TINYINT(1) DEFAULT 1,
    email_enabled TINYINT(1) DEFAULT 0,
    sms_enabled TINYINT(1) DEFAULT 0,
    push_enabled TINYINT(1) DEFAULT 0,
    
    -- Notification types (JSON array)
    enabled_types JSON,
    
    -- Delivery settings
    min_priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
    frequency ENUM('immediate', 'daily', 'weekly') DEFAULT 'immediate',
    days_before_expiry INT DEFAULT 30,
    
    -- Quiet hours (JSON: {start: "22:00", end: "08:00"})
    quiet_hours JSON,
    timezone VARCHAR(50) DEFAULT 'Europe/Bucharest',
    
    -- Contact overrides
    email VARCHAR(100),
    phone VARCHAR(20),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_company (user_id, company_id),
    INDEX idx_user_id (user_id),
    INDEX idx_company_id (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. notification_queue (Async Processing)
-- ============================================
CREATE TABLE IF NOT EXISTS notification_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT,
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    channel ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    
    -- Queue status
    status ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    
    -- Scheduling
    scheduled_at DATETIME,
    sent_at DATETIME,
    
    -- Retry logic
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    last_error TEXT,
    
    -- Payload (JSON)
    payload JSON,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status_scheduled (status, scheduled_at),
    INDEX idx_user_company (user_id, company_id),
    INDEX idx_notification_id (notification_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. notification_templates (Message Templates)
-- ============================================
CREATE TABLE IF NOT EXISTS notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    slug VARCHAR(100) NOT NULL,
    name VARCHAR(200) NOT NULL,
    
    -- Content per channel (with {{variables}})
    email_subject VARCHAR(255),
    email_body TEXT,
    sms_body VARCHAR(320),
    push_title VARCHAR(100),
    push_body VARCHAR(200),
    in_app_title VARCHAR(200),
    in_app_message TEXT,
    
    -- Variables (JSON array: ["vehicle_name", "days_until_expiry"])
    variables JSON,
    
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    is_active TINYINT(1) DEFAULT 1,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_slug_company (slug, company_id),
    INDEX idx_slug (slug),
    INDEX idx_company_id (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. notification_rate_limits (Anti-Spam)
-- ============================================
CREATE TABLE IF NOT EXISTS notification_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    channel ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    period ENUM('hourly', 'daily') NOT NULL,
    
    -- Current usage
    count_current INT DEFAULT 0,
    limit_max INT NOT NULL,
    
    -- Reset tracking
    period_start DATETIME NOT NULL,
    period_end DATETIME NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_company_channel_period (company_id, channel, period),
    INDEX idx_period_end (period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. Default Templates (Global - company_id = NULL)
-- ============================================
INSERT IGNORE INTO notification_templates 
(company_id, slug, name, email_subject, email_body, sms_body, in_app_title, in_app_message, variables, priority) 
VALUES
(NULL, 'document_expiry', 'Expirare Document', 
 'Document {{document_type}} expira in {{days_until_expiry}} zile',
 'Documentul {{document_type}} pentru vehiculul {{vehicle_name}} va expira in {{days_until_expiry}} zile ({{expiry_date}}).\n\nActiune necesara: Va rugam sa reinnnoiti documentul.',
 'Document {{document_type}} pt {{vehicle_name}} expira in {{days_until_expiry}} zile',
 'Document in expirare',
 'Documentul {{document_type}} pentru {{vehicle_name}} expira in {{days_until_expiry}} zile',
 '["document_type", "vehicle_name", "days_until_expiry", "expiry_date"]',
 'high'),

(NULL, 'insurance_expiry', 'Expirare Asigurare',
 'Asigurare {{insurance_type}} expira in {{days_until_expiry}} zile',
 'Asigurarea {{insurance_type}} pentru vehiculul {{vehicle_name}} va expira in {{days_until_expiry}} zile ({{expiry_date}}).\n\nActiune necesara: Contactati asiguratorul pentru reinnoire.',
 'Asigurare {{insurance_type}} pt {{vehicle_name}} expira in {{days_until_expiry}} zile',
 'Asigurare in expirare',
 'Asigurarea {{insurance_type}} pentru {{vehicle_name}} expira in {{days_until_expiry}} zile',
 '["insurance_type", "vehicle_name", "days_until_expiry", "expiry_date"]',
 'critical'),

(NULL, 'maintenance_due', 'Mentenanta Scadenta',
 'Mentenanta pentru {{vehicle_name}} este scadenta',
 'Vehiculul {{vehicle_name}} necesita mentenanta {{maintenance_type}}.\n\nDetalii: Ultima mentenanta la {{last_service_date}}, urmatoarea la {{next_service_date}}.',
 'Mentenanta {{maintenance_type}} pt {{vehicle_name}} scadenta',
 'Mentenanta scadenta',
 'Vehiculul {{vehicle_name}} necesita mentenanta',
 '["vehicle_name", "maintenance_type", "last_service_date", "next_service_date"]',
 'medium'),

(NULL, 'system_alert', 'Alerta Sistem',
 '{{alert_title}}',
 '{{alert_message}}',
 '{{alert_message}}',
 '{{alert_title}}',
 '{{alert_message}}',
 '["alert_title", "alert_message"]',
 'high');

-- ============================================
-- VERIFICATION QUERIES
-- ============================================
-- Run these to verify installation:
-- SELECT COUNT(*) FROM notification_preferences;
-- SELECT COUNT(*) FROM notification_queue;
-- SELECT COUNT(*) FROM notification_templates WHERE company_id IS NULL;
-- SELECT COUNT(*) FROM notification_rate_limits;
-- SHOW TABLES LIKE 'notification%';

-- ============================================
-- END OF CORE DATABASE MIGRATION
-- ============================================
