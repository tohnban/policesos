-- Migration: trust badge eligibility thresholds (won deals, account age)
-- Date: 2026-05-26

INSERT INTO settings (`key`, value, label, description) VALUES
    ('trust_badge_min_won_deals', '3', 'Selo confiança: negócios ganhos mínimos', 'Número mínimo de negociações com fecho ganho (proprietário ou promotor). Use 0 para desativar.'),
    ('trust_badge_min_account_days', '90', 'Selo confiança: dias mínimos na plataforma', 'Dias desde o registo até poder solicitar o selo. Use 0 para desativar.'),
    ('trust_badge_require_confirmed_closing', '1', 'Selo confiança: exigir fecho confirmado', '1 = só contam fechos ganhos confirmados; 0 = qualquer estado fechado_ganho.')
ON DUPLICATE KEY UPDATE
    value = VALUES(value),
    label = VALUES(label),
    description = VALUES(description),
    updated_at = NOW();
