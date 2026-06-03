-- Migration: Add date tracking columns to requests table
-- Date: 2026-04-23
-- Purpose: Precise timestamps for contact start, dispute window, and last interaction

USE imobil_db;

-- Add new date tracking columns
ALTER TABLE requests
    ADD COLUMN contact_started_at  TIMESTAMP NULL DEFAULT NULL AFTER closing_confirmed_at,
    ADD COLUMN dispute_open_until  TIMESTAMP NULL DEFAULT NULL AFTER contact_started_at,
    ADD COLUMN last_interaction_at TIMESTAMP NULL DEFAULT NULL AFTER dispute_open_until;

-- Backfill: contact_started_at from created_at for all existing requests
UPDATE requests
SET contact_started_at = created_at
WHERE contact_started_at IS NULL;

-- Backfill: last_interaction_at from updated_at (or created_at fallback)
UPDATE requests
SET last_interaction_at = COALESCE(updated_at, created_at)
WHERE last_interaction_at IS NULL;

-- Backfill: dispute_open_until for requests already in a dispute-eligible status
-- Window is 30 days from when the status was last updated
UPDATE requests
SET dispute_open_until = DATE_ADD(COALESCE(updated_at, created_at), INTERVAL 30 DAY)
WHERE status IN ('fechado_ganho', 'cancelado')
  AND dispute_open_until IS NULL;
