-- ============================================
-- HOSTICO FIX: Add Missing Columns to notifications
-- Run this on TENANT database (wcdsgzyf_fm_tenant_1)
-- ============================================

SET @db_name = DATABASE();

-- Add template_id column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'template_id');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE notifications ADD COLUMN template_id INT AFTER type, ADD INDEX idx_template_id (template_id)',
    'SELECT "Column template_id already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add rendered_at column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'rendered_at');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE notifications ADD COLUMN rendered_at DATETIME AFTER sent_at',
    'SELECT "Column rendered_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add read_at column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'read_at');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE notifications ADD COLUMN read_at DATETIME AFTER rendered_at',
    'SELECT "Column read_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add dismissed_at column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'dismissed_at');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE notifications ADD COLUMN dismissed_at DATETIME AFTER read_at',
    'SELECT "Column dismissed_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verification
SELECT 'All missing columns added successfully!' AS status;
SHOW COLUMNS FROM notifications;
