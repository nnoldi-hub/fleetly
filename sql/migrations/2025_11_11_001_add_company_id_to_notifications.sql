-- Migration: Add company_id column for broadcast support
ALTER TABLE notifications
    ADD COLUMN company_id INT NULL AFTER user_id;

CREATE INDEX idx_notifications_company ON notifications(company_id);
