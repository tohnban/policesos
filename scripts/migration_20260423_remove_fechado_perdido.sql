-- Migration: Remove fechado_perdido from requests lifecycle
-- Date: 2026-04-23
-- Purpose: Align commercial lifecycle to em_contacto/fechado_ganho/cancelado/expirado

USE imobil_db;

-- 1) Normalize existing data
UPDATE requests
SET status = 'cancelado'
WHERE status = 'fechado_perdido';

UPDATE requests
SET commercial_status = 'cancelado'
WHERE commercial_status = 'fechado_perdido';

-- 2) Remove fechado_perdido from request status enums
ALTER TABLE requests
    MODIFY COLUMN status ENUM(
        'em_contacto',
        'fechado_ganho',
        'cancelado',
        'expirado',
        'em_disputa'
    ) NOT NULL DEFAULT 'em_contacto';

ALTER TABLE requests
    MODIFY COLUMN commercial_status ENUM(
        'em_contacto',
        'fechado_ganho',
        'cancelado',
        'expirado'
    ) NULL DEFAULT NULL;
