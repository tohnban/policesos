<?php

namespace App\model;

class PaymentMethod extends ManipularBanco
{
    protected $table = 'payment_methods';

    public static function getAll(): array
    {
        $db = new self();
        $sql = "SELECT * FROM {$db->table} WHERE deleted_at IS NULL ORDER BY name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getActive(?string $audience = null): array
    {
        $db = new self();
        $params = [];
        $sql = "SELECT * FROM {$db->table} WHERE is_active = 1 AND deleted_at IS NULL";

        if ($audience !== null && in_array($audience, ['system', 'user'], true)) {
            $sql .= " AND audience IN (?, 'both')";
            $params[] = $audience;
        }

        $sql .= ' ORDER BY name ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        $db = new self();
        $sql = "SELECT * FROM {$db->table} WHERE id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $db = new self();
        $sql = "SELECT * FROM {$db->table} WHERE code = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([trim($code)]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function setActiveStatus(int $id, bool $isActive): bool
    {
        $db = new self();
        $sql = "UPDATE {$db->table} SET is_active = ? WHERE id = ? AND deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$isActive ? 1 : 0, $id]);
    }

    /**
     * Parse fields_config JSON from a method row into a normalized array.
     * Returns ['account_name'=>bool, 'account_number'=>bool, 'iban'=>bool,
     *          'bank_name'=>bool, 'wallet_provider'=>bool, 'phone_number'=>bool]
     */
    public static function parseFieldsConfig(?string $json): array
    {
        $defaults = [
            'account_name'   => false,
            'account_number' => false,
            'iban'           => false,
            'bank_name'      => false,
            'wallet_provider' => false,
            'phone_number'   => false,
        ];
        if (empty($json)) {
            return $defaults;
        }
        $parsed = json_decode($json, true);
        if (!is_array($parsed)) {
            return $defaults;
        }
        foreach ($defaults as $key => $_) {
            if (isset($parsed[$key])) {
                $defaults[$key] = (bool) $parsed[$key];
            }
        }
        return $defaults;
    }

    public static function create(array $data): int|false
    {
        $db = new self();

        $fieldsConfig = self::normalizeFieldsConfig($data['fields_config'] ?? []);

        $sql = "INSERT INTO {$db->table}
                    (code, name, direction, audience, requires_reference, fields_config, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $ok = $stmt->execute([
            trim($data['code']),
            trim($data['name']),
            $data['direction'] ?? 'both',
            $data['audience']  ?? 'both',
            !empty($data['requires_reference']) ? 1 : 0,
            json_encode($fieldsConfig),
            !empty($data['is_active']) ? 1 : 0,
        ]);
        if (!$ok) {
            return false;
        }
        $id = $db->ConexaoDB()->lastInsertId();
        return $id ? (int) $id : false;
    }

    public static function update(int $id, array $data): bool
    {
        $db = new self();

        $fieldsConfig = self::normalizeFieldsConfig($data['fields_config'] ?? []);

        $sql = "UPDATE {$db->table}
                SET code = ?, name = ?, direction = ?, audience = ?,
                    requires_reference = ?, fields_config = ?, is_active = ?
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            trim($data['code']),
            trim($data['name']),
            $data['direction'] ?? 'both',
            $data['audience']  ?? 'both',
            !empty($data['requires_reference']) ? 1 : 0,
            json_encode($fieldsConfig),
            !empty($data['is_active']) ? 1 : 0,
            $id,
        ]);
    }

    /**
     * Deletes a method only if no user accounts reference it.
     */
    public static function delete(int $id): bool|string
    {
        $db = new self();

        // Guard: check for linked user accounts
        $check = $db->prepare('SELECT COUNT(*) FROM user_payment_accounts WHERE method_id = ? AND is_active = 1');
        $check->execute([$id]);
        if ((int) $check->fetchColumn() > 0) {
            return 'Existem contas de utilizadores activas que usam este método. Desactive-as primeiro.';
        }

        $channelsCheck = $db->prepare('SELECT COUNT(*) FROM system_payment_channels WHERE method_id = ? AND is_active = 1');
        $channelsCheck->execute([$id]);
        if ((int) $channelsCheck->fetchColumn() > 0) {
            return 'Existem canais activos do sistema associados a este método. Desactive-os primeiro.';
        }

        $txCheck = $db->prepare('SELECT COUNT(*) FROM payment_transactions WHERE method_id = ?');
        $txCheck->execute([$id]);
        if ((int) $txCheck->fetchColumn() > 0) {
            return 'Existem transações associadas a este método. Não é possível remover por integridade histórica.';
        }

        $stmt = $db->prepare("UPDATE {$db->table} SET deleted_at = NOW(), is_active = 0 WHERE id = ? AND deleted_at IS NULL");
        return $stmt->execute([$id]);
    }

    private static function normalizeFieldsConfig($raw): array
    {
        $allowed = ['account_name', 'account_number', 'iban', 'bank_name', 'wallet_provider', 'phone_number'];
        $result  = [];
        foreach ($allowed as $field) {
            $result[$field] = !empty($raw[$field]);
        }
        return $result;
    }
}
