-- Migration: Add performance indexes (simpler, no PREPARE or information_schema queries)
-- Date: 2026-05-16

USE imobil_db;

-- Add indexes (will error if index already exists; run as DB admin)
ALTER TABLE properties ADD INDEX idx_properties_status_affiliate_created_at (status, affiliate_id, created_at);
ALTER TABLE users ADD INDEX idx_users_email_status_role (email, status, role);
ALTER TABLE requests ADD INDEX idx_requests_status_property_user (status, property_id, user_id);

-- Update optimizer statistics
ANALYZE TABLE properties;
ANALYZE TABLE users;
ANALYZE TABLE requests;
