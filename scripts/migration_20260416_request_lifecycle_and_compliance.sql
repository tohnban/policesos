USE imobil_db;

START TRANSACTION;

ALTER TABLE requests
  MODIFY COLUMN status ENUM('pendente','analise','em_contacto','proposta','aceite','recusado','fechado_ganho','fechado_perdido','cancelado','expirado','em_disputa') DEFAULT 'pendente';

ALTER TABLE requests
    ADD COLUMN IF NOT EXISTS next_followup_at TIMESTAMP NULL AFTER status,
    ADD COLUMN IF NOT EXISTS last_sla_alert_at TIMESTAMP NULL AFTER next_followup_at,
  ADD COLUMN IF NOT EXISTS attribution_expires_at TIMESTAMP NULL AFTER last_sla_alert_at,
  ADD COLUMN IF NOT EXISTS closing_confirmation_status ENUM('pendente','confirmado','contestada') NULL AFTER attribution_expires_at,
  ADD COLUMN IF NOT EXISTS closing_declared_by INT NULL AFTER closing_confirmation_status,
  ADD COLUMN IF NOT EXISTS closing_declared_at TIMESTAMP NULL AFTER closing_declared_by,
  ADD COLUMN IF NOT EXISTS closing_confirmed_by INT NULL AFTER closing_declared_at,
  ADD COLUMN IF NOT EXISTS closing_confirmed_at TIMESTAMP NULL AFTER closing_confirmed_by;

UPDATE requests
SET status = 'fechado_ganho'
WHERE status = 'aceite';

UPDATE requests
SET status = 'fechado_perdido'
WHERE status = 'recusado';

UPDATE requests
SET closing_confirmation_status = 'confirmado'
WHERE status = 'fechado_ganho'
  AND closing_confirmation_status IS NULL;

ALTER TABLE requests
    MODIFY COLUMN status ENUM('pendente','analise','em_contacto','proposta','fechado_ganho','fechado_perdido','cancelado','expirado','em_disputa') DEFAULT 'pendente';

UPDATE requests
SET attribution_expires_at = DATE_ADD(created_at, INTERVAL 90 DAY)
WHERE attribution_expires_at IS NULL;

UPDATE requests
SET next_followup_at = DATE_ADD(COALESCE(updated_at, created_at), INTERVAL 7 DAY)
WHERE next_followup_at IS NULL
  AND status IN ('pendente','analise','em_contacto','proposta');

UPDATE requests
SET status = 'expirado'
WHERE status IN ('pendente','analise','em_contacto','proposta')
  AND COALESCE(updated_at, created_at) <= DATE_SUB(NOW(), INTERVAL 30 DAY);

ALTER TABLE commissions
    ADD COLUMN IF NOT EXISTS due_at TIMESTAMP NULL AFTER status;

UPDATE commissions
SET due_at = DATE_ADD(created_at, INTERVAL 7 DAY)
WHERE due_at IS NULL
  AND status IN ('pendente', 'pending');

SET @idx_exists := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'requests'
    AND index_name = 'idx_requests_next_followup'
);
SET @sql := IF(
  @idx_exists = 0,
  'CREATE INDEX idx_requests_next_followup ON requests(next_followup_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'requests'
    AND index_name = 'idx_requests_attribution_expires'
);
SET @sql := IF(
  @idx_exists = 0,
  'CREATE INDEX idx_requests_attribution_expires ON requests(attribution_expires_at)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'commissions'
    AND index_name = 'idx_commissions_due_status'
);
SET @sql := IF(
  @idx_exists = 0,
  'CREATE INDEX idx_commissions_due_status ON commissions(due_at, status)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;
