USE imobil_db;

START TRANSACTION;

ALTER TABLE properties
    MODIFY COLUMN status ENUM('pendente', 'em_analise', 'disponivel', 'vendido', 'alugado', 'rejeitado') DEFAULT 'pendente';

COMMIT;
