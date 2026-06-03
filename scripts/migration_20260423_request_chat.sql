-- Migration: request negotiation chat
-- Date: 2026-04-23
-- Purpose: create one chat thread per request and persist negotiation messages

USE imobil_db;

CREATE TABLE IF NOT EXISTS request_chat_threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    status ENUM('ativo', 'bloqueado', 'encerrado') NOT NULL DEFAULT 'ativo',
    last_message_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_request_chat_thread (request_id),
    CONSTRAINT fk_request_chat_threads_request FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS request_chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    sender_user_id INT NOT NULL,
    message_type ENUM('text', 'system') NOT NULL DEFAULT 'text',
    message_text TEXT NOT NULL,
    attachment_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    edited_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_request_chat_messages_thread_created (thread_id, created_at),
    CONSTRAINT fk_request_chat_messages_thread FOREIGN KEY (thread_id) REFERENCES request_chat_threads(id) ON DELETE CASCADE,
    CONSTRAINT fk_request_chat_messages_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE
);
