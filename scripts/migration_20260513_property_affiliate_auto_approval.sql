-- Add affiliate approval mode per property
-- manual: owner approves/rejects requests
-- auto: requests are approved instantly

ALTER TABLE properties
    ADD COLUMN affiliate_approval_mode ENUM('manual', 'auto') NOT NULL DEFAULT 'manual' AFTER affiliate_id;
