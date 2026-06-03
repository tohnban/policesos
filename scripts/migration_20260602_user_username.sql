-- Nome de utilizador público (obrigatório) e registo da primeira alteração manual (cooldown 90 dias).

ALTER TABLE users
    ADD COLUMN username VARCHAR(32) NULL AFTER name,
    ADD COLUMN username_changed_at TIMESTAMP NULL DEFAULT NULL AFTER username;

-- Contas existentes: identificador estável até o utilizador personalizar no perfil.
UPDATE users
SET username = CONCAT('u', id)
WHERE username IS NULL OR TRIM(username) = '';

ALTER TABLE users
    MODIFY COLUMN username VARCHAR(32) NOT NULL,
    ADD UNIQUE INDEX uq_users_username (username);
