-- Migration: trust badge duration and pricing configuration
-- Date: 2026-05-06

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS trust_badge_duration_months INT NULL AFTER trust_badge_approved_at;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS trust_badge_payment_proof VARCHAR(255) NULL AFTER trust_badge_fee_paid;

INSERT INTO settings (`key`, value, label, description) VALUES
    ('trust_badge_monthly_fee', '5000', 'Selo confiança: valor por mês (Kz)', 'Preço cobrado por cada mês selecionado no pedido de selo.'),
    ('trust_badge_min_months', '1', 'Selo confiança: duração mínima (meses)', 'Menor duração permitida para solicitar o selo de confiança.'),
    ('trust_badge_max_months', '12', 'Selo confiança: duração máxima (meses)', 'Maior duração permitida para solicitar o selo de confiança.'),
    ('trust_badge_default_months', '6', 'Selo confiança: duração padrão (meses)', 'Opção selecionada por padrão no formulário do perfil.')
ON DUPLICATE KEY UPDATE
    value = VALUES(value),
    label = VALUES(label),
    description = VALUES(description),
    updated_at = NOW();
