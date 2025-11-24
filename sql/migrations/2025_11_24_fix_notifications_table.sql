-- ========================================
-- Migration: Fix Notifications Table for V2
-- Date: 2025-11-24
-- Description: Actualizează tabela notifications cu coloanele necesare pentru V2
-- ========================================

-- Verificăm dacă tabela există, dacă nu o creăm
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT 'FK la users.id - destinatar notificare',
    company_id INT NULL COMMENT 'FK la companies.id - pentru multi-tenancy',
    type VARCHAR(50) NOT NULL DEFAULT 'general',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    
    -- Related entities
    related_id INT NULL COMMENT 'ID entitate legată (document, vehicle, etc)',
    related_type VARCHAR(50) NULL COMMENT 'Tipul entității (document, vehicle, insurance, etc)',
    
    -- Action
    action_url VARCHAR(500) NULL COMMENT 'URL pentru acțiune (ex: link la document)',
    
    -- Status și tracking
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL,
    status ENUM('pending', 'sent', 'acknowledged', 'dismissed', 'expired') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    
    -- Template integration (V2)
    template_id INT NULL COMMENT 'FK la notification_templates.id',
    rendered_at TIMESTAMP NULL COMMENT 'Când a fost generat din template',
    
    -- Legacy fields (pentru compatibilitate)
    vehicle_id INT NULL,
    driver_id INT NULL,
    target_date DATE NULL,
    due_date DATE NULL,
    notification_methods JSON NULL,
    recipients JSON NULL,
    acknowledged_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_company (company_id),
    INDEX idx_type_priority (type, priority),
    INDEX idx_status_date (status, created_at),
    INDEX idx_related (related_type, related_id),
    INDEX idx_template (template_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adăugăm coloanele noi dacă tabela exista deja (safe migration)
ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS user_id INT NULL COMMENT 'FK la users.id' AFTER id,
ADD COLUMN IF NOT EXISTS company_id INT NULL COMMENT 'FK la companies.id' AFTER user_id,
ADD COLUMN IF NOT EXISTS is_read TINYINT(1) DEFAULT 0 AFTER status,
ADD COLUMN IF NOT EXISTS read_at TIMESTAMP NULL AFTER is_read,
ADD COLUMN IF NOT EXISTS template_id INT NULL COMMENT 'FK la notification_templates.id' AFTER sent_at,
ADD COLUMN IF NOT EXISTS rendered_at TIMESTAMP NULL AFTER template_id,
ADD COLUMN IF NOT EXISTS related_type VARCHAR(50) NULL AFTER related_id,
ADD COLUMN IF NOT EXISTS action_url VARCHAR(500) NULL AFTER related_type;

-- Adăugăm indexuri noi dacă nu există
ALTER TABLE notifications
ADD INDEX IF NOT EXISTS idx_user_read (user_id, is_read),
ADD INDEX IF NOT EXISTS idx_company (company_id),
ADD INDEX IF NOT EXISTS idx_template (template_id),
ADD INDEX IF NOT EXISTS idx_related (related_type, related_id);

-- Actualizăm tipul coloanei type pentru a fi mai flexibil
ALTER TABLE notifications 
MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'general';

-- Actualizăm title să accepte mai multe caractere
ALTER TABLE notifications
MODIFY COLUMN title VARCHAR(255) NOT NULL;

-- ========================================
-- Post-migration: Populate existing data
-- ========================================

-- Setăm user_id implicit pentru notificări vechi (primul admin)
UPDATE notifications 
SET user_id = (SELECT id FROM users WHERE role LIKE '%admin%' OR role_id IN (SELECT id FROM roles WHERE slug IN ('admin','superadmin')) LIMIT 1)
WHERE user_id IS NULL AND id > 0
LIMIT 1000;

-- Setăm company_id pentru notificări vechi (din user sau prima companie)
UPDATE notifications n
LEFT JOIN users u ON n.user_id = u.id
SET n.company_id = u.company_id
WHERE n.company_id IS NULL AND u.company_id IS NOT NULL
LIMIT 1000;

-- ========================================
-- Verificare finală
-- ========================================
SELECT 
    'notifications' as tabel,
    COUNT(*) as total_records,
    SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) as with_user_id,
    SUM(CASE WHEN company_id IS NOT NULL THEN 1 ELSE 0 END) as with_company_id,
    SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count
FROM notifications;
