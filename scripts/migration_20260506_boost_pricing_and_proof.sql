-- Adiciona colunas de pricing e comprovativo à tabela de boosts.
-- Adiciona 4 settings de configuração de pricing de destaque.
-- Idempotente.

START TRANSACTION;

ALTER TABLE property_boost_requests
    ADD COLUMN IF NOT EXISTS fee_required  DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER duration_days,
    ADD COLUMN IF NOT EXISTS payment_proof VARCHAR(255)  NULL AFTER payment_reference;

INSERT INTO settings (`key`, `value`, `label`, `description`) VALUES
    ('boost_daily_fee',    '2000', 'Destaque: valor por dia (Kz)',      'Preço cobrado por cada dia de destaque solicitado.'),
    ('boost_min_days',     '7',    'Destaque: duração mínima (dias)',    'Menor duração permitida para solicitar destaque.'),
    ('boost_max_days',     '90',   'Destaque: duração máxima (dias)',    'Maior duração permitida para solicitar destaque.'),
    ('boost_default_days', '30',   'Destaque: duração padrão (dias)',    'Opção selecionada por defeito no formulário de destaque.')
ON DUPLICATE KEY UPDATE `value` = `value`;

COMMIT;
