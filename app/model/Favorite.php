<?php
namespace App\model;

class Favorite extends ManipularBanco {
    protected $table = 'favorites';

    public static function countByUser($userId): int {
        $db = new self();
        $sql = "SELECT COUNT(*) AS total FROM {$db->table} WHERE user_id = ? AND deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        $stmt->execute([(int) $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    }

    public static function getPropertyIdsByUser($userId) {
        $db = new self();
        $sql = "SELECT property_id FROM {$db->table} WHERE user_id = ? AND deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(static function ($row) {
            return (int) $row['property_id'];
        }, $rows);
    }

    public static function exists($userId, $propertyId) {
        $db = new self();
        $sql = "SELECT id FROM {$db->table} WHERE user_id = ? AND property_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $propertyId]);
        return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public static function add($userId, $propertyId) {
        $db = new self();
        if (self::exists($userId, $propertyId)) {
            return true;
        }

        // Restore soft-deleted favorite if it already exists historically.
        $restoreSql = "UPDATE {$db->table} SET deleted_at = NULL WHERE user_id = ? AND property_id = ?";
        $restoreStmt = $db->prepare($restoreSql);
        $restoreStmt->execute([(int) $userId, (int) $propertyId]);
        if ($restoreStmt->rowCount() > 0) {
            return true;
        }

        return $db->Salvar([
            'user_id' => (int) $userId,
            'property_id' => (int) $propertyId,
        ], $db->table);
    }

    public static function remove($userId, $propertyId) {
        $db = new self();
        $sql = "UPDATE {$db->table} SET deleted_at = NOW() WHERE user_id = ? AND property_id = ? AND deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        return $stmt->execute([(int) $userId, (int) $propertyId]);
    }
}
