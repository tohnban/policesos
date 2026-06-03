-- Migration 2026-04-18
-- Goals:
-- 1) Add login rate-limit persistence table.
-- 2) Enforce single commission per request (idempotency at DB level).

USE imobil_db;

-- 1) Login attempts table
CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    login_identifier VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_identifier_time (login_identifier, attempted_at),
    INDEX idx_login_attempts_ip_time (ip_address, attempted_at)
);

-- 2) Clean duplicate commissions before adding unique key.
-- Keeps the oldest row for each request_id and removes subsequent duplicates.
DELETE c1
FROM commissions c1
INNER JOIN commissions c2
    ON c1.request_id = c2.request_id
   AND c1.id > c2.id;

-- Add DB-enforced idempotency.
SET @has_unique := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'commissions'
      AND index_name = 'unique_commission_request'
);

SET @sql := IF(
    @has_unique = 0,
    'ALTER TABLE commissions ADD UNIQUE KEY unique_commission_request (request_id)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
