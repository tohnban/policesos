-- Migration 2026-04-21
-- Payment Hub foundation:
-- 1) payment_methods: catalog of available payment methods.
-- 2) system_payment_channels: system-owned channels/accounts per method.
-- 3) user_payment_accounts: user-owned payout/payment data.
-- 4) payment_transactions: unified ledger for system/user payment operations.

USE imobil_db;

START TRANSACTION;

CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(120) NOT NULL,
    direction ENUM('incoming', 'outgoing', 'both') NOT NULL DEFAULT 'both',
    audience ENUM('system', 'user', 'both') NOT NULL DEFAULT 'both',
    requires_reference BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_payment_method_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_payment_channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_id INT NOT NULL,
    channel_name VARCHAR(150) NOT NULL,
    account_name VARCHAR(150) NULL,
    account_number VARCHAR(120) NULL,
    iban VARCHAR(80) NULL,
    bank_name VARCHAR(120) NULL,
    wallet_provider VARCHAR(80) NULL,
    instructions TEXT NULL,
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_spc_method FOREIGN KEY (method_id) REFERENCES payment_methods(id),
    INDEX idx_spc_method_active (method_id, is_active),
    INDEX idx_spc_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_payment_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    method_id INT NOT NULL,
    account_label VARCHAR(120) NULL,
    account_name VARCHAR(150) NULL,
    account_number VARCHAR(120) NULL,
    iban VARCHAR(80) NULL,
    bank_name VARCHAR(120) NULL,
    wallet_provider VARCHAR(80) NULL,
    phone_number VARCHAR(30) NULL,
    metadata JSON NULL,
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_upa_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_upa_method FOREIGN KEY (method_id) REFERENCES payment_methods(id),
    INDEX idx_upa_user_active (user_id, is_active),
    INDEX idx_upa_user_default (user_id, is_default),
    INDEX idx_upa_method (method_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_transactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    transaction_type ENUM('commission_payout', 'boost_fee', 'trust_badge_fee', 'manual_adjustment') NOT NULL,
    direction ENUM('incoming', 'outgoing') NOT NULL,
    status ENUM('pendente', 'processando', 'confirmado', 'cancelado', 'falhado') NOT NULL DEFAULT 'pendente',
    amount DECIMAL(15,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'AOA',
    method_id INT NULL,
    system_channel_id INT NULL,
    user_account_id INT NULL,
    counterparty_user_id INT NULL,
    related_entity_type VARCHAR(40) NULL,
    related_entity_id INT NULL,
    reference_code VARCHAR(120) NULL,
    proof_file VARCHAR(255) NULL,
    notes TEXT NULL,
    created_by INT NULL,
    confirmed_by INT NULL,
    confirmed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pt_method FOREIGN KEY (method_id) REFERENCES payment_methods(id),
    CONSTRAINT fk_pt_system_channel FOREIGN KEY (system_channel_id) REFERENCES system_payment_channels(id),
    CONSTRAINT fk_pt_user_account FOREIGN KEY (user_account_id) REFERENCES user_payment_accounts(id),
    CONSTRAINT fk_pt_counterparty_user FOREIGN KEY (counterparty_user_id) REFERENCES users(id),
    CONSTRAINT fk_pt_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_pt_confirmed_by FOREIGN KEY (confirmed_by) REFERENCES users(id),
    INDEX idx_pt_status_created (status, created_at),
    INDEX idx_pt_type_created (transaction_type, created_at),
    INDEX idx_pt_entity (related_entity_type, related_entity_id),
    INDEX idx_pt_counterparty (counterparty_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO payment_methods (code, name, direction, audience, requires_reference, is_active)
VALUES
    ('bank_transfer', 'Transferencia Bancaria', 'both', 'both', TRUE, TRUE),
    ('multicaixa_express', 'Multicaixa Express', 'both', 'both', TRUE, TRUE),
    ('mobile_wallet', 'Carteira Movel', 'both', 'both', TRUE, TRUE),
    ('cash', 'Numerario', 'incoming', 'system', FALSE, TRUE)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    direction = VALUES(direction),
    audience = VALUES(audience),
    requires_reference = VALUES(requires_reference),
    is_active = VALUES(is_active);

COMMIT;
