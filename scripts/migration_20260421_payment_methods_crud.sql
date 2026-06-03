-- Migration 2026-04-21 (b)
-- Add fields_config JSON column to payment_methods so admin can configure
-- which data fields each method requires from users.

USE imobil_db;

ALTER TABLE payment_methods
    ADD COLUMN IF NOT EXISTS fields_config JSON NULL COMMENT 'Boolean map of fields shown in user form' AFTER requires_reference;

-- Set sensible defaults for the 4 seeded methods
UPDATE payment_methods
SET fields_config = '{"account_name":true,"account_number":true,"iban":true,"bank_name":true,"wallet_provider":false,"phone_number":false}'
WHERE code = 'bank_transfer' AND fields_config IS NULL;

UPDATE payment_methods
SET fields_config = '{"account_name":true,"account_number":false,"iban":false,"bank_name":false,"wallet_provider":true,"phone_number":true}'
WHERE code = 'multicaixa_express' AND fields_config IS NULL;

UPDATE payment_methods
SET fields_config = '{"account_name":true,"account_number":false,"iban":false,"bank_name":false,"wallet_provider":true,"phone_number":true}'
WHERE code = 'mobile_wallet' AND fields_config IS NULL;

UPDATE payment_methods
SET fields_config = '{"account_name":false,"account_number":false,"iban":false,"bank_name":false,"wallet_provider":false,"phone_number":false}'
WHERE code = 'cash' AND fields_config IS NULL;
