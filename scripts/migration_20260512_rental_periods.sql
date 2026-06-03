USE imobil_db;

START TRANSACTION;

ALTER TABLE properties
    ADD COLUMN IF NOT EXISTS rental_days INT NULL AFTER purpose,
    ADD COLUMN IF NOT EXISTS rental_months INT NULL AFTER rental_days;

-- Set sensible defaults for existing rows: 7 dias para aluguel curto, 1 mes para aluguel longo
UPDATE properties SET rental_days = 7 WHERE purpose = 'aluguer_curto' AND rental_days IS NULL;
UPDATE properties SET rental_months = 1 WHERE purpose = 'aluguer_longo' AND rental_months IS NULL;

COMMIT;
