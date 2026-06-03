-- Migration: saved_searches table for user-saved filters and notifications
-- Date: 2026-05-16

USE imobil_db;

CREATE TABLE IF NOT EXISTS saved_searches (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    filters JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_saved_searches_user (user_id)
);

-- Optional: if you want to notify users when new properties match saved searches, run a worker that queries this table.
