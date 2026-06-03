-- Use este ficheiro SE a migração principal falhar por colunas já existentes
-- e só faltarem o índice ou o ENUM em payment_transactions.
-- Copie e execute apenas o que ainda não correu.

USE imobil_db;

-- 1) Backfill (seguro repetir)
UPDATE commissions
SET owner_payment_status = 'enviado'
WHERE owner_payment_submitted_at IS NOT NULL
  AND (owner_payment_status IS NULL OR owner_payment_status = 'nenhum');

-- 2) Índice (ignore erro se já existir)
-- CREATE INDEX idx_commissions_owner_payment_status ON commissions (owner_payment_status, status);

-- 3) Tipo de transação (obrigatório para o código novo)
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
