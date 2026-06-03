USE imobil_db;

START TRANSACTION;

ALTER TABLE requests
    ADD COLUMN payment_confirmation_status ENUM('pendente', 'declarado_comprador', 'confirmado_proprietario', 'contestado') NULL AFTER closing_confirmation_status,
    ADD COLUMN payment_declared_by INT NULL AFTER payment_confirmation_status,
    ADD COLUMN payment_declared_at TIMESTAMP NULL AFTER payment_declared_by,
    ADD COLUMN payment_proof_path VARCHAR(255) NULL AFTER payment_declared_at,
    ADD COLUMN payment_received_confirmed_by INT NULL AFTER payment_proof_path,
    ADD COLUMN payment_received_confirmed_at TIMESTAMP NULL AFTER payment_received_confirmed_by;

UPDATE requests
SET payment_confirmation_status = 'pendente'
WHERE status = 'fechado_ganho'
  AND payment_confirmation_status IS NULL;

ALTER TABLE requests
    ADD CONSTRAINT fk_requests_payment_declared_by
        FOREIGN KEY (payment_declared_by) REFERENCES users(id),
    ADD CONSTRAINT fk_requests_payment_received_confirmed_by
        FOREIGN KEY (payment_received_confirmed_by) REFERENCES users(id);

CREATE INDEX idx_requests_payment_confirmation_status
    ON requests (payment_confirmation_status);

CREATE INDEX idx_requests_payment_declared_at
    ON requests (payment_declared_at);

COMMIT;
