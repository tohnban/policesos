USE imobil_db;

START TRANSACTION;

ALTER TABLE commissions
    ADD COLUMN owner_payment_proof_path VARCHAR(255) NULL AFTER payment_reference,
    ADD COLUMN owner_payment_reference VARCHAR(120) NULL AFTER owner_payment_proof_path,
    ADD COLUMN owner_payment_submitted_at TIMESTAMP NULL AFTER owner_payment_reference;

CREATE INDEX idx_commissions_owner_payment_submitted
    ON commissions (owner_payment_submitted_at);

COMMIT;
