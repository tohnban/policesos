USE imobil_db;

START TRANSACTION;

ALTER TABLE commissions
    ADD COLUMN IF NOT EXISTS affiliate_payout_status ENUM('nenhum', 'pendente', 'pago') NOT NULL DEFAULT 'nenhum' AFTER affiliate_payout_completed_at;

UPDATE commissions
SET affiliate_payout_status = 'pago'
WHERE affiliate_payout_completed_at IS NOT NULL;

UPDATE commissions c
INNER JOIN properties p ON p.id = c.property_id
SET c.affiliate_payout_status = 'pendente'
WHERE c.affiliate_amount > 0
  AND c.affiliate_id > 0
  AND c.affiliate_id <> p.affiliate_id
  AND c.status = 'pago'
  AND c.affiliate_payout_completed_at IS NULL
  AND c.affiliate_payout_status <> 'pago';

COMMIT;
