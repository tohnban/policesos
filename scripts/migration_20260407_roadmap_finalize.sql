USE imobil_db;

START TRANSACTION;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role ENUM('super_admin', 'moderador', 'financeiro', 'suporte', 'utilizador')
    DEFAULT 'utilizador' AFTER is_admin;

UPDATE users
SET role = 'super_admin'
WHERE is_admin = 1;

UPDATE users
SET trust_badge_status = 'nenhum'
WHERE trust_badge_status = 'none';

UPDATE users
SET trust_badge_status = 'pendente'
WHERE trust_badge_status = 'pending';

UPDATE users
SET trust_badge_status = 'aprovado'
WHERE trust_badge_status = 'approved';

UPDATE users
SET trust_badge_status = 'rejeitado'
WHERE trust_badge_status = 'rejected';

ALTER TABLE users
    MODIFY COLUMN trust_badge_status ENUM('nenhum', 'pendente', 'aprovado', 'rejeitado')
    DEFAULT 'nenhum';

UPDATE commissions
SET status = 'pendente'
WHERE status = 'pending';

UPDATE commissions
SET status = 'pago'
WHERE status = 'paid';

UPDATE commissions
SET status = 'cancelado'
WHERE status = 'cancelled';

ALTER TABLE commissions
    MODIFY COLUMN status ENUM('pendente', 'pago', 'cancelado')
    DEFAULT 'pendente';

ALTER TABLE commissions
    ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP NULL AFTER status,
    ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(100) NULL AFTER paid_at;

COMMIT;