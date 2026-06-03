-- Liquidação de comissões: estados do pagamento do proprietário e ledger completo.
-- Idempotente: pode executar mais do que uma vez sem erro #1060.

USE imobil_db;

START TRANSACTION;

-- Colunas em commissions (MariaDB / MySQL 8+)
ALTER TABLE commissions
    ADD COLUMN IF NOT EXISTS owner_payment_method_id INT NULL AFTER owner_payment_submitted_at;

ALTER TABLE commissions
    ADD COLUMN IF NOT EXISTS owner_payment_channel_id INT NULL AFTER owner_payment_method_id;

ALTER TABLE commissions
    ADD COLUMN IF NOT EXISTS owner_payment_status ENUM('nenhum', 'enviado', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'nenhum' AFTER owner_payment_channel_id;

ALTER TABLE commissions
    ADD COLUMN IF NOT EXISTS owner_payment_validated_by INT NULL AFTER owner_payment_status;

ALTER TABLE commissions
    ADD COLUMN IF NOT EXISTS owner_payment_validated_at TIMESTAMP NULL AFTER owner_payment_validated_by;

ALTER TABLE commissions
    ADD COLUMN IF NOT EXISTS owner_payment_rejection_reason VARCHAR(255) NULL AFTER owner_payment_validated_at;

ALTER TABLE commissions
    ADD COLUMN IF NOT EXISTS affiliate_payout_account_id INT NULL AFTER owner_payment_rejection_reason;

ALTER TABLE commissions
    ADD COLUMN IF NOT EXISTS affiliate_payout_completed_at TIMESTAMP NULL AFTER affiliate_payout_account_id;

-- Backfill: comprovativos já enviados antes desta migração
UPDATE commissions
SET owner_payment_status = 'enviado'
WHERE owner_payment_submitted_at IS NOT NULL
  AND (owner_payment_status IS NULL OR owner_payment_status = 'nenhum');

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'commissions'
      AND index_name = 'idx_commissions_owner_payment_status'
);

SET @sql := IF(
    @idx_exists = 0,
    'CREATE INDEX idx_commissions_owner_payment_status ON commissions (owner_payment_status, status)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Novo tipo no ledger (reexecutar é seguro)
ALTER TABLE payment_transactions
    MODIFY COLUMN transaction_type ENUM(
        'commission_owner_payment',
        'commission_payout',
        'system_commission',
        'boost_fee',
        'trust_badge_fee',
        'manual_adjustment',
        'subscription_fee'
    ) NOT NULL;

COMMIT;
