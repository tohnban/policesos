-- Migration: countries + regions catalog and property location linkage
-- Date: 2026-05-13

CREATE TABLE IF NOT EXISTS countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(120) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_countries_name (name),
    INDEX idx_countries_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO countries (code, name, sort_order) VALUES
    ('AO', 'Angola', 10)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    sort_order = VALUES(sort_order);

CREATE TABLE IF NOT EXISTS regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(120) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_regions_country (country_id),
    INDEX idx_regions_name (name),
    INDEX idx_regions_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO regions (country_id, code, name, sort_order)
SELECT c.id, r.code, r.name, r.sort_order
FROM countries c
JOIN (
    SELECT 'luanda' AS code, 'Luanda' AS name, 10 AS sort_order
    UNION ALL SELECT 'bengo', 'Bengo', 20
    UNION ALL SELECT 'benguela', 'Benguela', 30
    UNION ALL SELECT 'bie', 'Bié', 40
    UNION ALL SELECT 'cabinda', 'Cabinda', 50
    UNION ALL SELECT 'cuando_cubango', 'Cuando Cubango', 60
    UNION ALL SELECT 'cuanza_norte', 'Cuanza Norte', 70
    UNION ALL SELECT 'cuanza_sul', 'Cuanza Sul', 80
    UNION ALL SELECT 'cunene', 'Cunene', 90
    UNION ALL SELECT 'huambo', 'Huambo', 100
    UNION ALL SELECT 'huila', 'Huíla', 110
    UNION ALL SELECT 'malanje', 'Malanje', 120
    UNION ALL SELECT 'moxico', 'Moxico', 130
    UNION ALL SELECT 'namibe', 'Namibe', 140
    UNION ALL SELECT 'uige', 'Uíge', 150
    UNION ALL SELECT 'zaire', 'Zaire', 160
    UNION ALL SELECT 'lunda_norte', 'Lunda Norte', 170
    UNION ALL SELECT 'lunda_sul', 'Lunda Sul', 180
) r
WHERE c.code = 'AO'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    country_id = VALUES(country_id),
    sort_order = VALUES(sort_order);

SET @has_regions_country_col := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'regions'
      AND COLUMN_NAME = 'country_id'
);
SET @sql_add_regions_country_col := IF(@has_regions_country_col = 0,
    'ALTER TABLE regions ADD COLUMN country_id INT NOT NULL AFTER id',
    'SELECT 1');
PREPARE stmt_add_regions_country_col FROM @sql_add_regions_country_col;
EXECUTE stmt_add_regions_country_col;
DEALLOCATE PREPARE stmt_add_regions_country_col;

SET @has_regions_country_idx := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'regions'
      AND INDEX_NAME = 'idx_regions_country'
);
SET @sql_add_regions_country_idx := IF(@has_regions_country_idx = 0,
    'ALTER TABLE regions ADD INDEX idx_regions_country (country_id)',
    'SELECT 1');
PREPARE stmt_add_regions_country_idx FROM @sql_add_regions_country_idx;
EXECUTE stmt_add_regions_country_idx;
DEALLOCATE PREPARE stmt_add_regions_country_idx;

SET @has_regions_country_fk := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'regions'
      AND CONSTRAINT_NAME = 'fk_regions_country'
);
SET @sql_add_regions_country_fk := IF(@has_regions_country_fk = 0,
    'ALTER TABLE regions ADD CONSTRAINT fk_regions_country FOREIGN KEY (country_id) REFERENCES countries(id)',
    'SELECT 1');
PREPARE stmt_add_regions_country_fk FROM @sql_add_regions_country_fk;
EXECUTE stmt_add_regions_country_fk;
DEALLOCATE PREPARE stmt_add_regions_country_fk;

SET @default_country_id := (SELECT id FROM countries WHERE code = 'AO' LIMIT 1);
UPDATE regions SET country_id = @default_country_id WHERE country_id IS NULL OR country_id = 0;

SET @has_country_col := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'properties'
      AND COLUMN_NAME = 'country_id'
);
SET @sql_add_country_col := IF(@has_country_col = 0,
    'ALTER TABLE properties ADD COLUMN country_id INT NULL AFTER price',
    'SELECT 1');
PREPARE stmt_add_country_col FROM @sql_add_country_col;
EXECUTE stmt_add_country_col;
DEALLOCATE PREPARE stmt_add_country_col;

SET @has_country_idx := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'properties'
      AND INDEX_NAME = 'idx_properties_country'
);
SET @sql_add_country_idx := IF(@has_country_idx = 0,
    'ALTER TABLE properties ADD INDEX idx_properties_country (country_id)',
    'SELECT 1');
PREPARE stmt_add_country_idx FROM @sql_add_country_idx;
EXECUTE stmt_add_country_idx;
DEALLOCATE PREPARE stmt_add_country_idx;

SET @has_country_status_idx := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'properties'
      AND INDEX_NAME = 'idx_properties_country_status'
);
SET @sql_add_country_status_idx := IF(@has_country_status_idx = 0,
    'ALTER TABLE properties ADD INDEX idx_properties_country_status (country_id, status, created_at)',
    'SELECT 1');
PREPARE stmt_add_country_status_idx FROM @sql_add_country_status_idx;
EXECUTE stmt_add_country_status_idx;
DEALLOCATE PREPARE stmt_add_country_status_idx;

SET @has_country_fk := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'properties'
      AND CONSTRAINT_NAME = 'fk_properties_country'
);
SET @sql_add_country_fk := IF(@has_country_fk = 0,
    'ALTER TABLE properties ADD CONSTRAINT fk_properties_country FOREIGN KEY (country_id) REFERENCES countries(id)',
    'SELECT 1');
PREPARE stmt_add_country_fk FROM @sql_add_country_fk;
EXECUTE stmt_add_country_fk;
DEALLOCATE PREPARE stmt_add_country_fk;

UPDATE properties p
LEFT JOIN regions r ON r.id = p.region_id
SET p.country_id = r.country_id
WHERE p.country_id IS NULL
    AND p.region_id IS NOT NULL
    AND r.country_id IS NOT NULL;

SET @has_region_col := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'properties'
      AND COLUMN_NAME = 'region_id'
);
SET @sql_add_region_col := IF(@has_region_col = 0,
    'ALTER TABLE properties ADD COLUMN region_id INT NULL AFTER price',
    'SELECT 1');
PREPARE stmt_add_region_col FROM @sql_add_region_col;
EXECUTE stmt_add_region_col;
DEALLOCATE PREPARE stmt_add_region_col;

SET @has_region_idx := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'properties'
      AND INDEX_NAME = 'idx_properties_region'
);
SET @sql_add_region_idx := IF(@has_region_idx = 0,
    'ALTER TABLE properties ADD INDEX idx_properties_region (region_id)',
    'SELECT 1');
PREPARE stmt_add_region_idx FROM @sql_add_region_idx;
EXECUTE stmt_add_region_idx;
DEALLOCATE PREPARE stmt_add_region_idx;

SET @has_region_status_idx := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'properties'
      AND INDEX_NAME = 'idx_properties_region_status'
);
SET @sql_add_region_status_idx := IF(@has_region_status_idx = 0,
    'ALTER TABLE properties ADD INDEX idx_properties_region_status (region_id, status, created_at)',
    'SELECT 1');
PREPARE stmt_add_region_status_idx FROM @sql_add_region_status_idx;
EXECUTE stmt_add_region_status_idx;
DEALLOCATE PREPARE stmt_add_region_status_idx;

SET @has_region_fk := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'properties'
      AND CONSTRAINT_NAME = 'fk_properties_region'
);
SET @sql_add_region_fk := IF(@has_region_fk = 0,
    'ALTER TABLE properties ADD CONSTRAINT fk_properties_region FOREIGN KEY (region_id) REFERENCES regions(id)',
    'SELECT 1');
PREPARE stmt_add_region_fk FROM @sql_add_region_fk;
EXECUTE stmt_add_region_fk;
DEALLOCATE PREPARE stmt_add_region_fk;
