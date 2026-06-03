-- Corrige caminhos legados de comprovativos do selo de confianca.
-- Objetivo: alinhar para /public/storage/uploads/trust_badge_proofs/
-- Esta migration e idempotente.

START TRANSACTION;

-- 1) URLs absolutas antigas: .../storage/uploads/trust_badge_proofs/...
--    (evita alterar as que ja estao em .../public/storage/...)
UPDATE users
SET trust_badge_payment_proof = REPLACE(
    trust_badge_payment_proof,
    '/storage/uploads/trust_badge_proofs/',
    '/public/storage/uploads/trust_badge_proofs/'
)
WHERE trust_badge_payment_proof LIKE '%/storage/uploads/trust_badge_proofs/%'
  AND trust_badge_payment_proof NOT LIKE '%/public/storage/uploads/trust_badge_proofs/%';

-- 2) Caminho relativo antigo sem barra inicial: storage/uploads/trust_badge_proofs/...
UPDATE users
SET trust_badge_payment_proof = CONCAT('public/', trust_badge_payment_proof)
WHERE trust_badge_payment_proof LIKE 'storage/uploads/trust_badge_proofs/%';

-- 3) Caminho relativo antigo com barra inicial: /storage/uploads/trust_badge_proofs/...
UPDATE users
SET trust_badge_payment_proof = CONCAT('/public', trust_badge_payment_proof)
WHERE trust_badge_payment_proof LIKE '/storage/uploads/trust_badge_proofs/%';

COMMIT;
