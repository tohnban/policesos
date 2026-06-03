-- Migration: settings table for configurable parameters
-- Date: 2026-05-03

CREATE TABLE IF NOT EXISTS settings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    `key`       VARCHAR(100) NOT NULL,
    value       VARCHAR(255) NOT NULL,
    label       VARCHAR(150) NOT NULL COMMENT 'Human-readable label for the admin panel',
    description TEXT NULL,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_settings_key (`key`)
);

INSERT INTO settings (`key`, value, label, description) VALUES
    ('commission_system_pct',        '2.00',  'Taxa do sistema (%)',                'Percentagem da comissão que vai para o sistema quando há afiliado válido.'),
    ('commission_affiliate_pct',     '3.00',  'Taxa do afiliado (%)',               'Percentagem da comissão que vai para o afiliado quando há afiliado válido.'),
    ('commission_system_only_pct',   '5.00',  'Taxa do sistema sem afiliado (%)',   'Percentagem total quando não há afiliado válido (100% vai para o sistema).'),
    ('commission_due_days',          '7',     'Prazo de vencimento da comissão (dias)', 'Número de dias após o lançamento até à data de vencimento.')
ON DUPLICATE KEY UPDATE value = VALUES(value);
