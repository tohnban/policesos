<?php

namespace App\model;

class PaymentTransaction extends ManipularBanco
{
    protected $table = 'payment_transactions';

    private const ALLOWED_STATUSES = ['pendente', 'processando', 'confirmado', 'cancelado', 'falhado', 'rejeitado'];
    private const ALLOWED_TYPES = [
        'commission_owner_payment',
        'commission_payout',
        'system_commission',
        'boost_fee',
        'trust_badge_fee',
        'manual_adjustment',
        'subscription_fee',
    ];

    public static function create(array $data): int|bool
    {
        $db = new self();

        $payload = [
            'transaction_type' => (string) ($data['transaction_type'] ?? 'manual_adjustment'),
            'direction' => (string) ($data['direction'] ?? 'incoming'),
            'status' => (string) ($data['status'] ?? 'pendente'),
            'amount' => (float) ($data['amount'] ?? 0),
            'currency' => strtoupper((string) ($data['currency'] ?? 'AOA')),
            'method_id' => isset($data['method_id']) ? (int) $data['method_id'] : null,
            'system_channel_id' => isset($data['system_channel_id']) ? (int) $data['system_channel_id'] : null,
            'user_account_id' => isset($data['user_account_id']) ? (int) $data['user_account_id'] : null,
            'counterparty_user_id' => isset($data['counterparty_user_id']) ? (int) $data['counterparty_user_id'] : null,
            'related_entity_type' => isset($data['related_entity_type']) ? (string) $data['related_entity_type'] : null,
            'related_entity_id' => isset($data['related_entity_id']) ? (int) $data['related_entity_id'] : null,
            'reference_code' => isset($data['reference_code']) ? trim((string) $data['reference_code']) : null,
            'proof_file' => isset($data['proof_file']) ? (string) $data['proof_file'] : null,
            'notes' => isset($data['notes']) ? (string) $data['notes'] : null,
            'created_by' => isset($data['created_by']) ? (int) $data['created_by'] : null,
            'confirmed_by' => isset($data['confirmed_by']) ? (int) $data['confirmed_by'] : null,
            'confirmed_at' => isset($data['confirmed_at']) ? (string) $data['confirmed_at'] : null,
        ];

        return $db->Salvar($payload, $db->table);
    }

    public static function hasOpenSubscriptionInvoice(int $subscriptionId): bool
    {
        if ($subscriptionId <= 0) {
            return false;
        }
        $db = new self();
        $sql = "SELECT COUNT(*) FROM {$db->table}
                WHERE related_entity_type = 'user_subscription'
                  AND related_entity_id = ?
                  AND transaction_type = 'subscription_fee'
                  AND status IN ('pendente', 'processando')";
        $stmt = $db->prepare($sql);
        $stmt->execute([$subscriptionId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function findById(int $id): ?array
    {
        $db = new self();
        $sql = "SELECT pt.*, pm.name AS method_name, pm.code AS method_code,
                   u.name AS counterparty_name, u.email AS counterparty_email
                FROM {$db->table} pt
                LEFT JOIN payment_methods pm ON pm.id = pt.method_id
            LEFT JOIN users u ON u.id = pt.counterparty_user_id
                WHERE pt.id = ?
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getPending(int $limit = 100): array
    {
        $db = new self();
        $limit = max(1, min(500, $limit));
        $sql = "SELECT pt.*, pm.name AS method_name
                FROM {$db->table} pt
                LEFT JOIN payment_methods pm ON pm.id = pt.method_id
                WHERE pt.status IN ('pendente', 'processando')
                ORDER BY pt.created_at ASC
                LIMIT {$limit}";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getList(?string $status = null, ?string $type = null, int $limit = 50, int $offset = 0, array $excludeStatuses = []): array
    {
        $db = new self();
        $params = [];
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        $sql = "SELECT pt.*, pm.name AS method_name,
                   u.name AS counterparty_name, u.email AS counterparty_email
                FROM {$db->table} pt
                LEFT JOIN payment_methods pm ON pm.id = pt.method_id
            LEFT JOIN users u ON u.id = pt.counterparty_user_id
                WHERE 1=1";

        if ($status !== null && in_array($status, self::ALLOWED_STATUSES, true)) {
            $sql .= ' AND pt.status = ?';
            $params[] = $status;
        }

        if ($type !== null && in_array($type, self::ALLOWED_TYPES, true)) {
            $sql .= ' AND pt.transaction_type = ?';
            $params[] = $type;
        }

        $validExclude = array_values(array_filter($excludeStatuses, fn ($s) => in_array($s, self::ALLOWED_STATUSES, true)));
        if (!empty($validExclude)) {
            $placeholders = implode(',', array_fill(0, count($validExclude), '?'));
            $sql .= " AND pt.status NOT IN ({$placeholders})";
            $params = array_merge($params, $validExclude);
        }

        $sql .= " ORDER BY pt.created_at DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getListForExport(?string $status = null, ?string $type = null, int $limit = 5000): array
    {
        $db = new self();
        $params = [];
        $limit = max(1, min(20000, $limit));

        $sql = "SELECT pt.*, pm.name AS method_name,
                       u.name AS counterparty_name, u.email AS counterparty_email
                FROM {$db->table} pt
                LEFT JOIN payment_methods pm ON pm.id = pt.method_id
                LEFT JOIN users u ON u.id = pt.counterparty_user_id
                WHERE 1=1";

        if ($status !== null && in_array($status, self::ALLOWED_STATUSES, true)) {
            $sql .= ' AND pt.status = ?';
            $params[] = $status;
        }

        if ($type !== null && in_array($type, self::ALLOWED_TYPES, true)) {
            $sql .= ' AND pt.transaction_type = ?';
            $params[] = $type;
        }

        $sql .= " ORDER BY pt.created_at DESC LIMIT {$limit}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function countList(?string $status = null, ?string $type = null, array $excludeStatuses = []): int
    {
        $db = new self();
        $params = [];
        $sql = "SELECT COUNT(*)
                FROM {$db->table} pt
                WHERE 1=1";

        if ($status !== null && in_array($status, self::ALLOWED_STATUSES, true)) {
            $sql .= ' AND pt.status = ?';
            $params[] = $status;
        }

        if ($type !== null && in_array($type, self::ALLOWED_TYPES, true)) {
            $sql .= ' AND pt.transaction_type = ?';
            $params[] = $type;
        }

        $validExclude = array_values(array_filter($excludeStatuses, fn ($s) => in_array($s, self::ALLOWED_STATUSES, true)));
        if (!empty($validExclude)) {
            $placeholders = implode(',', array_fill(0, count($validExclude), '?'));
            $sql .= " AND pt.status NOT IN ({$placeholders})";
            $params = array_merge($params, $validExclude);
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function getAdminSummary(): array
    {
        $db = new self();
        $sql = "SELECT
                    COUNT(*) AS total_count,
                    SUM(CASE WHEN status IN ('pendente', 'processando') THEN 1 ELSE 0 END) AS pending_count,
                    COALESCE(SUM(CASE WHEN status IN ('pendente', 'processando') THEN amount ELSE 0 END), 0) AS pending_amount,
                    SUM(CASE WHEN status = 'confirmado' THEN 1 ELSE 0 END) AS confirmed_count,
                    COALESCE(SUM(CASE WHEN status = 'confirmado' AND confirmed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN amount ELSE 0 END), 0) AS confirmed_amount_30d
                FROM {$db->table}";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

        return [
            'total_count' => (int) ($row['total_count'] ?? 0),
            'pending_count' => (int) ($row['pending_count'] ?? 0),
            'pending_amount' => (float) ($row['pending_amount'] ?? 0),
            'confirmed_count' => (int) ($row['confirmed_count'] ?? 0),
            'confirmed_amount_30d' => (float) ($row['confirmed_amount_30d'] ?? 0),
        ];
    }

    public static function getByCounterpartyUser(int $userId, int $limit = 200): array
    {
        $db = new self();
        $limit = max(1, min(500, $limit));
        $sql = "SELECT pt.*, pm.name AS method_name,
                   u.name AS counterparty_name, u.email AS counterparty_email
                FROM {$db->table} pt
                LEFT JOIN payment_methods pm ON pm.id = pt.method_id
            LEFT JOIN users u ON u.id = pt.counterparty_user_id
                WHERE pt.counterparty_user_id = ?
                ORDER BY pt.created_at DESC
                LIMIT {$limit}";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getByCounterpartyUserFiltered(int $userId, ?string $status = null, ?string $type = null, int $limit = 200): array
    {
        $db = new self();
        $params = [(int) $userId];
        $limit = max(1, min(5000, $limit));

        $sql = "SELECT pt.*, pm.name AS method_name,
                   u.name AS counterparty_name, u.email AS counterparty_email
                FROM {$db->table} pt
                LEFT JOIN payment_methods pm ON pm.id = pt.method_id
                LEFT JOIN users u ON u.id = pt.counterparty_user_id
                WHERE pt.counterparty_user_id = ?";

        if ($status !== null && in_array($status, self::ALLOWED_STATUSES, true)) {
            $sql .= ' AND pt.status = ?';
            $params[] = $status;
        }

        if ($type !== null && in_array($type, self::ALLOWED_TYPES, true)) {
            $sql .= ' AND pt.transaction_type = ?';
            $params[] = $type;
        }

        $sql .= " ORDER BY pt.created_at DESC LIMIT {$limit}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getByEntity(string $entityType, int $entityId): array
    {
        $db = new self();
        $sql = "SELECT * FROM {$db->table}
                WHERE related_entity_type = ? AND related_entity_id = ?
                ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$entityType, $entityId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function findAffiliatePayoutForCommission(int $commissionId, ?string $status = null): ?array
    {
        if ($commissionId <= 0) {
            return null;
        }

        foreach (self::getByEntity('commission', $commissionId) as $row) {
            if (($row['transaction_type'] ?? '') !== 'commission_payout') {
                continue;
            }
            if (($row['direction'] ?? '') !== 'outgoing') {
                continue;
            }
            if ($status !== null && (string) ($row['status'] ?? '') !== $status) {
                continue;
            }

            return $row;
        }

        return null;
    }

    public static function findPendingAffiliatePayout(int $commissionId): ?array
    {
        if ($commissionId <= 0) {
            return null;
        }

        foreach (self::getByEntity('commission', $commissionId) as $row) {
            if (($row['transaction_type'] ?? '') !== 'commission_payout') {
                continue;
            }
            if (($row['direction'] ?? '') !== 'outgoing') {
                continue;
            }
            if (in_array((string) ($row['status'] ?? ''), ['pendente', 'processando'], true)) {
                return $row;
            }
        }

        return null;
    }

    public static function hasConfirmedAffiliatePayout(int $commissionId): bool
    {
        return self::countAffiliatePayouts($commissionId, 'confirmado') > 0;
    }

    public static function hasAffiliatePayoutForCommission(int $commissionId): bool
    {
        return self::countAffiliatePayouts($commissionId) > 0;
    }

    public static function countAffiliatePayouts(int $commissionId, ?string $status = null): int
    {
        if ($commissionId <= 0) {
            return 0;
        }

        $count = 0;
        foreach (self::getByEntity('commission', $commissionId) as $row) {
            if (($row['transaction_type'] ?? '') !== 'commission_payout') {
                continue;
            }
            if (($row['direction'] ?? '') !== 'outgoing') {
                continue;
            }
            if ($status !== null && (string) ($row['status'] ?? '') !== $status) {
                continue;
            }
            $count++;
        }

        return $count;
    }

    public static function confirmWithProof(
        int $id,
        int $confirmedBy,
        string $proofFile,
        string $referenceCode = ''
    ): bool {
        $db = new self();
        $proofFile = trim($proofFile);
        $referenceCode = trim($referenceCode);

        $sql = "UPDATE {$db->table}
                SET status = 'confirmado',
                    confirmed_by = ?,
                    confirmed_at = NOW(),
                    proof_file = ?,
                    reference_code = CASE WHEN ? <> '' THEN ? ELSE reference_code END
                WHERE id = ? AND status IN ('pendente', 'processando')";
        $stmt = $db->prepare($sql);
        $ok = $stmt->execute([
            $confirmedBy > 0 ? $confirmedBy : null,
            $proofFile !== '' ? $proofFile : null,
            $referenceCode,
            $referenceCode,
            $id,
        ]);

        return $ok && $stmt->rowCount() > 0;
    }

    public static function markAsConfirmed(int $id, int $confirmedBy, string $referenceCode = '', string $notes = ''): bool
    {
        $db = new self();
        $sql = "UPDATE {$db->table}
                SET status = 'confirmado',
                    confirmed_by = ?,
                    confirmed_at = NOW(),
                    reference_code = CASE WHEN ? <> '' THEN ? ELSE reference_code END,
                    notes = CASE WHEN ? <> '' THEN ? ELSE notes END
                WHERE id = ? AND status IN ('pendente', 'processando')";
        $stmt = $db->prepare($sql);
        $ok = $stmt->execute([
            $confirmedBy,
            $referenceCode,
            $referenceCode,
            $notes,
            $notes,
            $id,
        ]);
        return $ok && $stmt->rowCount() > 0;
    }

    public static function markAsCancelled(int $id, string $notes = ''): bool
    {
        $db = new self();
        $sql = "UPDATE {$db->table}
                SET status = 'cancelado',
                    notes = CASE WHEN ? <> '' THEN ? ELSE notes END
                WHERE id = ? AND status IN ('pendente', 'processando')";
        $stmt = $db->prepare($sql);
        $ok = $stmt->execute([$notes, $notes, $id]);
        return $ok && $stmt->rowCount() > 0;
    }

    public static function markAsRejected(int $id, string $notes = ''): bool
    {
        $db = new self();
        $sql = "UPDATE {$db->table}
                SET status = 'rejeitado',
                    notes = CASE WHEN ? <> '' THEN ? ELSE notes END
                WHERE id = ? AND status IN ('pendente', 'processando')";
        $stmt = $db->prepare($sql);
        $ok = $stmt->execute([$notes, $notes, $id]);
        return $ok && $stmt->rowCount() > 0;
    }
}
