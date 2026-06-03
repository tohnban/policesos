-- Migration: Separate commercial_status and dispute_status columns
-- Date: 2026-04-23
-- Purpose: Split the single `status` column into two independent axes:
--   commercial_status — tracks the negotiation result
--   dispute_status    — tracks the dispute lifecycle independently

USE imobil_db;

-- 1. Add the two new columns
ALTER TABLE requests
    ADD COLUMN commercial_status ENUM(
        'em_contacto', 'fechado_ganho', 'cancelado', 'expirado',
        'pendente', 'analise', 'proposta', 'fechado_perdido'
    ) NULL DEFAULT NULL AFTER status,
    ADD COLUMN dispute_status ENUM(
        'nenhuma', 'aberta', 'em_analise',
        'julgada_procedente', 'julgada_improcedente'
    ) NOT NULL DEFAULT 'nenhuma' AFTER commercial_status;

-- 2. Backfill commercial_status for non-dispute rows (direct copy from status)
UPDATE requests
SET commercial_status = status
WHERE status != 'em_disputa';

-- 3. For rows in dispute: commercial state was fechado_ganho before the dispute opened
--    (contestClosingWon transitions from fechado_ganho → em_disputa)
UPDATE requests
SET commercial_status = 'fechado_ganho'
WHERE status = 'em_disputa';

-- 4. Backfill dispute_status for rows currently in dispute
UPDATE requests
SET dispute_status = 'aberta'
WHERE status = 'em_disputa';

-- 5. Rows whose dispute was already resolved land back on fechado_ganho/cancelado
--    with dispute_status still 'nenhuma' — but we can tag historically resolved
--    disputes as 'julgada_procedente' or 'julgada_improcedente' only if we know
--    they went through a dispute cycle. Since we cannot reliably detect this from
--    the current schema, we leave those rows with dispute_status = 'nenhuma'.
--    Going forward, resolveDispute() will write the correct value.

-- Optional: add an index on the two new columns for filter queries
ALTER TABLE requests
    ADD INDEX idx_commercial_status (commercial_status),
    ADD INDEX idx_dispute_status    (dispute_status);
