<?php

namespace App\model;

class PropertyAffiliate extends ManipularBanco
{
    protected $table = 'property_affiliates';

    public static function create($data)
    {
        $db = new self();
        $status = (string) ($data['status'] ?? 'pendente');
        if (!in_array($status, ['pendente', 'ativo', 'rejeitado'], true)) {
            $status = 'pendente';
        }

        return $db->Salvar([
            'user_id' => (int) ($data['user_id'] ?? 0),
            'property_id' => (int) ($data['property_id'] ?? 0),
            'status' => $status,
            'approved_at' => $status === 'ativo' ? date('Y-m-d H:i:s') : null,
            'rejected_at' => $status === 'rejeitado' ? date('Y-m-d H:i:s') : null,
        ], $db->table);
    }

    public static function getByProperty($propertyId, $status = null)
    {
        $db = new self();
        $sql = "SELECT pa.id, pa.user_id, pa.property_id, pa.status, pa.created_at,
                       pa.approved_at, pa.rejected_at, u.name, u.email, u.phone, u.is_admin
                FROM {$db->table} pa
                JOIN users u ON pa.user_id = u.id
                WHERE pa.property_id = ?";
        $params = [(int) $propertyId];

        if (!empty($status)) {
            $sql .= ' AND pa.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY pa.created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getByProperties(array $propertyIds, $status = null): array
    {
        $propertyIds = array_values(array_filter(array_map('intval', $propertyIds)));
        if (empty($propertyIds)) {
            return [];
        }

        $db = new self();
        $placeholders = implode(', ', array_fill(0, count($propertyIds), '?'));
        $sql = "SELECT pa.id, pa.user_id, pa.property_id, pa.status, pa.created_at,
                       pa.approved_at, pa.rejected_at, u.name, u.email, u.phone, u.is_admin
                FROM {$db->table} pa
                JOIN users u ON pa.user_id = u.id
                WHERE pa.property_id IN ({$placeholders})";
        $params = $propertyIds;

        if (!empty($status)) {
            $sql .= ' AND pa.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY pa.created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $propertyId = (int) ($row['property_id'] ?? 0);
            if (!isset($grouped[$propertyId])) {
                $grouped[$propertyId] = [];
            }
            $grouped[$propertyId][] = $row;
        }

        return $grouped;
    }

    public static function exists($userId, $propertyId)
    {
        $db = new self();
        $sql = "SELECT id FROM {$db->table}
                WHERE user_id = ? AND property_id = ?
                AND status IN ('ativo', 'pendente')
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([(int) $userId, (int) $propertyId]);
        return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public static function isActiveAffiliate($userId, $propertyId)
    {
        $db = new self();
        $sql = "SELECT id FROM {$db->table}
                WHERE user_id = ? AND property_id = ? AND status = 'ativo'
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([(int) $userId, (int) $propertyId]);
        return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public static function getStatusForUser(int $userId, int $propertyId): ?string
    {
        $db = new self();
        $sql = "SELECT status FROM {$db->table}
                WHERE user_id = ? AND property_id = ?
                ORDER BY created_at DESC
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $propertyId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (string) $row['status'] : null;
    }

    public static function approve($id, $userId = null)
    {
        $db = new self();

        if ($userId !== null) {
            $sql = "SELECT pa.id
                    FROM {$db->table} pa
                    JOIN properties p ON pa.property_id = p.id
                    WHERE pa.id = ? AND p.affiliate_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([(int) $id, (int) $userId]);
            if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                return false;
            }
        }

        $sql = "UPDATE {$db->table}
                SET status = 'ativo', approved_at = NOW()
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([(int) $id]);
    }

    public static function reject($id, $userId = null)
    {
        $db = new self();

        if ($userId !== null) {
            $sql = "SELECT pa.id
                    FROM {$db->table} pa
                    JOIN properties p ON pa.property_id = p.id
                    WHERE pa.id = ? AND p.affiliate_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([(int) $id, (int) $userId]);
            if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                return false;
            }
        }

        $sql = "UPDATE {$db->table}
                SET status = 'rejeitado', rejected_at = NOW()
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([(int) $id]);
    }

    public static function find($id)
    {
        $db = new self();
        $sql = "SELECT * FROM {$db->table} WHERE id = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get all affiliate requests for properties owned by $ownerId,
     * including affiliate user info and referral/commission stats.
     */
    public static function getByOwner(int $ownerId, int $limit = 0, int $offset = 0, ?string $status = null): array
    {
        $db  = new self();
        $allowedStatuses = ['pendente', 'ativo', 'rejeitado'];
        if ($status !== null && !in_array($status, $allowedStatuses, true)) {
            $status = null;
        }

        $sql = "SELECT
                    p.id            AS property_id,
                    p.title         AS property_title,
                    p.price         AS property_price,
                    p.location      AS property_location,
                    p.status        AS property_status,
                    pa.id           AS affiliate_request_id,
                    pa.status       AS affiliate_status,
                    pa.created_at   AS requested_at,
                    pa.approved_at,
                    pa.rejected_at,
                    u.id            AS affiliate_user_id,
                    u.username      AS affiliate_username,
                    u.name          AS affiliate_name,
                    u.email         AS affiliate_email,
                    u.phone         AS affiliate_phone,
                    u.affiliate_code,
                    COUNT(DISTINCT r.id)                AS referral_count,
                    COALESCE(SUM(c.affiliate_amount), 0) AS commission_total
                FROM {$db->table} pa
                JOIN properties p  ON pa.property_id = p.id
                JOIN users u       ON pa.user_id      = u.id
                LEFT JOIN requests r
                       ON r.affiliate_id = u.id AND r.property_id = p.id
                LEFT JOIN commissions c
                       ON c.affiliate_id = u.id AND c.request_id  = r.id
                WHERE p.affiliate_id = ?";
        $params = [$ownerId];

        if ($status !== null) {
            $sql .= ' AND pa.status = ?';
            $params[] = $status;
        }

        $sql .= ' GROUP BY pa.id, p.id, u.id
                ORDER BY p.title ASC, pa.status DESC, pa.created_at DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function countByOwner(int $ownerId, ?string $status = null): int
    {
        $db = new self();
        $allowedStatuses = ['pendente', 'ativo', 'rejeitado'];
        if ($status !== null && !in_array($status, $allowedStatuses, true)) {
            $status = null;
        }

        $sql = "SELECT COUNT(*) FROM {$db->table} pa
                JOIN properties p ON pa.property_id = p.id
                WHERE p.affiliate_id = ?";
        $params = [$ownerId];

        if ($status !== null) {
            $sql .= ' AND pa.status = ?';
            $params[] = $status;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function getAffiliationTerms()
    {
        return [
            'title' => 'Programa de Afiliação - Termos e Condições',
            'sections' => [
                [
                    'heading' => '1. Sobre o Programa',
                    'content' => 'O Programa de Afiliação permite que você divulgue imóveis e receba comissão quando houver conclusão de negócio (venda ou locação).',
                ],
                [
                    'heading' => '2. Elegibilidade',
                    'content' => 'Para participar, você deve ser um utilizador registado e ativo na plataforma. Proprietários não podem se afiliar aos seus próprios imóveis.',
                ],
                [
                    'heading' => '3. Comissões',
                    'content' => 'As comissões são calculadas de acordo com as regras operacionais da plataforma e são pagas após a conclusão do negócio e confirmação de todos os termos.',
                ],
                [
                    'heading' => '4. Responsabilidades do Afiliado',
                    'content' => 'Como afiliado, você concorda em: (a) Divulgar o imóvel de forma honesta e precisa; (b) Não fazer afirmações falsas ou enganosas; (c) Cumprir todas as leis aplicáveis.',
                ],
                [
                    'heading' => '5. Aprovação e Moderação',
                    'content' => 'Cada imóvel define a sua política de afiliação: aprovação automática pelo sistema, validação manual pelo proprietário ou afiliação desativada. Quando manual, a aprovação não é garantida.',
                ],
                [
                    'heading' => '6. Suspensão e Rescisão',
                    'content' => 'A plataforma reserva o direito de suspender ou rescindir sua afiliação se houver violação destes termos ou da política de uso da plataforma.',
                ],
                [
                    'heading' => '7. Aceitação',
                    'content' => 'Ao clicar em "Aceito os termos", você concorda com todos os termos e condições acima. A entrada poderá ser imediata (modo automático), aguardar decisão do proprietário (modo manual) ou não estar disponível para o imóvel.',
                ],
            ],
            'last_updated' => date('Y-m-d'),
        ];
    }
}
