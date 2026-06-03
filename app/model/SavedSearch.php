<?php
namespace App\model;

class SavedSearch extends ManipularBanco {
    protected $table = 'saved_searches';

    private static function normalizeFilters(array $filters): array {
        return [
            'search_type' => $filters['type'] ?? null,
            'search_purpose' => $filters['purpose'] ?? null,
            'country_id' => isset($filters['country_id']) && $filters['country_id'] !== '' ? (int) $filters['country_id'] : null,
            'region_id' => isset($filters['region_id']) && $filters['region_id'] !== '' ? (int) $filters['region_id'] : null,
            'min_price' => isset($filters['min_price']) && $filters['min_price'] !== '' ? (float) $filters['min_price'] : null,
            'max_price' => isset($filters['max_price']) && $filters['max_price'] !== '' ? (float) $filters['max_price'] : null,
            'min_area' => isset($filters['min_area']) && $filters['min_area'] !== '' ? (float) $filters['min_area'] : null,
            'max_area' => isset($filters['max_area']) && $filters['max_area'] !== '' ? (float) $filters['max_area'] : null,
            'bedrooms' => isset($filters['bedrooms']) && $filters['bedrooms'] !== '' ? (int) $filters['bedrooms'] : null,
            'bathrooms' => isset($filters['bathrooms']) && $filters['bathrooms'] !== '' ? (int) $filters['bathrooms'] : null,
            'search_keyword' => !empty($filters['keyword']) ? trim((string) $filters['keyword']) : null,
            'trusted_only' => !empty($filters['trusted_only']) ? 1 : 0,
        ];
    }

    public static function createForUser(int $userId, string $name, array $filters): bool {
        $db = new self();
        $normalized = self::normalizeFilters($filters);
        $data = [
            'user_id' => $userId,
            'name' => $name,
            'filters' => json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'search_type' => $normalized['search_type'],
            'search_purpose' => $normalized['search_purpose'],
            'country_id' => $normalized['country_id'],
            'region_id' => $normalized['region_id'],
            'min_price' => $normalized['min_price'],
            'max_price' => $normalized['max_price'],
            'min_area' => $normalized['min_area'],
            'max_area' => $normalized['max_area'],
            'bedrooms' => $normalized['bedrooms'],
            'bathrooms' => $normalized['bathrooms'],
            'search_keyword' => $normalized['search_keyword'],
            'trusted_only' => $normalized['trusted_only'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        return (bool) $db->Salvar($data, $db->table);
    }

    public static function getByUser(int $userId): array {
        $db = new self();
        $sql = "SELECT * FROM {$db->table} WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['filters'] = json_decode((string) ($r['filters'] ?? '[]'), true) ?: [];
        }
        return $rows;
    }

    public static function deleteById(int $id, int $userId): bool {
        $db = new self();
        $sql = "DELETE FROM {$db->table} WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    public static function matchForProperty(array $property, array $owner = []): array {
        $db = new self();
        $propertyText = strtolower(trim((string) ($property['title'] ?? '')) . ' ' . trim((string) ($property['description'] ?? '')) . ' ' . trim((string) ($property['location'] ?? '')));
        $ownerTrusted = User::isTrustBadgeActive($owner) ? 1 : 0;

        $sql = "SELECT * FROM {$db->table}
                WHERE (search_type IS NULL OR search_type = ?)
                  AND (search_purpose IS NULL OR search_purpose = ?)
                  AND (country_id IS NULL OR country_id = ?)
                  AND (region_id IS NULL OR region_id = ?)
                  AND (bedrooms IS NULL OR bedrooms <= ?)
                  AND (bathrooms IS NULL OR bathrooms <= ?)
                  AND (min_price IS NULL OR min_price <= ?)
                  AND (max_price IS NULL OR max_price >= ?)
                  AND (min_area IS NULL OR min_area <= ?)
                  AND (max_area IS NULL OR max_area >= ?)
                  AND (trusted_only IS NULL OR trusted_only = 0 OR ? = 1)
                  AND (search_keyword IS NULL OR search_keyword = '' OR ? LIKE CONCAT('%', LOWER(search_keyword), '%'))";

        $params = [
            $property['type'] ?? null,
            $property['purpose'] ?? null,
            isset($property['country_id']) && $property['country_id'] !== '' ? (int) $property['country_id'] : null,
            isset($property['region_id']) && $property['region_id'] !== '' ? (int) $property['region_id'] : null,
            isset($property['bedrooms']) ? (int) $property['bedrooms'] : 0,
            isset($property['bathrooms']) ? (int) $property['bathrooms'] : 0,
            isset($property['price']) && $property['price'] !== '' ? (float) $property['price'] : 0.0,
            isset($property['price']) && $property['price'] !== '' ? (float) $property['price'] : 0.0,
            isset($property['area']) && $property['area'] !== '' ? (float) $property['area'] : 0.0,
            isset($property['area']) && $property['area'] !== '' ? (float) $property['area'] : 0.0,
            $ownerTrusted,
            $propertyText,
        ];

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['filters'] = json_decode((string) ($r['filters'] ?? '[]'), true) ?: [];
        }

        return $rows;
    }
}
