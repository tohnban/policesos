-- Migration: optimize saved searches for efficient property matching
-- Date: 2026-05-16

USE imobil_db;

ALTER TABLE saved_searches
    ADD COLUMN search_type VARCHAR(80) NULL AFTER name,
    ADD COLUMN search_purpose VARCHAR(80) NULL AFTER search_type,
    ADD COLUMN country_id INT NULL AFTER search_purpose,
    ADD COLUMN region_id INT NULL AFTER country_id,
    ADD COLUMN min_price DECIMAL(14,2) NULL AFTER region_id,
    ADD COLUMN max_price DECIMAL(14,2) NULL AFTER min_price,
    ADD COLUMN min_area DECIMAL(14,2) NULL AFTER max_price,
    ADD COLUMN max_area DECIMAL(14,2) NULL AFTER min_area,
    ADD COLUMN bedrooms INT NULL AFTER max_area,
    ADD COLUMN bathrooms INT NULL AFTER bedrooms,
    ADD COLUMN search_keyword VARCHAR(255) NULL AFTER bathrooms,
    ADD COLUMN trusted_only TINYINT(1) DEFAULT 0 AFTER search_keyword;

UPDATE saved_searches
SET
    search_type = JSON_UNQUOTE(JSON_EXTRACT(filters, '$.type')),
    search_purpose = JSON_UNQUOTE(JSON_EXTRACT(filters, '$.purpose')),
    country_id = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(filters, '$.country_id')), '') + 0,
    region_id = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(filters, '$.region_id')), '') + 0,
    min_price = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(filters, '$.min_price')), '') + 0,
    max_price = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(filters, '$.max_price')), '') + 0,
    min_area = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(filters, '$.min_area')), '') + 0,
    max_area = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(filters, '$.max_area')), '') + 0,
    bedrooms = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(filters, '$.bedrooms')), '') + 0,
    bathrooms = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(filters, '$.bathrooms')), '') + 0,
    search_keyword = JSON_UNQUOTE(JSON_EXTRACT(filters, '$.keyword')),
    trusted_only = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(filters, '$.trusted_only')), '') + 0
WHERE filters IS NOT NULL;

ALTER TABLE saved_searches
    ADD INDEX idx_saved_searches_search_type (search_type),
    ADD INDEX idx_saved_searches_search_purpose (search_purpose),
    ADD INDEX idx_saved_searches_region (region_id),
    ADD INDEX idx_saved_searches_country (country_id),
    ADD INDEX idx_saved_searches_keyword (search_keyword),
    ADD INDEX idx_saved_searches_trusted_only (trusted_only);
