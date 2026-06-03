-- Migration: Add unarchive notification method support
-- Date: 2025-01-23
-- Description: Ensures notification table has all required fields and supports unarchive operations

-- Verify notification table structure
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS `is_archived` TINYINT(1) DEFAULT 0 COMMENT 'Notification archived status' AFTER `is_read`;
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS `notification_group` VARCHAR(40) DEFAULT NULL COMMENT 'SHA1 hash for grouping similar notifications' AFTER `id`;
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS `grouped_count` INT DEFAULT 1 COMMENT 'Number of notifications in this group' AFTER `notification_group`;

-- Create index for faster lookups on inbox/archive queries
CREATE INDEX IF NOT EXISTS `idx_user_read_archived` ON notifications(user_id, is_read, is_archived);
CREATE INDEX IF NOT EXISTS `idx_user_archived` ON notifications(user_id, is_archived);
CREATE INDEX IF NOT EXISTS `idx_notification_group` ON notifications(notification_group);

-- Verify column types and attributes are correct
UPDATE notifications SET `is_archived` = 0 WHERE `is_archived` IS NULL;
UPDATE notifications SET `grouped_count` = 1 WHERE `grouped_count` IS NULL OR `grouped_count` = 0;
