-- Migration: operational hardening
-- Date: 2026-05-03
-- Scope:
-- 1) soft deletes for audit trail (favorites, payment_methods)
-- 2) boost expiration support and indexes
-- 3) chat indexes hardening
-- 4) configurable global POST rate limit settings

USE imobil_db;

-- -----------------------------
-- 1) Soft delete columns
-- -----------------------------

SET @exists_col := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'favorites'
    AND column_name = 'deleted_at'
);
SET @sql := IF(@exists_col = 0,
  'ALTER TABLE favorites ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'favorites'
    AND index_name = 'idx_favorites_deleted_at'
);
SET @sql := IF(@exists_idx = 0,
  'CREATE INDEX idx_favorites_deleted_at ON favorites(deleted_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_col := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'payment_methods'
    AND column_name = 'deleted_at'
);
SET @sql := IF(@exists_col = 0,
  'ALTER TABLE payment_methods ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'payment_methods'
    AND index_name = 'idx_payment_methods_deleted_at'
);
SET @sql := IF(@exists_idx = 0,
  'CREATE INDEX idx_payment_methods_deleted_at ON payment_methods(deleted_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------
-- 2) Boost expiration lifecycle
-- -----------------------------

SET @status_type := (
  SELECT COLUMN_TYPE
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'property_boost_requests'
    AND column_name = 'status'
  LIMIT 1
);
SET @needs_enum_upgrade := IF(@status_type LIKE '%expirado%', 0, 1);
SET @sql := IF(@needs_enum_upgrade = 1,
  "ALTER TABLE property_boost_requests MODIFY COLUMN status ENUM('pendente','aprovado','rejeitado','expirado') NOT NULL DEFAULT 'pendente'",
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_col := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'property_boost_requests'
    AND column_name = 'expired_at'
);
SET @sql := IF(@exists_col = 0,
  'ALTER TABLE property_boost_requests ADD COLUMN expired_at DATETIME NULL DEFAULT NULL AFTER expires_at',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'property_boost_requests'
    AND index_name = 'idx_pbr_status_expires_at'
);
SET @sql := IF(@exists_idx = 0,
  'CREATE INDEX idx_pbr_status_expires_at ON property_boost_requests(status, expires_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'property_boost_requests'
    AND index_name = 'idx_pbr_property_status_expires'
);
SET @sql := IF(@exists_idx = 0,
  'CREATE INDEX idx_pbr_property_status_expires ON property_boost_requests(property_id, status, expires_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------
-- 3) Chat indexes hardening
-- -----------------------------

SET @exists_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'request_chat_threads'
    AND index_name = 'idx_request_chat_threads_last_message_at'
);
SET @sql := IF(@exists_idx = 0,
  'CREATE INDEX idx_request_chat_threads_last_message_at ON request_chat_threads(last_message_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'request_chat_messages'
    AND index_name = 'idx_rcm_thread_sender_created'
);
SET @sql := IF(@exists_idx = 0,
  'CREATE INDEX idx_rcm_thread_sender_created ON request_chat_messages(thread_id, sender_user_id, created_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'request_chat_messages'
    AND index_name = 'idx_rcm_thread_deleted_created'
);
SET @sql := IF(@exists_idx = 0,
  'CREATE INDEX idx_rcm_thread_deleted_created ON request_chat_messages(thread_id, deleted_at, created_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------
-- 4) Settings for global POST rate limiting
-- -----------------------------

INSERT INTO settings (`key`, value, label, description)
VALUES
  ('rate_limit_post_max', '60', 'Limite global de POST por janela', 'Número máximo de requisições POST por IP e rota em cada janela.'),
  ('rate_limit_post_window_seconds', '60', 'Janela de rate limit POST (segundos)', 'Duração da janela para limitação global de requisições POST.')
ON DUPLICATE KEY UPDATE value = VALUES(value);
