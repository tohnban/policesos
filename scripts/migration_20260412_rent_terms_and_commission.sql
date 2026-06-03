USE imobil_db;

START TRANSACTION;

ALTER TABLE properties
    ADD COLUMN IF NOT EXISTS rent_payment_terms JSON NULL AFTER purpose;

ALTER TABLE requests
    ADD COLUMN IF NOT EXISTS payment_term ENUM('mensal','trimestral','semestral','anual') NULL AFTER type,
    ADD COLUMN IF NOT EXISTS months_count TINYINT NULL AFTER payment_term,
    ADD COLUMN IF NOT EXISTS monthly_reference_amount DECIMAL(15,2) NULL AFTER months_count,
    ADD COLUMN IF NOT EXISTS modality_total_amount DECIMAL(15,2) NULL AFTER monthly_reference_amount;

COMMIT;
