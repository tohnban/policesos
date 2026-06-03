-- Migration: API token authentication and rate limiting
-- Date: 2026-05-16

USE imobil_db;

CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    token VARCHAR(128) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    scopes VARCHAR(255) NOT NULL DEFAULT 'read:properties',
    status ENUM('active', 'revoked') NOT NULL DEFAULT 'active',
    expires_at DATETIME NULL,
    last_used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_tokens_status_expires (status, expires_at),
    INDEX idx_api_tokens_user_id (user_id)
);

INSERT INTO settings (`key`, value, label, description)
VALUES
  ('api_rate_limit_max', '300', 'API rate limit', 'Número máximo de pedidos API válidos por IP ou token por janela.'),
  ('api_rate_limit_window_seconds', '60', 'Janela de rate limit API', 'Janela de tempo em segundos para aplicar a limitação de pedidos API.'),
  ('api_token_ttl_days', '365', 'Validade dos tokens API (dias)', 'Tempo padrão de validade para novos tokens de API em dias.')
ON DUPLICATE KEY UPDATE value = VALUES(value), label = VALUES(label), description = VALUES(description);
