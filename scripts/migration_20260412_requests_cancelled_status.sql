USE imobil_db;

START TRANSACTION;

ALTER TABLE requests
    MODIFY COLUMN status ENUM('pendente','analise','aceite','recusado','cancelado') DEFAULT 'pendente';

COMMIT;
