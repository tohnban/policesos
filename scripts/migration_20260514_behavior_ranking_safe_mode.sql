-- Migration: safe mode behavior ranking support
-- Date: 2026-05-14

CREATE TABLE IF NOT EXISTS property_behavior_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    visitor_key VARCHAR(64) NULL,
    property_id INT NOT NULL,
    event_type ENUM('view', 'favorite', 'request') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pbe_property_time (property_id, created_at),
    INDEX idx_pbe_user_time (user_id, created_at),
    INDEX idx_pbe_visitor_time (visitor_key, created_at),
    INDEX idx_pbe_event_time (event_type, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (property_id) REFERENCES properties(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_visitor_key_col := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'property_behavior_events'
      AND COLUMN_NAME = 'visitor_key'
);
SET @sql_add_visitor_key_col := IF(@has_visitor_key_col = 0,
    'ALTER TABLE property_behavior_events ADD COLUMN visitor_key VARCHAR(64) NULL AFTER user_id',
    'SELECT 1');
PREPARE stmt_add_visitor_key_col FROM @sql_add_visitor_key_col;
EXECUTE stmt_add_visitor_key_col;
DEALLOCATE PREPARE stmt_add_visitor_key_col;

SET @has_pbe_visitor_idx := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'property_behavior_events'
      AND INDEX_NAME = 'idx_pbe_visitor_time'
);
SET @sql_add_pbe_visitor_idx := IF(@has_pbe_visitor_idx = 0,
    'ALTER TABLE property_behavior_events ADD INDEX idx_pbe_visitor_time (visitor_key, created_at)',
    'SELECT 1');
PREPARE stmt_add_pbe_visitor_idx FROM @sql_add_pbe_visitor_idx;
EXECUTE stmt_add_pbe_visitor_idx;
DEALLOCATE PREPARE stmt_add_pbe_visitor_idx;

SET @sql_user_nullable := 'ALTER TABLE property_behavior_events MODIFY COLUMN user_id INT NULL';
PREPARE stmt_user_nullable FROM @sql_user_nullable;
EXECUTE stmt_user_nullable;
DEALLOCATE PREPARE stmt_user_nullable;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL,
    value VARCHAR(255) NOT NULL,
    label VARCHAR(150) NOT NULL,
    description TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_settings_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (`key`, value, label, description) VALUES
    ('behavior_ranking_enabled', '0', 'Ranking comportamental ativado', '0 desativado (safe mode), 1 ativado.'),
    ('behavior_ranking_lookback_days', '90', 'Janela comportamental (dias)', 'Quantidade de dias usada para ler eventos comportamentais por utilizador.'),
    ('behavior_weight_view', '1', 'Peso: visualização', 'Peso aplicado ao evento de visualização de imóvel.'),
    ('behavior_weight_favorite', '4', 'Peso: favorito', 'Peso aplicado ao evento de favorito.'),
    ('behavior_weight_request', '8', 'Peso: solicitação', 'Peso aplicado ao evento de solicitação de imóvel.'),
    ('behavior_max_score_per_property', '50', 'Teto de score por imóvel', 'Limite máximo de score comportamental aplicado por imóvel para o mesmo visitante/utilizador.')
ON DUPLICATE KEY UPDATE
    value = VALUES(value),
    label = VALUES(label),
    description = VALUES(description);
