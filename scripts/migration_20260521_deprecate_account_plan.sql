-- Migration: Mark users.account_plan as deprecated (runtime uses user_subscriptions)
-- Date: 2026-05-21

USE imobil_db;

START TRANSACTION;

SET @has_account_plan := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'account_plan'
);

SET @sql_deprecate := IF(
    @has_account_plan > 0,
    "ALTER TABLE users MODIFY COLUMN account_plan ENUM('free', 'premium') NOT NULL DEFAULT 'free'
        COMMENT 'DEPRECATED: use user_subscriptions + subscription_plans. Legacy backfill only.'",
    'SELECT 1'
);
PREPARE stmt_deprecate FROM @sql_deprecate;
EXECUTE stmt_deprecate;
DEALLOCATE PREPARE stmt_deprecate;

COMMIT;
