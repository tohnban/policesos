-- Migration: high-priority hardening (auth throttling + operational indexes)
-- Date: 2026-05-15

USE imobil_db;

-- -----------------------------
-- 1) Fine-grained rate-limit settings for auth and API
-- -----------------------------

INSERT INTO settings (`key`, value, label, description)
VALUES
  ('rate_limit_auth_login_max', '10', 'Limite de login por janela', 'Número máximo de tentativas de login por IP/rota na janela de tempo.'),
  ('rate_limit_auth_login_window_seconds', '60', 'Janela rate limit login (segundos)', 'Duração da janela de limitação do endpoint de login.'),
  ('rate_limit_auth_register_max', '5', 'Limite de registos por janela', 'Número máximo de registos por IP/rota na janela de tempo.'),
  ('rate_limit_auth_register_window_seconds', '300', 'Janela rate limit registo (segundos)', 'Duração da janela de limitação do endpoint de registo.'),
  ('rate_limit_auth_recover_max', '5', 'Limite de recuperação por janela', 'Número máximo de pedidos de recuperação de senha por IP/rota na janela de tempo.'),
  ('rate_limit_auth_recover_window_seconds', '300', 'Janela rate limit recuperação (segundos)', 'Duração da janela de limitação do endpoint de recuperação.'),
  ('rate_limit_auth_reset_max', '10', 'Limite de reset por janela', 'Número máximo de redefinições por IP/rota na janela de tempo.'),
  ('rate_limit_auth_reset_window_seconds', '600', 'Janela rate limit reset (segundos)', 'Duração da janela de limitação do endpoint de redefinição.'),
  ('rate_limit_api_max', '120', 'Limite de API por janela', 'Número máximo de pedidos API por IP/rota na janela de tempo.'),
  ('rate_limit_api_window_seconds', '60', 'Janela rate limit API (segundos)', 'Duração da janela de limitação dos endpoints de API.')
ON DUPLICATE KEY UPDATE value = VALUES(value), label = VALUES(label), description = VALUES(description);

-- -----------------------------
-- 2) Audit log access indexes
-- -----------------------------

SET @exists_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'logs'
    AND index_name = 'idx_logs_user_created'
);
SET @sql := IF(@exists_idx = 0,
  'CREATE INDEX idx_logs_user_created ON logs(user_id, created_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'logs'
    AND index_name = 'idx_logs_entity_created'
);
SET @sql := IF(@exists_idx = 0,
  'CREATE INDEX idx_logs_entity_created ON logs(entity_type, entity_id, created_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'logs'
    AND index_name = 'idx_logs_action_created'
);
SET @sql := IF(@exists_idx = 0,
  'CREATE INDEX idx_logs_action_created ON logs(action, created_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------
-- 3) Operational indexes for high-frequency dashboards/queries
-- -----------------------------

SET @exists_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'users'
    AND index_name = 'idx_users_role_status_created'
);
SET @sql := IF(@exists_idx = 0,
  'CREATE INDEX idx_users_role_status_created ON users(role, status, created_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'properties'
    AND index_name = 'idx_properties_affiliate_status_created'
);
SET @sql := IF(@exists_idx = 0,
  'CREATE INDEX idx_properties_affiliate_status_created ON properties(affiliate_id, status, created_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'requests'
    AND index_name = 'idx_requests_status_updated'
);
SET @sql := IF(@exists_idx = 0,
  'CREATE INDEX idx_requests_status_updated ON requests(status, updated_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
