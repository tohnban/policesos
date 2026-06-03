-- Migration: Add performance indexes for critical queries
-- Date: 2026-05-16

USE imobil_db;

-- properties(status, affiliate_id, created_at)
SELECT COUNT(1) INTO @exists FROM information_schema.statistics
 WHERE table_schema = DATABASE() AND table_name = 'properties' AND index_name = 'idx_properties_status_affiliate_created_at';
SET @sql = IF(@exists = 0,
    'ALTER TABLE properties ADD INDEX idx_properties_status_affiliate_created_at (status, affiliate_id, created_at)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- users(email, status, role)
SELECT COUNT(1) INTO @exists FROM information_schema.statistics
 WHERE table_schema = DATABASE() AND table_name = 'users' AND index_name = 'idx_users_email_status_role';
SET @sql = IF(@exists = 0,
    'ALTER TABLE users ADD INDEX idx_users_email_status_role (email, status, role)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- requests(status, property_id, user_id)
SELECT COUNT(1) INTO @exists FROM information_schema.statistics
 WHERE table_schema = DATABASE() AND table_name = 'requests' AND index_name = 'idx_requests_status_property_user';
SET @sql = IF(@exists = 0,
    'ALTER TABLE requests ADD INDEX idx_requests_status_property_user (status, property_id, user_id)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Optional: analyze tables to update optimizer statistics
ANALYZE TABLE properties;
ANALYZE TABLE users;
ANALYZE TABLE requests;
