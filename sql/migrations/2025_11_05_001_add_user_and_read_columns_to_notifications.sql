-- Migration: Add per-user and read-state columns to notifications to align with application model
ALTER TABLE notifications
    ADD COLUMN user_id INT NULL AFTER id,
    ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN read_at TIMESTAMP NULL AFTER is_read,
    ADD COLUMN related_type VARCHAR(50) NULL AFTER related_id,
    ADD COLUMN action_url VARCHAR(255) NULL AFTER related_type,
    ADD CONSTRAINT fk_notifications_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Helpful indexes
CREATE INDEX idx_notifications_user_unread ON notifications(user_id, is_read);
CREATE INDEX idx_notifications_user_created ON notifications(user_id, created_at);
