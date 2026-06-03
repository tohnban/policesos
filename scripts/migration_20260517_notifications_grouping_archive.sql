-- Migration: Enhanced notifications with grouping and archive

ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS notification_group VARCHAR(100) NULL AFTER type,
    ADD COLUMN IF NOT EXISTS grouped_count INT NOT NULL DEFAULT 1 AFTER notification_group,
    ADD COLUMN IF NOT EXISTS is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER grouped_count,
    ADD COLUMN IF NOT EXISTS is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER is_read;

ALTER TABLE notifications
    ADD INDEX IF NOT EXISTS idx_notifications_user_read_archived (user_id, is_read, is_archived),
    ADD INDEX IF NOT EXISTS idx_notifications_group (notification_group),
    ADD INDEX IF NOT EXISTS idx_notifications_created_at (created_at DESC);
