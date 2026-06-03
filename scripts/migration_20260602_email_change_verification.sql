-- Alteração de email no perfil: email pendente até confirmação do link.

ALTER TABLE email_verifications
    ADD COLUMN pending_email VARCHAR(255) NULL DEFAULT NULL AFTER user_id;
