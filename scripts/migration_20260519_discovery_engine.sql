-- Discovery engine: decay, penalties, exploration and impressions
-- Date: 2026-05-19

CREATE TABLE IF NOT EXISTS property_impressions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    visitor_key VARCHAR(64) NULL,
    property_id INT NOT NULL,
    surface VARCHAR(64) NOT NULL,
    shown_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pi_property (property_id),
    INDEX idx_pi_visitor_surface_time (visitor_key, surface, shown_at),
    INDEX idx_pi_user_surface_time (user_id, surface, shown_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (`key`, value, label, description) VALUES
    ('behavior_decay_lambda', '0.035', 'Ranking: decaimento temporal (lambda)', 'Fator de decaimento por dia nos eventos (maior = esquece mais rápido).'),
    ('behavior_view_penalty_threshold', '4', 'Ranking: limiar de views sem conversão', 'Número de visualizações sem favorito/pedido que aplica penalização.'),
    ('behavior_view_penalty_points', '6', 'Ranking: pontos de penalização', 'Pontos subtraídos quando o limiar de views sem conversão é atingido.'),
    ('behavior_explore_ratio', '15', 'Discovery: % exploração', 'Percentagem de slots preenchidos com imóveis novos no perfil (0-30).'),
    ('behavior_impression_cooldown_hours', '24', 'Discovery: cooldown de impressão (horas)', 'Horas antes de repetir o mesmo imóvel na mesma superfície.'),
    ('behavior_home_carousel_size', '8', 'Discovery: tamanho do carrossel na home', 'Quantidade de imóveis exibidos no carrossel personalizado.'),
    ('behavior_continue_exploring_size', '6', 'Discovery: bloco continuar a explorar', 'Quantidade de imóveis no bloco de retoma.'),
    ('behavior_promoted_interval', '4', 'Discovery: intervalo de patrocinados', 'A cada N imóveis na grelha mistura um patrocinado (featured).')
ON DUPLICATE KEY UPDATE
    value = VALUES(value),
    label = VALUES(label),
    description = VALUES(description);
