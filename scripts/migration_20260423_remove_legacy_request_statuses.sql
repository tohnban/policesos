-- Migration: Remove legacy request statuses from active lifecycle
-- Date: 2026-04-23
-- Purpose: Normalize historical rows and remove pendente/analise/proposta from request enums

USE imobil_db;

-- 1) Normalize historical legacy statuses to em_contacto
UPDATE requests
SET status = 'em_contacto'
WHERE status IN ('pendente', 'analise', 'proposta');

UPDATE requests
SET commercial_status = 'em_contacto'
WHERE commercial_status IN ('pendente', 'analise', 'proposta');

-- 2) Remove legacy values from status enum (keeps em_disputa for compatibility)
ALTER TABLE requests
    MODIFY COLUMN status ENUM(
        'em_contacto',
        'fechado_ganho',
        'cancelado',
        'expirado',
        'fechado_perdido',
        'em_disputa'
    ) NOT NULL DEFAULT 'em_contacto';

-- 3) Remove legacy values from commercial_status enum
ALTER TABLE requests
    MODIFY COLUMN commercial_status ENUM(
        'em_contacto',
        'fechado_ganho',
        'cancelado',
        'expirado',
        'fechado_perdido'
    ) NULL DEFAULT NULL;
