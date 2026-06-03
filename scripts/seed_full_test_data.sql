USE imobil_db;

START TRANSACTION;

-- Contas seed: email já verificado para permitir login imediato em desenvolvimento.
UPDATE users
SET email_verified_at = COALESCE(email_verified_at, NOW())
WHERE email LIKE '%.seed@imobil.local'
  AND email_verified_at IS NULL;

-- Password for all seeded users: Teste@123
SET @seed_password_hash = '$2y$10$ngFVnA/quB/XBxyk0eSTbOHp1TxojSW60zA40w.GbZI9GSKc9hmIC';

-- Seed users with complete role coverage.
INSERT INTO users (
    email, password, name, user_type, document_number, phone,
    is_affiliate, affiliate_code, is_admin, role, status,
    account_plan, trust_badge_status, trust_badge_requested_at,
    trust_badge_approved_at, trust_badge_fee_required, trust_badge_fee_paid,
    document_file, profile_photo, created_at
)
SELECT
    'admin.seed@imobil.local', @seed_password_hash, 'Admin Seed', 'pessoa_fisica', '90000000000001', '+244990000001',
    0, NULL, 1, 'super_admin', 'ativo',
    'premium', 'aprovado', NULL,
    NOW(), 0, 1,
    'seed_admin.pdf', NULL, DATE_SUB(NOW(), INTERVAL 120 DAY)
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'admin.seed@imobil.local'
);

INSERT INTO users (
    email, password, name, user_type, document_number, phone,
    is_affiliate, affiliate_code, is_admin, role, status,
    account_plan, trust_badge_status, trust_badge_requested_at,
    trust_badge_approved_at, trust_badge_fee_required, trust_badge_fee_paid,
    document_file, profile_photo, created_at
)
SELECT
    'moderador.seed@imobil.local', @seed_password_hash, 'Moderador Seed', 'pessoa_fisica', '90000000000002', '+244990000002',
    0, NULL, 0, 'moderador', 'ativo',
    'premium', 'nenhum', NULL,
    NULL, 0, 0,
    'seed_moderador.pdf', NULL, DATE_SUB(NOW(), INTERVAL 90 DAY)
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'moderador.seed@imobil.local'
);

INSERT INTO users (
    email, password, name, user_type, document_number, phone,
    is_affiliate, affiliate_code, is_admin, role, status,
    account_plan, trust_badge_status, trust_badge_requested_at,
    trust_badge_approved_at, trust_badge_fee_required, trust_badge_fee_paid,
    document_file, profile_photo, created_at
)
SELECT
    'financeiro.seed@imobil.local', @seed_password_hash, 'Financeiro Seed', 'pessoa_fisica', '90000000000003', '+244990000003',
    0, NULL, 0, 'financeiro', 'ativo',
    'premium', 'nenhum', NULL,
    NULL, 0, 0,
    'seed_financeiro.pdf', NULL, DATE_SUB(NOW(), INTERVAL 85 DAY)
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'financeiro.seed@imobil.local'
);

INSERT INTO users (
    email, password, name, user_type, document_number, phone,
    is_affiliate, affiliate_code, is_admin, role, status,
    account_plan, trust_badge_status, trust_badge_requested_at,
    trust_badge_approved_at, trust_badge_fee_required, trust_badge_fee_paid,
    document_file, profile_photo, created_at
)
SELECT
    'suporte.seed@imobil.local', @seed_password_hash, 'Suporte Seed', 'pessoa_fisica', '90000000000004', '+244990000004',
    0, NULL, 0, 'suporte', 'ativo',
    'free', 'nenhum', NULL,
    NULL, 0, 0,
    'seed_suporte.pdf', NULL, DATE_SUB(NOW(), INTERVAL 70 DAY)
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'suporte.seed@imobil.local'
);

INSERT INTO users (
    email, password, name, user_type, document_number, phone,
    is_affiliate, affiliate_code, is_admin, role, status,
    account_plan, trust_badge_status, trust_badge_requested_at,
    trust_badge_approved_at, trust_badge_fee_required, trust_badge_fee_paid,
    document_file, profile_photo, created_at
)
SELECT
    'afiliado.seed@imobil.local', @seed_password_hash, 'Afiliado Seed', 'pessoa_fisica', '90000000000005', '+244990000005',
    1, 'AFFSEED001', 0, 'utilizador', 'ativo',
    'premium', 'aprovado', DATE_SUB(NOW(), INTERVAL 40 DAY),
    DATE_SUB(NOW(), INTERVAL 35 DAY), 12000, 1,
    'seed_afiliado.pdf', NULL, DATE_SUB(NOW(), INTERVAL 65 DAY)
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'afiliado.seed@imobil.local'
);

INSERT INTO users (
    email, password, name, user_type, document_number, phone,
    is_affiliate, affiliate_code, is_admin, role, status,
    account_plan, trust_badge_status, trust_badge_requested_at,
    trust_badge_approved_at, trust_badge_fee_required, trust_badge_fee_paid,
    document_file, profile_photo, created_at
)
SELECT
    'proprietario.seed@imobil.local', @seed_password_hash, 'Proprietario Seed', 'pessoa_fisica', '90000000000006', '+244990000006',
    0, NULL, 0, 'utilizador', 'ativo',
    'premium', 'nenhum', NULL,
    NULL, 0, 0,
    'seed_proprietario.pdf', NULL, DATE_SUB(NOW(), INTERVAL 60 DAY)
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'proprietario.seed@imobil.local'
);

INSERT INTO users (
    email, password, name, user_type, document_number, phone,
    is_affiliate, affiliate_code, is_admin, role, status,
    account_plan, trust_badge_status, trust_badge_requested_at,
    trust_badge_approved_at, trust_badge_fee_required, trust_badge_fee_paid,
    document_file, profile_photo, created_at
)
SELECT
    'cliente.seed@imobil.local', @seed_password_hash, 'Cliente Seed', 'pessoa_fisica', '90000000000007', '+244990000007',
    0, NULL, 0, 'utilizador', 'ativo',
    'free', 'nenhum', NULL,
    NULL, 0, 0,
    'seed_cliente.pdf', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'cliente.seed@imobil.local'
);

INSERT INTO users (
    email, password, name, user_type, document_number, phone,
    is_affiliate, affiliate_code, is_admin, role, status,
    account_plan, trust_badge_status, trust_badge_requested_at,
    trust_badge_approved_at, trust_badge_fee_required, trust_badge_fee_paid,
    document_file, profile_photo, created_at
)
SELECT
    'pendente.seed@imobil.local', @seed_password_hash, 'Pendente Seed', 'pessoa_fisica', '90000000000008', '+244990000008',
    0, NULL, 0, 'utilizador', 'pendente',
    'free', 'pendente', DATE_SUB(NOW(), INTERVAL 2 DAY),
    NULL, 8000, 0,
    'seed_pendente.pdf', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY)
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'pendente.seed@imobil.local'
);

-- Seed properties owned by the seeded owner.
INSERT INTO properties (
    title, description, type, purpose, price, location, bedrooms, bathrooms, area,
    images, video_url, affiliate_id, owner_bonus_pct, visibility, featured, status, created_at
)
SELECT
    'Seed Casa Talatona', 'Imovel seed para fluxo de venda.', 'casa', 'venda', 250000.00, 'Talatona', 4, 3, 280.00,
    JSON_ARRAY('img/seed_casa_talatona.jpg'), NULL,
    (SELECT id FROM users WHERE email = 'proprietario.seed@imobil.local'),
    1.50, 'premium', 1, 'disponivel', DATE_SUB(NOW(), INTERVAL 20 DAY)
WHERE NOT EXISTS (
    SELECT 1 FROM properties WHERE title = 'Seed Casa Talatona' AND location = 'Talatona'
);

INSERT INTO properties (
    title, description, type, purpose, price, location, bedrooms, bathrooms, area,
    images, video_url, affiliate_id, owner_bonus_pct, visibility, featured, status, created_at
)
SELECT
    'Seed Apartamento Centro', 'Imovel seed para fluxo de aluguer.', 'apartamento', 'aluguer_longo', 1200.00, 'Luanda Centro', 2, 2, 95.00,
    JSON_ARRAY('img/seed_apto_centro.jpg'), NULL,
    (SELECT id FROM users WHERE email = 'proprietario.seed@imobil.local'),
    0.50, 'basic', 0, 'disponivel', DATE_SUB(NOW(), INTERVAL 15 DAY)
WHERE NOT EXISTS (
    SELECT 1 FROM properties WHERE title = 'Seed Apartamento Centro' AND location = 'Luanda Centro'
);

INSERT INTO properties (
    title, description, type, purpose, price, location, bedrooms, bathrooms, area,
    images, video_url, affiliate_id, owner_bonus_pct, visibility, featured, status, created_at
)
SELECT
    'Seed Terreno Viana', 'Imovel seed pendente para moderacao.', 'terreno', 'venda', 70000.00, 'Viana', 0, 0, 500.00,
    JSON_ARRAY('img/seed_terreno_viana.jpg'), NULL,
    (SELECT id FROM users WHERE email = 'proprietario.seed@imobil.local'),
    0.00, 'basic', 0, 'pendente', DATE_SUB(NOW(), INTERVAL 4 DAY)
WHERE NOT EXISTS (
    SELECT 1 FROM properties WHERE title = 'Seed Terreno Viana' AND location = 'Viana'
);

INSERT INTO properties (
    title, description, type, purpose, price, location, bedrooms, bathrooms, area,
    images, video_url, affiliate_id, owner_bonus_pct, visibility, featured, status, created_at
)
SELECT
    'Seed Vivenda Rejeitada', 'Imovel seed rejeitado para historico.', 'vivenda', 'venda', 540000.00, 'Kilamba', 5, 4, 420.00,
    JSON_ARRAY('img/seed_vivenda_rejeitada.jpg'), NULL,
    (SELECT id FROM users WHERE email = 'proprietario.seed@imobil.local'),
    2.00, 'premium', 0, 'rejeitado', DATE_SUB(NOW(), INTERVAL 3 DAY)
WHERE NOT EXISTS (
    SELECT 1 FROM properties WHERE title = 'Seed Vivenda Rejeitada' AND location = 'Kilamba'
);

-- Favorites.
INSERT INTO favorites (user_id, property_id, created_at)
SELECT u.id, p.id, DATE_SUB(NOW(), INTERVAL 6 DAY)
FROM users u
JOIN properties p ON p.title = 'Seed Casa Talatona' AND p.location = 'Talatona'
WHERE u.email = 'cliente.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM favorites f WHERE f.user_id = u.id AND f.property_id = p.id
  );

INSERT INTO favorites (user_id, property_id, created_at)
SELECT u.id, p.id, DATE_SUB(NOW(), INTERVAL 5 DAY)
FROM users u
JOIN properties p ON p.title = 'Seed Apartamento Centro' AND p.location = 'Luanda Centro'
WHERE u.email = 'cliente.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM favorites f WHERE f.user_id = u.id AND f.property_id = p.id
  );

-- Property affiliate request and approval scenarios.
INSERT INTO property_affiliates (user_id, property_id, status, created_at, approved_at, rejected_at)
SELECT u.id, p.id, 'ativo', DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY), NULL
FROM users u
JOIN properties p ON p.title = 'Seed Casa Talatona' AND p.location = 'Talatona'
WHERE u.email = 'afiliado.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM property_affiliates pa WHERE pa.user_id = u.id AND pa.property_id = p.id
  );

INSERT INTO property_affiliates (user_id, property_id, status, created_at, approved_at, rejected_at)
SELECT u.id, p.id, 'pendente', DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, NULL
FROM users u
JOIN properties p ON p.title = 'Seed Apartamento Centro' AND p.location = 'Luanda Centro'
WHERE u.email = 'afiliado.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM property_affiliates pa WHERE pa.user_id = u.id AND pa.property_id = p.id
  );

-- Requests for each state.
INSERT INTO requests (user_id, property_id, affiliate_id, type, status, message, created_at, updated_at)
SELECT
    c.id,
    p.id,
    a.id,
    'compra',
    'fechado_ganho',
    'SEED_REQ_FECHADO_GANHO',
    DATE_SUB(NOW(), INTERVAL 10 DAY),
    DATE_SUB(NOW(), INTERVAL 9 DAY)
FROM users c
JOIN users a ON a.email = 'afiliado.seed@imobil.local'
JOIN properties p ON p.title = 'Seed Casa Talatona' AND p.location = 'Talatona'
WHERE c.email = 'cliente.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM requests r WHERE r.message = 'SEED_REQ_FECHADO_GANHO'
  );

INSERT INTO requests (user_id, property_id, affiliate_id, type, status, message, created_at, updated_at)
SELECT
    c.id,
    p.id,
    a.id,
    'aluguer_longo',
    'em_contacto',
    'SEED_REQ_ANALISE',
    DATE_SUB(NOW(), INTERVAL 6 DAY),
    DATE_SUB(NOW(), INTERVAL 5 DAY)
FROM users c
JOIN users a ON a.email = 'afiliado.seed@imobil.local'
JOIN properties p ON p.title = 'Seed Apartamento Centro' AND p.location = 'Luanda Centro'
WHERE c.email = 'cliente.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM requests r WHERE r.message = 'SEED_REQ_ANALISE'
  );

INSERT INTO requests (user_id, property_id, affiliate_id, type, status, message, created_at, updated_at)
SELECT
    c.id,
    p.id,
    a.id,
    'compra',
    'em_contacto',
    'SEED_REQ_PENDENTE',
    DATE_SUB(NOW(), INTERVAL 2 DAY),
    DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM users c
JOIN users a ON a.email = 'afiliado.seed@imobil.local'
JOIN properties p ON p.title = 'Seed Casa Talatona' AND p.location = 'Talatona'
WHERE c.email = 'cliente.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM requests r WHERE r.message = 'SEED_REQ_PENDENTE'
  );

INSERT INTO requests (user_id, property_id, affiliate_id, type, status, message, created_at, updated_at)
SELECT
    c.id,
    p.id,
    a.id,
    'compra',
        'cancelado',
        'SEED_REQ_CANCELADO',
    DATE_SUB(NOW(), INTERVAL 8 DAY),
    DATE_SUB(NOW(), INTERVAL 7 DAY)
FROM users c
JOIN users a ON a.email = 'afiliado.seed@imobil.local'
JOIN properties p ON p.title = 'Seed Apartamento Centro' AND p.location = 'Luanda Centro'
WHERE c.email = 'cliente.seed@imobil.local'
  AND NOT EXISTS (
                        SELECT 1 FROM requests r WHERE r.message = 'SEED_REQ_CANCELADO'
  );

-- Commissions linked to closed-won requests.
INSERT INTO commissions (
    affiliate_id, property_id, request_id, amount,
    total_pct, system_pct, affiliate_pct,
    system_amount, affiliate_amount, status, paid_at, payment_reference, created_at
)
SELECT
    a.id,
    p.id,
    r.id,
    12500.00,
    5.00, 2.00, 3.00,
    5000.00, 7500.00,
    'pago',
    DATE_SUB(NOW(), INTERVAL 8 DAY),
    'SEED-PAG-001',
    DATE_SUB(NOW(), INTERVAL 9 DAY)
FROM users a
JOIN properties p ON p.title = 'Seed Casa Talatona' AND p.location = 'Talatona'
JOIN requests r ON r.message = 'SEED_REQ_FECHADO_GANHO'
WHERE a.email = 'afiliado.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM commissions c WHERE c.request_id = r.id
  );

INSERT INTO commissions (
    affiliate_id, property_id, request_id, amount,
    total_pct, system_pct, affiliate_pct,
    system_amount, affiliate_amount, status, paid_at, payment_reference, created_at
)
SELECT
    a.id,
    p.id,
    r.id,
    60.00,
    5.00, 2.00, 3.00,
    24.00, 36.00,
    'pendente',
    NULL,
    NULL,
    DATE_SUB(NOW(), INTERVAL 5 DAY)
FROM users a
JOIN properties p ON p.title = 'Seed Apartamento Centro' AND p.location = 'Luanda Centro'
JOIN requests r ON r.message = 'SEED_REQ_ANALISE'
WHERE a.email = 'afiliado.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM commissions c WHERE c.request_id = r.id
  );

-- Notifications.
INSERT INTO notifications (user_id, actor_id, type, title, message, metadata, is_read, created_at, read_at)
SELECT
    u.id,
    a.id,
    'request_status_updated',
    'Status atualizado',
    'Sua solicitacao seed foi atualizada para fechado_ganho.',
    JSON_OBJECT('request_tag', 'SEED_REQ_FECHADO_GANHO'),
    0,
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    NULL
FROM users u
JOIN users a ON a.email = 'moderador.seed@imobil.local'
WHERE u.email = 'cliente.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM notifications n
      WHERE n.user_id = u.id AND n.type = 'request_status_updated' AND JSON_EXTRACT(n.metadata, '$.request_tag') = 'SEED_REQ_FECHADO_GANHO'
  );

INSERT INTO notifications (user_id, actor_id, type, title, message, metadata, is_read, created_at, read_at)
SELECT
    u.id,
    a.id,
    'commission_paid',
    'Comissao paga',
    'Comissao seed paga com referencia SEED-PAG-001.',
    JSON_OBJECT('reference', 'SEED-PAG-001'),
    1,
    DATE_SUB(NOW(), INTERVAL 7 DAY),
    DATE_SUB(NOW(), INTERVAL 6 DAY)
FROM users u
JOIN users a ON a.email = 'financeiro.seed@imobil.local'
WHERE u.email = 'afiliado.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM notifications n
      WHERE n.user_id = u.id AND n.type = 'commission_paid' AND JSON_EXTRACT(n.metadata, '$.reference') = 'SEED-PAG-001'
  );

-- Documents for compliance screens.
INSERT INTO documents (user_id, property_id, type, filename, version, status, rejection_reason, reviewed_by, reviewed_at, created_at, updated_at)
SELECT
    u.id,
    NULL,
    'user_registration',
    'seed_user_doc_aprovado_v1.pdf',
    'v1',
    'aprovado',
    NULL,
    a.id,
    DATE_SUB(NOW(), INTERVAL 34 DAY),
    DATE_SUB(NOW(), INTERVAL 40 DAY),
    DATE_SUB(NOW(), INTERVAL 34 DAY)
FROM users u
JOIN users a ON a.email = 'moderador.seed@imobil.local'
WHERE u.email = 'afiliado.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM documents d WHERE d.filename = 'seed_user_doc_aprovado_v1.pdf'
  );

INSERT INTO documents (user_id, property_id, type, filename, version, status, rejection_reason, reviewed_by, reviewed_at, created_at, updated_at)
SELECT
    u.id,
    NULL,
    'user_registration',
    'seed_user_doc_pendente_v1.pdf',
    'v1',
    'pendente',
    NULL,
    NULL,
    NULL,
    DATE_SUB(NOW(), INTERVAL 2 DAY),
    DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM users u
WHERE u.email = 'pendente.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM documents d WHERE d.filename = 'seed_user_doc_pendente_v1.pdf'
  );

INSERT INTO documents (user_id, property_id, type, filename, version, status, rejection_reason, reviewed_by, reviewed_at, created_at, updated_at)
SELECT
    NULL,
    p.id,
    'property_ownership',
    'seed_prop_doc_rejeitado_v1.pdf',
    'v1',
    'rejeitado',
    'Documento ilegivel no upload seed.',
    a.id,
    DATE_SUB(NOW(), INTERVAL 2 DAY),
    DATE_SUB(NOW(), INTERVAL 3 DAY),
    DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM properties p
JOIN users a ON a.email = 'moderador.seed@imobil.local'
WHERE p.title = 'Seed Terreno Viana' AND p.location = 'Viana'
  AND NOT EXISTS (
      SELECT 1 FROM documents d WHERE d.filename = 'seed_prop_doc_rejeitado_v1.pdf'
  );

-- Logs for audit views.
INSERT INTO logs (user_id, action, entity_type, entity_id, details, ip_address, created_at)
SELECT
    u.id,
    'seed_create_property',
    'property',
    p.id,
    'Seed de propriedade criado para testes.',
    '127.0.0.1',
    DATE_SUB(NOW(), INTERVAL 20 DAY)
FROM users u
JOIN properties p ON p.title = 'Seed Casa Talatona' AND p.location = 'Talatona'
WHERE u.email = 'proprietario.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM logs l WHERE l.action = 'seed_create_property' AND l.entity_id = p.id
  );

INSERT INTO logs (user_id, action, entity_type, entity_id, details, ip_address, created_at)
SELECT
    u.id,
    'seed_update_request_status',
    'request',
    r.id,
    'Status seed ajustado para fechado_ganho.',
    '127.0.0.1',
    DATE_SUB(NOW(), INTERVAL 9 DAY)
FROM users u
JOIN requests r ON r.message = 'SEED_REQ_FECHADO_GANHO'
WHERE u.email = 'moderador.seed@imobil.local'
  AND NOT EXISTS (
      SELECT 1 FROM logs l WHERE l.action = 'seed_update_request_status' AND l.entity_id = r.id
  );

COMMIT;

-- Quick summary for validation.
SELECT 'users_seed' AS metric, COUNT(*) AS total FROM users WHERE email LIKE '%.seed@imobil.local'
UNION ALL
SELECT 'properties_seed', COUNT(*) FROM properties WHERE title LIKE 'Seed %'
UNION ALL
SELECT 'requests_seed', COUNT(*) FROM requests WHERE message LIKE 'SEED_REQ_%'
UNION ALL
SELECT 'commissions_seed', COUNT(*) FROM commissions c JOIN requests r ON r.id = c.request_id WHERE r.message LIKE 'SEED_REQ_%'
UNION ALL
SELECT 'documents_seed', COUNT(*) FROM documents WHERE filename LIKE 'seed_%'
UNION ALL
SELECT 'notifications_seed', COUNT(*) FROM notifications WHERE title LIKE 'Status atualizado' OR title LIKE 'Comissao paga';
