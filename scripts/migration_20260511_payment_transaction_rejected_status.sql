USE imobil_db;

START TRANSACTION;

ALTER TABLE payment_transactions
    MODIFY COLUMN status ENUM('pendente', 'processando', 'confirmado', 'cancelado', 'falhado', 'rejeitado') NOT NULL DEFAULT 'pendente';

COMMIT;
