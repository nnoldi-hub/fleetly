-- ============================================
-- HOSTICO DEPLOYMENT: TENANT DATABASE
-- Notification System V2 - Tenant Tables Update
-- Database: fleet_management_company_X (TENANT)
-- ============================================
-- Run this on EACH tenant database (fleet_management_company_1, fleet_management_company_2, etc.)

-- ============================================
-- 1. UPDATE notifications table (add V2 columns)
-- ============================================

-- Check if columns exist before adding them
SET @db_name = DATABASE();

-- Add status column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'status');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE notifications ADD COLUMN status ENUM(''pending'', ''sent'', ''failed'', ''read'') DEFAULT ''sent'' AFTER priority',
    'SELECT "Column status already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add scheduled_at column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'scheduled_at');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE notifications ADD COLUMN scheduled_at DATETIME AFTER status',
    'SELECT "Column scheduled_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add sent_at column if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'sent_at');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE notifications ADD COLUMN sent_at DATETIME AFTER scheduled_at',
    'SELECT "Column sent_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add metadata column if not exists (JSON for template variables)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'metadata');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE notifications ADD COLUMN metadata JSON AFTER action_url',
    'SELECT "Column metadata already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 2. UPDATE documents table (add expiry_status)
-- ============================================

-- Check if documents table exists
SET @table_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'documents');

-- Add expiry_status column if table exists and column doesn't
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'documents' AND COLUMN_NAME = 'expiry_status');

SET @sql = IF(@table_exists > 0 AND @col_exists = 0,
    'ALTER TABLE documents ADD COLUMN expiry_status ENUM(''active'', ''expiring_soon'', ''expired'') DEFAULT ''active'' AFTER status',
    'SELECT "Table documents not found or column expiry_status already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 3. UPDATE insurance table (add expiry_status)
-- ============================================

-- Check if insurance table exists
SET @table_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'insurance');

-- Add expiry_status column if table exists and column doesn't
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'insurance' AND COLUMN_NAME = 'expiry_status');

SET @sql = IF(@table_exists > 0 AND @col_exists = 0,
    'ALTER TABLE insurance ADD COLUMN expiry_status ENUM(''active'', ''expiring_soon'', ''expired'') DEFAULT ''active'' AFTER status',
    'SELECT "Table insurance not found or column expiry_status already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 4. UPDATE maintenance table (add due_status)
-- ============================================

-- Check if maintenance table exists
SET @table_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'maintenance');

-- Add due_status column if table exists and column doesn't
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'maintenance' AND COLUMN_NAME = 'due_status');

SET @sql = IF(@table_exists > 0 AND @col_exists = 0,
    'ALTER TABLE maintenance ADD COLUMN due_status ENUM(''scheduled'', ''due_soon'', ''overdue'') DEFAULT ''scheduled'' AFTER status',
    'SELECT "Table maintenance not found or column due_status already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 5. Create indexes for performance
-- ============================================

-- Index on notifications.status
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_status');
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE notifications ADD INDEX idx_status (status)',
    'SELECT "Index idx_status already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index on notifications.scheduled_at
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_scheduled_at');
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE notifications ADD INDEX idx_scheduled_at (scheduled_at)',
    'SELECT "Index idx_scheduled_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- VERIFICATION QUERIES
-- ============================================
-- Run these to verify installation:
-- SHOW COLUMNS FROM notifications;
-- SHOW COLUMNS FROM documents LIKE '%expiry_status%';
-- SHOW COLUMNS FROM insurance LIKE '%expiry_status%';
-- SHOW COLUMNS FROM maintenance LIKE '%due_status%';
-- SHOW INDEXES FROM notifications;

-- ============================================
-- END OF TENANT DATABASE MIGRATION
-- ============================================
