<?php

namespace App\model;

class PropertyImpression extends ManipularBanco
{
    protected $table = 'property_impressions';

    public static function record(?int $userId, ?string $visitorKey, int $propertyId, string $surface): bool
    {
        $propertyId = (int) $propertyId;
        $surface = trim($surface);
        if ($propertyId <= 0 || $surface === '') {
            return false;
        }

        $userId = (int) ($userId ?? 0);
        $visitorKey = trim((string) ($visitorKey ?? ''));
        if ($userId <= 0 && $visitorKey === '') {
            return false;
        }

        $db = new self();
        $sql = "INSERT INTO {$db->table} (user_id, visitor_key, property_id, surface, shown_at)
                VALUES (?, ?, ?, ?, NOW())";
        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute([
            $userId > 0 ? $userId : null,
            $visitorKey !== '' ? $visitorKey : null,
            $propertyId,
            $surface,
            ]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param int[] $propertyIds
     */
    public static function recordMany(?int $userId, ?string $visitorKey, array $propertyIds, string $surface): void
    {
        foreach ($propertyIds as $propertyId) {
            self::record($userId, $visitorKey, (int) $propertyId, $surface);
        }
    }

    /**
     * Property IDs shown on a surface within the cooldown window.
     *
     * @return int[]
     */
    public static function getRecentPropertyIds(
        ?int $userId,
        ?string $visitorKey,
        string $surface,
        int $cooldownHours
    ): array {
        $surface = trim($surface);
        if ($surface === '') {
            return [];
        }

        $userId = (int) ($userId ?? 0);
        $visitorKey = trim((string) ($visitorKey ?? ''));
        if ($userId <= 0 && $visitorKey === '') {
            return [];
        }

        $cooldownHours = max(1, $cooldownHours);
        $db = new self();

        if ($userId > 0 && $visitorKey !== '') {
            $sql = "SELECT DISTINCT property_id FROM {$db->table}
                    WHERE surface = ?
                      AND shown_at >= DATE_SUB(NOW(), INTERVAL {$cooldownHours} HOUR)
                      AND (user_id = ? OR visitor_key = ?)";
            $params = [$surface, $userId, $visitorKey];
        } elseif ($userId > 0) {
            $sql = "SELECT DISTINCT property_id FROM {$db->table}
                    WHERE surface = ?
                      AND shown_at >= DATE_SUB(NOW(), INTERVAL {$cooldownHours} HOUR)
                      AND user_id = ?";
            $params = [$surface, $userId];
        } else {
            $sql = "SELECT DISTINCT property_id FROM {$db->table}
                    WHERE surface = ?
                      AND shown_at >= DATE_SUB(NOW(), INTERVAL {$cooldownHours} HOUR)
                      AND visitor_key = ?";
            $params = [$surface, $visitorKey];
        }

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }

        return array_map(static function (array $row): int {
            return (int) ($row['property_id'] ?? 0);
        }, $rows ?: []);
    }
}
