<?php
namespace App\model;

class Region extends ManipularBanco {
    protected $table = 'regions';

    public static function getActive(?int $countryId = null): array {
        try {
            $db = new self();
            $sql = "SELECT id, code, name, country_id FROM {$db->table} WHERE is_active = 1";
            $params = [];
            if ($countryId !== null && $countryId > 0) {
                $sql .= " AND country_id = ?";
                $params[] = $countryId;
            }
            $sql .= " ORDER BY sort_order ASC, name ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function exists(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        try {
            $db = new self();
            $sql = "SELECT 1 FROM {$db->table} WHERE id = ? AND is_active = 1 LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function findById(int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        try {
            $db = new self();
            $sql = "SELECT id, code, name, is_active, sort_order FROM {$db->table} WHERE id = ? LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function belongsToCountry(int $regionId, int $countryId): bool {
        if ($regionId <= 0 || $countryId <= 0) {
            return false;
        }
        try {
            $db = new self();
            $sql = "SELECT 1 FROM {$db->table} WHERE id = ? AND country_id = ? AND is_active = 1 LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$regionId, $countryId]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
