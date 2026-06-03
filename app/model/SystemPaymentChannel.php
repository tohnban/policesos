<?php
namespace App\model;

class SystemPaymentChannel extends ManipularBanco {
    protected $table = 'system_payment_channels';

    public static function getByMethodId(int $methodId): array {
        $db = new self();
        $sql = "SELECT * FROM {$db->table}
                WHERE method_id = ?
                ORDER BY is_active DESC, is_default DESC, channel_name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$methodId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getActiveByMethodId(int $methodId): array {
        $db = new self();
        $sql = "SELECT * FROM {$db->table}
                WHERE method_id = ? AND is_active = 1
                ORDER BY is_default DESC, channel_name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$methodId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getActiveByMethodCode(string $methodCode): array {
        $method = PaymentMethod::findByCode($methodCode);
        if (!$method) {
            return [];
        }
        return self::getActiveByMethodId((int) $method['id']);
    }

    public static function findById(int $id): ?array {
        $db = new self();
        $sql = "SELECT spc.*, pm.code AS method_code, pm.name AS method_name
                FROM {$db->table} spc
                JOIN payment_methods pm ON pm.id = spc.method_id
                WHERE spc.id = ?
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function setDefault(int $id, int $methodId): bool {
        $db = new self();
        $conn = $db->ConexaoDB();

        try {
            $conn->beginTransaction();

            $clearSql = "UPDATE {$db->table} SET is_default = 0 WHERE method_id = ?";
            $clearStmt = $conn->prepare($clearSql);
            $clearStmt->execute([$methodId]);

            $setSql = "UPDATE {$db->table} SET is_default = 1 WHERE id = ? AND method_id = ? AND is_active = 1";
            $setStmt = $conn->prepare($setSql);
            $setStmt->execute([$id, $methodId]);

            if ($setStmt->rowCount() === 0) {
                throw new \RuntimeException('Canal inválido para padrão');
            }

            $conn->commit();
            return true;
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return false;
        }
    }

    public static function create(array $data): int|bool {
        $db = new self();

        $payload = [
            'method_id' => (int) ($data['method_id'] ?? 0),
            'channel_name' => trim((string) ($data['channel_name'] ?? '')),
            'account_name' => trim((string) ($data['account_name'] ?? '')) ?: null,
            'account_number' => trim((string) ($data['account_number'] ?? '')) ?: null,
            'iban' => trim((string) ($data['iban'] ?? '')) ?: null,
            'bank_name' => trim((string) ($data['bank_name'] ?? '')) ?: null,
            'wallet_provider' => trim((string) ($data['wallet_provider'] ?? '')) ?: null,
            'instructions' => trim((string) ($data['instructions'] ?? '')) ?: null,
            'is_default' => !empty($data['is_default']) ? 1 : 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];

        if ($payload['method_id'] <= 0 || $payload['channel_name'] === '') {
            return false;
        }

        if ((int) $payload['is_default'] === 1) {
            $payload['is_active'] = 1;
        }

        $conn = $db->ConexaoDB();
        try {
            $conn->beginTransaction();

            if ((int) $payload['is_default'] === 1) {
                $clearSql = "UPDATE {$db->table} SET is_default = 0 WHERE method_id = ?";
                $clearStmt = $conn->prepare($clearSql);
                $clearStmt->execute([$payload['method_id']]);
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

    public static function update(int $id, array $data): bool {
        $db = new self();
        $channel = self::findById($id);
        if (!$channel) {
            return false;
        }

        $payload = [
            'channel_name' => trim((string) ($data['channel_name'] ?? '')),
            'account_name' => trim((string) ($data['account_name'] ?? '')) ?: null,
            'account_number' => trim((string) ($data['account_number'] ?? '')) ?: null,
            'iban' => trim((string) ($data['iban'] ?? '')) ?: null,
            'bank_name' => trim((string) ($data['bank_name'] ?? '')) ?: null,
            'wallet_provider' => trim((string) ($data['wallet_provider'] ?? '')) ?: null,
            'instructions' => trim((string) ($data['instructions'] ?? '')) ?: null,
            'is_default' => !empty($data['is_default']) ? 1 : 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];

        if ($payload['channel_name'] === '') {
            return false;
        }

        if ((int) $payload['is_default'] === 1) {
            $payload['is_active'] = 1;
        }

        $conn = $db->ConexaoDB();
        try {
            $conn->beginTransaction();

            if ((int) $payload['is_default'] === 1) {
                $clearSql = "UPDATE {$db->table} SET is_default = 0 WHERE method_id = ?";
                $clearStmt = $conn->prepare($clearSql);
                $clearStmt->execute([(int) $channel['method_id']]);
            }

            $sql = "UPDATE {$db->table}
                    SET channel_name = ?,
                        account_name = ?,
                        account_number = ?,
                        iban = ?,
                        bank_name = ?,
                        wallet_provider = ?,
                        instructions = ?,
                        is_default = ?,
                        is_active = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $payload['channel_name'],
                $payload['account_name'],
                $payload['account_number'],
                $payload['iban'],
                $payload['bank_name'],
                $payload['wallet_provider'],
                $payload['instructions'],
                $payload['is_default'],
                $payload['is_active'],
                $id,
            ]);

            $conn->commit();
            return true;
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return false;
        }
    }

    public static function deactivate(int $id): bool {
        $db = new self();
        $sql = "UPDATE {$db->table}
                SET is_active = 0,
                    is_default = 0
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$id]);
    }
}
