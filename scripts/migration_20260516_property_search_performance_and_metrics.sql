-- Migration: Add property search performance columns, indexes and metric events tracking

ALTER TABLE properties
    ADD COLUMN IF NOT EXISTS latitude DOUBLE NULL AFTER location,
    ADD COLUMN IF NOT EXISTS longitude DOUBLE NULL AFTER latitude,
    ADD COLUMN IF NOT EXISTS has_garage TINYINT(1) NOT NULL DEFAULT 0 AFTER featured,
    ADD COLUMN IF NOT EXISTS has_pool TINYINT(1) NOT NULL DEFAULT 0 AFTER has_garage,
    ADD COLUMN IF NOT EXISTS has_elevator TINYINT(1) NOT NULL DEFAULT 0 AFTER has_pool,
    ADD COLUMN IF NOT EXISTS has_security TINYINT(1) NOT NULL DEFAULT 0 AFTER has_elevator;

ALTER TABLE properties
    ADD INDEX IF NOT EXISTS idx_properties_status_featured (status, featured),
    ADD INDEX IF NOT EXISTS idx_properties_country_region (country_id, region_id),
    ADD INDEX IF NOT EXISTS idx_properties_price (price),
    ADD INDEX IF NOT EXISTS idx_properties_bedrooms (bedrooms),
    ADD INDEX IF NOT EXISTS idx_properties_bathrooms (bathrooms),
    ADD INDEX IF NOT EXISTS idx_properties_area (area);

CREATE TABLE IF NOT EXISTS metric_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    metadata TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_events_event_type_created_at (event_type, created_at),
    INDEX idx_metric_events_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add a fulltext search index for property discovery if supported by the database.
ALTER TABLE properties
    ADD FULLTEXT INDEX idx_properties_fulltext_title_description_location (title, description, location);
