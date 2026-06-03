-- Fix boost proof paths: convert full URLs to relative paths
-- Idempotente.

START TRANSACTION;

UPDATE property_boost_requests
SET payment_proof = TRIM(BOTH '/' FROM REPLACE(payment_proof, 'http://localhost/', ''))
WHERE payment_proof IS NOT NULL
  AND payment_proof LIKE 'http://localhost/%';

COMMIT;
