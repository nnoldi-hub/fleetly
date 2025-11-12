-- ========================================
-- Migration: Notification System V2
-- Date: 2025-01-12
-- Description: Arhitectură modernă cu preferences, queue, templates
-- ========================================

-- 1. Tabel notification_preferences (înlocuiește JSON din system_settings)
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
    enabled_types JSON DEFAULT '["document_expiry","insurance_expiry","maintenance_due"]',
    
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
    quiet_hours JSON DEFAULT NULL,
    
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

-- 2. Tabel notification_queue (pentru procesare asincronă)
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

-- 3. Tabel notification_templates (pentru customizare mesaje)
CREATE TABLE IF NOT EXISTS notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL COMMENT 'document_expiry, insurance_expiry, etc.',
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    
    -- Template per canal
    email_subject VARCHAR(255) NULL,
    email_body TEXT NULL,
    sms_body VARCHAR(160) NULL COMMENT 'Max 160 caractere pentru 1 SMS',
    push_title VARCHAR(100) NULL,
    push_body VARCHAR(200) NULL,
    in_app_title VARCHAR(255) NULL,
    in_app_message TEXT NULL,
    
    -- Variabile disponibile (JSON array)
    available_variables JSON DEFAULT '[]' COMMENT 'Ex: ["vehicle_plate", "days_until_expiry"]',
    
    -- Default priority pentru notificări create cu acest template
    default_priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    
    -- Activare/dezactivare
    enabled TINYINT(1) DEFAULT 1,
    
    -- Multi-tenancy: NULL = global (toate companiile), sau specific per company
    company_id INT NULL COMMENT 'NULL = template global, altfel customizare per companie',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_slug_company (slug, company_id),
    KEY idx_slug (slug),
    KEY idx_company (company_id),
    KEY idx_enabled (enabled),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Populare template-uri default (global)
INSERT INTO notification_templates 
(slug, name, description, email_subject, email_body, sms_body, push_title, push_body, in_app_title, in_app_message, available_variables, default_priority) 
VALUES
-- Document Expiry
('document_expiry', 
 'Document în Expirare', 
 'Notificare automată pentru documente care urmează să expire',
 'Document {{document_type}} expiră în {{days_until_expiry}} zile - {{vehicle_plate}}', 
 'Bună ziua,\n\nDocumentul {{document_type}} pentru vehiculul {{vehicle_plate}} va expira în {{days_until_expiry}} zile ({{expiry_date}}).\n\nVă rugăm să îl reînnoiți cât mai curând pentru a evita întreruperea activității.\n\nDetalii: {{action_url}}\n\nCu stimă,\nEchipa Fleet Management',
 'Document {{document_type}} pt {{vehicle_plate}} expiră în {{days_until_expiry}} zile. Reînnoiți urgent!',
 'Document în expirare',
 'Documentul {{document_type}} pentru {{vehicle_plate}} expiră în {{days_until_expiry}} zile',
 'Document în expirare',
 'Documentul {{document_type}} pentru vehiculul {{vehicle_plate}} expiră în {{days_until_expiry}} zile ({{expiry_date}}).',
 '["vehicle_plate", "document_type", "days_until_expiry", "expiry_date", "action_url"]',
 'medium'),

-- Insurance Expiry
('insurance_expiry', 
 'Asigurare în Expirare',
 'Notificare automată pentru polițe de asigurare care urmează să expire',
 'URGENT: Asigurare {{insurance_type}} expiră în {{days_until_expiry}} zile - {{vehicle_plate}}',
 'Bună ziua,\n\nAsigurarea {{insurance_type}} pentru vehiculul {{vehicle_plate}} va expira în {{days_until_expiry}} zile ({{expiry_date}}).\n\n⚠️ ATENȚIE: Circulația fără asigurare validă este ilegală și poate atrage amenzi și suspendarea certificatului de înmatriculare!\n\nVă rugăm să reînnoiți polița URGENT.\n\nDetalii: {{action_url}}\n\nCu stimă,\nEchipa Fleet Management',
 'URGENT! Asigurare {{insurance_type}} pt {{vehicle_plate}} expiră în {{days_until_expiry}} zile. Reînnoiți ACUM!',
 '⚠️ Asigurare expiră',
 'Asigurarea {{insurance_type}} pt {{vehicle_plate}} expiră în {{days_until_expiry}} zile',
 'Asigurare în expirare',
 'Asigurarea {{insurance_type}} pentru vehiculul {{vehicle_plate}} expiră în {{days_until_expiry}} zile ({{expiry_date}}). Reînnoiți urgent!',
 '["vehicle_plate", "insurance_type", "days_until_expiry", "expiry_date", "policy_number", "action_url"]',
 'high'),

-- Maintenance Due
('maintenance_due', 
 'Mentenanță Scadentă',
 'Notificare automată pentru mentenanță programată sau scadentă',
 'Mentenanță necesară: {{maintenance_type}} - {{vehicle_plate}}',
 'Bună ziua,\n\nVehiculul {{vehicle_plate}} necesită mentenanță: {{maintenance_type}}.\n\nScadență: {{due_date}}\nKm parcurși: {{current_km}} km\n\nPentru a menține vehiculul în condiții optime și a preveni defecțiuni majore, vă rugăm să programați serviciul cât mai curând.\n\nProgramare: {{action_url}}\n\nCu stimă,\nEchipa Fleet Management',
 'Mentenanță {{vehicle_plate}}: {{maintenance_type}}. Scadență: {{due_date}}',
 '🔧 Mentenanță scadentă',
 'Vehiculul {{vehicle_plate}} necesită: {{maintenance_type}}',
 'Mentenanță scadentă',
 'Vehiculul {{vehicle_plate}} necesită mentenanță: {{maintenance_type}}. Scadență: {{due_date}}.',
 '["vehicle_plate", "maintenance_type", "due_date", "current_km", "action_url"]',
 'medium'),

-- Generic System Alert
('system_alert',
 'Alertă Sistem',
 'Template generic pentru notificări sistem',
 '{{alert_title}} - Fleet Management',
 'Bună ziua,\n\n{{alert_message}}\n\nCu stimă,\nEchipa Fleet Management',
 '{{alert_message}}',
 '{{alert_title}}',
 '{{alert_message}}',
 '{{alert_title}}',
 '{{alert_message}}',
 '["alert_title", "alert_message", "action_url"]',
 'medium');

-- 5. Actualizare tabel documents (status calculat automat)
-- NOTĂ: Dacă documentele sunt în tenant DB, rulează această migrație per tenant!
-- Pentru flexibilitate, adăugăm doar coloana, fără GENERATED pentru compatibilitate MySQL 5.6
ALTER TABLE documents 
ADD COLUMN IF NOT EXISTS expiry_status VARCHAR(20) DEFAULT 'active' COMMENT 'active, expiring_soon, expired',
ADD INDEX IF NOT EXISTS idx_expiry_status (expiry_status),
ADD INDEX IF NOT EXISTS idx_expiry_date_status (expiry_date, status);

-- 6. Actualizare tabel insurance (dacă există)
ALTER TABLE insurance 
ADD COLUMN IF NOT EXISTS expiry_status VARCHAR(20) DEFAULT 'active' COMMENT 'active, expiring_soon, expired',
ADD INDEX IF NOT EXISTS idx_expiry_status (expiry_status);

-- 7. Actualizare tabel notifications (adăugăm coloană template_id pentru tracking)
ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS template_id INT NULL COMMENT 'FK la notification_templates.id',
ADD COLUMN IF NOT EXISTS rendered_at TIMESTAMP NULL COMMENT 'Când a fost generat mesajul din template',
ADD INDEX IF NOT EXISTS idx_template (template_id);

-- 8. Tabel pentru rate limiting (anti-spam)
CREATE TABLE IF NOT EXISTS notification_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    channel ENUM('email', 'sms', 'push') NOT NULL,
    
    -- Contorizare
    count_current INT DEFAULT 0,
    reset_at TIMESTAMP NOT NULL,
    
    -- Limite configurate
    limit_hourly INT DEFAULT 100 COMMENT 'Max email/oră',
    limit_daily INT DEFAULT 500 COMMENT 'Max email/zi pentru SMS',
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_company_channel (company_id, channel),
    KEY idx_reset_at (reset_at),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Extindere notification_logs cu referință la queue
ALTER TABLE notification_logs 
ADD COLUMN IF NOT EXISTS queue_id INT NULL COMMENT 'FK la notification_queue.id',
ADD INDEX IF NOT EXISTS idx_queue (queue_id);

-- ========================================
-- Post-migration Notes:
-- ========================================
-- 1. Rulează scripts/migrate_notification_preferences.php pentru a muta datele din system_settings
-- 2. Actualizează cron jobs pentru a include process_notifications_queue.php
-- 3. Testează generarea notificărilor cu template-uri noi
-- 4. Monitorizează notification_queue pentru backlog

-- ========================================
-- Rollback Instructions:
-- ========================================
-- DROP TABLE IF EXISTS notification_rate_limits;
-- ALTER TABLE notification_logs DROP COLUMN queue_id;
-- ALTER TABLE notifications DROP COLUMN template_id, DROP COLUMN rendered_at;
-- ALTER TABLE insurance DROP COLUMN expiry_status;
-- ALTER TABLE documents DROP COLUMN expiry_status;
-- DROP TABLE IF EXISTS notification_templates;
-- DROP TABLE IF EXISTS notification_queue;
-- DROP TABLE IF EXISTS notification_preferences;
