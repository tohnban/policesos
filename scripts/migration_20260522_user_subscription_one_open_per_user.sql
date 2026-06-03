-- Migration: enforce one open subscription per user (cleanup duplicates)
-- Date: 2026-05-22

USE imobil_db;

START TRANSACTION;

-- Keep the canonical open row per user; cancel the rest.
UPDATE user_subscriptions us
INNER JOIN (
    SELECT user_id,
        COALESCE(
            MAX(CASE WHEN status = 'pending_activation' THEN id END),
            MAX(CASE WHEN status = 'past_due' AND grace_until IS NOT NULL AND grace_until >= NOW() THEN id END),
            MAX(CASE WHEN status = 'active' AND starts_at <= NOW()
                AND (ends_at IS NULL OR ends_at >= NOW()) THEN id END),
            MAX(id)
        ) AS keep_id
    FROM user_subscriptions
    WHERE status IN ('pending_activation', 'active', 'past_due')
    GROUP BY user_id
) pick ON pick.user_id = us.user_id
SET us.status = 'cancelled',
    us.ends_at = NOW(),
    us.auto_renew = 0,
    us.grace_until = NULL,
    us.notes = CONCAT(COALESCE(us.notes, ''), ' | deduplicação: uma subscrição aberta por utilizador')
WHERE us.status IN ('pending_activation', 'active', 'past_due')
  AND us.id <> pick.keep_id;

COMMIT;
