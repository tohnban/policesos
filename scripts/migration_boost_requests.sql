-- Migration: property_boost_requests table
-- Run manually via phpMyAdmin or mysql CLI before deploying this feature.
-- Date: 2025

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `property_boost_requests` (
    `id`                INT          AUTO_INCREMENT PRIMARY KEY,
    `property_id`       INT          NOT NULL,
    `user_id`           INT          NOT NULL,
    `boost_type`        ENUM('destaque','premium') NOT NULL DEFAULT 'destaque',
    `duration_days`     INT          NOT NULL DEFAULT 30,
    `payment_reference` VARCHAR(200) DEFAULT NULL,
    `status`            ENUM('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
    `notes`             TEXT         DEFAULT NULL,
    `requested_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `approved_at`       TIMESTAMP    NULL DEFAULT NULL,
    `rejected_at`       TIMESTAMP    NULL DEFAULT NULL,
    `expires_at`        TIMESTAMP    NULL DEFAULT NULL,
    CONSTRAINT `fk_pbr_property` FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pbr_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)      ON DELETE CASCADE,
    INDEX `idx_pbr_status` (`status`),
    INDEX `idx_pbr_property` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
