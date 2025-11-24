-- Migration: Fix notification_queue schema - Add missing columns
-- Date: 2025-11-24
-- Description: Adds max_attempts and scheduled_at columns required by Queue Processor

-- Add max_attempts column (required for retry logic)
ALTER TABLE notification_queue 
ADD COLUMN IF NOT EXISTS max_attempts INT DEFAULT 3 AFTER attempts;

-- Add scheduled_at column (required for delayed notifications)
ALTER TABLE notification_queue 
ADD COLUMN IF NOT EXISTS scheduled_at DATETIME DEFAULT NULL AFTER status;

-- Update existing records to have max_attempts = 3
UPDATE notification_queue SET max_attempts = 3 WHERE max_attempts IS NULL;

-- Verification query
SELECT 
    COUNT(*) as total_items,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
FROM notification_queue;
