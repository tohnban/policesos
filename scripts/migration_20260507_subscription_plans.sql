-- Migration: Official subscription plans and user subscriptions
-- Date: 2026-05-07

USE imobil_db;

START TRANSACTION;

CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(120) NOT NULL,
    monthly_price_aoa DECIMAL(12,2) NOT NULL DEFAULT 0,
    max_active_properties INT NULL,
    ranking_weight INT NOT NULL DEFAULT 0,
    visibility_tier ENUM('basic', 'premium') NOT NULL DEFAULT 'basic',
    has_featured_in_results BOOLEAN NOT NULL DEFAULT FALSE,
    has_reports BOOLEAN NOT NULL DEFAULT FALSE,
    has_advanced_reports BOOLEAN NOT NULL DEFAULT FALSE,
    has_priority_support BOOLEAN NOT NULL DEFAULT FALSE,
    has_auto_renew BOOLEAN NOT NULL DEFAULT FALSE,
    has_institutional_page BOOLEAN NOT NULL DEFAULT FALSE,
    is_custom_pricing BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_subscription_plans_code (code),
    INDEX idx_subscription_plans_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_subscriptions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('pending_activation', 'active', 'past_due', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    grace_until DATETIME NULL,
    auto_renew BOOLEAN NOT NULL DEFAULT FALSE,
    negotiated_price_aoa DECIMAL(12,2) NULL,
    billing_cycle_months INT NOT NULL DEFAULT 1,
    last_payment_transaction_id BIGINT NULL,
    notes VARCHAR(255) NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_user_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id),
    CONSTRAINT fk_user_subscriptions_payment_tx FOREIGN KEY (last_payment_transaction_id) REFERENCES payment_transactions(id),
    CONSTRAINT fk_user_subscriptions_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_user_subscriptions_user_status_dates (user_id, status, starts_at, ends_at),
    INDEX idx_user_subscriptions_status_end (status, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscription_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    from_plan_id INT NULL,
    to_plan_id INT NULL,
    event_type ENUM('activated', 'renewed', 'upgraded', 'downgraded', 'cancelled', 'expired', 'payment_failed', 'manual_adjustment') NOT NULL,
    metadata JSON NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_subscription_events_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_subscription_events_from_plan FOREIGN KEY (from_plan_id) REFERENCES subscription_plans(id),
    CONSTRAINT fk_subscription_events_to_plan FOREIGN KEY (to_plan_id) REFERENCES subscription_plans(id),
    CONSTRAINT fk_subscription_events_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_subscription_events_user_created (user_id, created_at),
    INDEX idx_subscription_events_type_created (event_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO subscription_plans (
    code,
    name,
    monthly_price_aoa,
    max_active_properties,
    ranking_weight,
    visibility_tier,
    has_featured_in_results,
    has_reports,
    has_advanced_reports,
    has_priority_support,
    has_auto_renew,
    has_institutional_page,
    is_custom_pricing,
    is_active
) VALUES
    ('essential', 'Plano Essencial', 0.00, 3, 0, 'basic', FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, TRUE),
    ('professional', 'Plano Profissional', 25000.00, 15, 50, 'premium', TRUE, TRUE, FALSE, TRUE, TRUE, FALSE, FALSE, TRUE),
    ('enterprise', 'Plano Empresarial', 100000.00, NULL, 100, 'premium', TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    monthly_price_aoa = VALUES(monthly_price_aoa),
    max_active_properties = VALUES(max_active_properties),
    ranking_weight = VALUES(ranking_weight),
    visibility_tier = VALUES(visibility_tier),
    has_featured_in_results = VALUES(has_featured_in_results),
    has_reports = VALUES(has_reports),
    has_advanced_reports = VALUES(has_advanced_reports),
    has_priority_support = VALUES(has_priority_support),
    has_auto_renew = VALUES(has_auto_renew),
    has_institutional_page = VALUES(has_institutional_page),
    is_custom_pricing = VALUES(is_custom_pricing),
    is_active = VALUES(is_active);

INSERT INTO user_subscriptions (
    user_id,
    plan_id,
    status,
    starts_at,
    auto_renew,
    billing_cycle_months,
    notes
)
SELECT
    u.id,
    sp.id,
    'active',
    NOW(),
    CASE WHEN sp.code = 'professional' THEN TRUE ELSE FALSE END,
    1,
    'Backfill inicial a partir de account_plan legado'
FROM users u
JOIN subscription_plans sp
    ON sp.code = CASE WHEN COALESCE(u.account_plan, 'free') = 'premium' THEN 'professional' ELSE 'essential' END
LEFT JOIN user_subscriptions us
    ON us.user_id = u.id
   AND us.status = 'active'
   AND us.starts_at <= NOW()
   AND (us.ends_at IS NULL OR us.ends_at >= NOW())
WHERE us.id IS NULL;

-- Extend payment transaction type enum to support subscription billing.
SET @has_subscription_fee := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'payment_transactions'
      AND COLUMN_NAME = 'transaction_type'
      AND COLUMN_TYPE LIKE '%subscription_fee%'
);
SET @sql_tx_enum := IF(
    @has_subscription_fee = 0,
    "ALTER TABLE payment_transactions MODIFY COLUMN transaction_type ENUM('commission_payout','system_commission','boost_fee','trust_badge_fee','manual_adjustment','subscription_fee') NOT NULL",
    'SELECT 1'
);
PREPARE stmt_tx_enum FROM @sql_tx_enum;
EXECUTE stmt_tx_enum;
DEALLOCATE PREPARE stmt_tx_enum;

COMMIT;
