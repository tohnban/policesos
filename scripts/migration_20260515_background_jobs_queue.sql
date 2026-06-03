-- Migration: background jobs queue for async tasks (email first)
-- Date: 2026-05-15

USE imobil_db;

CREATE TABLE IF NOT EXISTS background_jobs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    queue_name VARCHAR(60) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    priority TINYINT NOT NULL DEFAULT 5,
    attempts INT NOT NULL DEFAULT 0,
    max_attempts INT NOT NULL DEFAULT 5,
    run_after DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    locked_at DATETIME NULL,
    locked_by VARCHAR(80) NULL,
    completed_at DATETIME NULL,
    failed_at DATETIME NULL,
    last_error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_jobs_status_queue_run_after (status, queue_name, run_after),
    INDEX idx_jobs_locked_at_status (locked_at, status),
    INDEX idx_jobs_priority_created (priority, created_at)
);

INSERT INTO settings (`key`, value, label, description)
VALUES
  ('mail_queue_enabled', '1', 'Fila assíncrona de emails ativa', 'Quando 1, envios de email entram na fila e são processados por worker.'),
  ('mail_queue_max_attempts', '5', 'Máximo de tentativas por email', 'Número máximo de tentativas de envio por job de email.'),
  ('mail_queue_batch_size', '20', 'Lote por execução do worker', 'Quantidade de jobs processados por ciclo do worker.'),
  ('mail_queue_lock_timeout_seconds', '300', 'Timeout de lock do worker (s)', 'Tempo para considerar job processing como órfão e devolver para pending.')
ON DUPLICATE KEY UPDATE value = VALUES(value), label = VALUES(label), description = VALUES(description);
