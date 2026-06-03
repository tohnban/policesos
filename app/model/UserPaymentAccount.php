<?php
namespace App\model;

class UserPaymentAccount extends ManipularBanco {
    protected $table = 'user_payment_accounts';

    public static function getDefaultActiveForUser(int $userId): ?array {
        $accounts = self::getShareableActiveByUser($userId);

        return $accounts[0] ?? null;
    }

    public static function getShareableActiveByUser(int $userId): array {
        $db = new self();
        $sql = "SELECT upa.*, pm.code AS method_code, pm.name AS method_name, pm.fields_config
                FROM {$db->table} upa
                JOIN payment_methods pm ON pm.id = upa.method_id
                WHERE upa.user_id = ? AND upa.is_active = 1
                ORDER BY upa.is_default DESC, upa.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getByUser(int $userId): array {
        $db = new self();
        $sql = "SELECT upa.*, pm.code AS method_code, pm.name AS method_name
                FROM {$db->table} upa
                JOIN payment_methods pm ON pm.id = upa.method_id
                WHERE upa.user_id = ?
                ORDER BY upa.is_default DESC, upa.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function findByIdForUser(int $id, int $userId): ?array {
        $db = new self();
        $sql = "SELECT upa.*, pm.code AS method_code, pm.name AS method_name
                FROM {$db->table} upa
                JOIN payment_methods pm ON pm.id = upa.method_id
                WHERE upa.id = ? AND upa.user_id = ?
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function createForUser(int $userId, array $data): int|bool {
        $db = new self();
        $conn = $db->ConexaoDB();

        $payload = [
            'user_id' => $userId,
            'method_id' => (int) ($data['method_id'] ?? 0),
            'account_label' => trim((string) ($data['account_label'] ?? '')) ?: null,
            'account_name' => trim((string) ($data['account_name'] ?? '')) ?: null,
            'account_number' => trim((string) ($data['account_number'] ?? '')) ?: null,
            'iban' => trim((string) ($data['iban'] ?? '')) ?: null,
            'bank_name' => trim((string) ($data['bank_name'] ?? '')) ?: null,
            'wallet_provider' => trim((string) ($data['wallet_provider'] ?? '')) ?: null,
            'phone_number' => trim((string) ($data['phone_number'] ?? '')) ?: null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE) : null,
            'is_default' => !empty($data['is_default']) ? 1 : 0,
            'is_active' => 1,
        ];

        if ($payload['method_id'] <= 0) {
            return false;
        }

        try {
            $conn->beginTransaction();

            if ((int) $payload['is_default'] === 1) {
                $clearSql = "UPDATE {$db->table} SET is_default = 0 WHERE user_id = ?";
                $clearStmt = $conn->prepare($clearSql);
                $clearStmt->execute([$userId]);
            }

            $columns = array_keys($payload);
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $sql = "INSERT INTO {$db->table} (" . implode(',', $columns) . ") VALUES ({$placeholders})";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array_values($payload));

            $id = (int) $conn->lastInsertId();
            $conn->commit();
            return $id > 0 ? $id : true;
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return false;
        }
    }

    public static function setDefault(int $id, int $userId): bool {
        $db = new self();
        $conn = $db->ConexaoDB();

        try {
            $conn->beginTransaction();

            $clearSql = "UPDATE {$db->table} SET is_default = 0 WHERE user_id = ?";
            $clearStmt = $conn->prepare($clearSql);
            $clearStmt->execute([$userId]);

            $setSql = "UPDATE {$db->table}
                       SET is_default = 1
                       WHERE id = ? AND user_id = ? AND is_active = 1";
            $setStmt = $conn->prepare($setSql);
            $setStmt->execute([$id, $userId]);

            $conn->commit();
            return true;
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return false;
        }
    }

    public static function deactivate(int $id, int $userId): bool {
        $db = new self();
        $sql = "UPDATE {$db->table}
                SET is_active = 0,
                    is_default = 0
                WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }
}
