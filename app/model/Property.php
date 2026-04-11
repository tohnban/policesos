<?php
namespace App\model;

class Property extends ManipularBanco {
    protected $table = 'properties';

    private static function buildFilteredQuery(array $filters = [], bool $countOnly = false): array {
        $db = new self();
        $select = $countOnly
            ? 'COUNT(*) AS total'
            : "p.*, u.name AS owner_name,
                CASE WHEN u.status = 'ativo' THEN 1 ELSE 0 END AS owner_verified,
                CASE WHEN u.status = 'ativo'
                    AND u.trust_badge_status IN ('aprovado', 'approved')
                    AND u.trust_badge_fee_paid = 1
                THEN 1 ELSE 0 END AS owner_trusted";

        $sql = "SELECT {$select}
                FROM {$db->table} p
                LEFT JOIN users u ON p.affiliate_id = u.id
                WHERE p.status = 'disponivel'";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND p.type = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['purpose'])) {
            $sql .= " AND p.purpose = ?";
            $params[] = $filters['purpose'];
        }
        if (!empty($filters['min_price'])) {
            $sql .= " AND p.price >= ?";
            $params[] = $filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $sql .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
        }
        if (!empty($filters['location'])) {
            $sql .= " AND p.location LIKE ?";
            $params[] = '%' . $filters['location'] . '%';
        }
        if (!empty($filters['bedrooms'])) {
            $sql .= " AND p.bedrooms >= ?";
            $params[] = (int) $filters['bedrooms'];
        }
        if (!empty($filters['bathrooms'])) {
            $sql .= " AND p.bathrooms >= ?";
            $params[] = (int) $filters['bathrooms'];
        }
        if (!empty($filters['min_area'])) {
            $sql .= " AND p.area >= ?";
            $params[] = (float) $filters['min_area'];
        }
        if (!empty($filters['max_area'])) {
            $sql .= " AND p.area <= ?";
            $params[] = (float) $filters['max_area'];
        }
        if (!empty($filters['keyword'])) {
            $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
            $kw = '%' . $filters['keyword'] . '%';
            $params[] = $kw;
            $params[] = $kw;
        }
        if (!empty($filters['trusted_only'])) {
            $sql .= " AND u.trust_badge_status IN ('aprovado', 'approved') AND u.trust_badge_fee_paid = 1";
        }

        return [$db, $sql, $params];
    }

    public static function create($data) {
        $db = new self();
        return $db->Salvar($data, $db->table);
    }

    public static function getFeatured() {
        $db = new self();
        $sql = "SELECT p.*, u.name AS owner_name, u.phone AS owner_phone,
                CASE WHEN u.status = 'ativo' THEN 1 ELSE 0 END AS owner_verified,
                CASE WHEN u.status = 'ativo'
                    AND u.trust_badge_status IN ('aprovado', 'approved')
                    AND u.trust_badge_fee_paid = 1
                THEN 1 ELSE 0 END AS owner_trusted
                FROM {$db->table} p
                LEFT JOIN users u ON p.affiliate_id = u.id
                WHERE p.featured = 1 AND p.status = 'disponivel'
                ORDER BY (p.visibility = 'premium') DESC, p.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getFiltered($filters = [], $limit = null, $offset = null) {
        list($db, $sql, $params) = self::buildFilteredQuery($filters, false);

        $sortMap = [
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'newest'     => 'p.created_at DESC',
            'oldest'     => 'p.created_at ASC',
        ];
        $sortKey = $filters['sort'] ?? 'newest';
        $orderBy = $sortMap[$sortKey] ?? 'p.created_at DESC';
        // Paid featured listings always come first in discovery pages.
        $sql .= " ORDER BY (p.featured = 1) DESC, (p.visibility = 'premium') DESC, {$orderBy}";

        if ($limit !== null) {
            $sql .= " LIMIT " . max(1, (int) $limit);
        }
        if ($offset !== null) {
            $sql .= " OFFSET " . max(0, (int) $offset);
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function countFiltered(array $filters = []): int {
        list($db, $sql, $params) = self::buildFilteredQuery($filters, true);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int) ($row['total'] ?? 0);
    }

    public static function find($id) {
        $db = new self();
        $sql = "SELECT p.*, u.name AS owner_name,
                CASE WHEN u.status = 'ativo' THEN 1 ELSE 0 END AS owner_verified,
                CASE WHEN u.status = 'ativo'
                    AND u.trust_badge_status IN ('aprovado', 'approved')
                    AND u.trust_badge_fee_paid = 1
                THEN 1 ELSE 0 END AS owner_trusted
                FROM {$db->table} p
                LEFT JOIN users u ON p.affiliate_id = u.id
                WHERE p.id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public static function getByAffiliate($affiliateId) {
        $db = new self();
        $sql = "SELECT * FROM {$db->table} WHERE affiliate_id = ? ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$affiliateId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getPending() {
        $db = new self();
        $sql = "SELECT p.*, u.name as owner_name FROM {$db->table} p LEFT JOIN users u ON p.affiliate_id = u.id WHERE p.status = 'pendente' ORDER BY p.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function approve($id) {
        $db = new self();
        $sql = "UPDATE {$db->table} SET status = 'disponivel' WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$id]);
    }

    public static function reject($id) {
        $db = new self();
        $sql = "UPDATE {$db->table} SET status = 'rejeitado' WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$id]);
    }

    public static function validateData($data) {
        $errors = [];
        $allowedTypes = ['casa', 'edificio', 'vivenda', 'terreno', 'apartamento'];
        $allowedPurposes = ['venda', 'aluguer_curto', 'aluguer_longo'];

        if (empty($data['title'])) {
            $errors[] = 'Título é obrigatório';
        } elseif (mb_strlen((string) $data['title']) > 255) {
            $errors[] = 'Título excede 255 caracteres';
        }

        if (empty($data['description'])) {
            $errors[] = 'Descrição é obrigatória';
        }

        if (empty($data['type']) || !in_array((string) $data['type'], $allowedTypes, true)) {
            $errors[] = 'Tipo de imóvel inválido';
        }

        if (empty($data['purpose']) || !in_array((string) $data['purpose'], $allowedPurposes, true)) {
            $errors[] = 'Finalidade inválida';
        }

        if (!isset($data['price']) || !is_numeric($data['price']) || (float) $data['price'] <= 0) {
            $errors[] = 'Preço deve ser numérico e maior que zero';
        }

        if (empty($data['location'])) {
            $errors[] = 'Localização é obrigatória';
        } elseif (mb_strlen((string) $data['location']) > 255) {
            $errors[] = 'Localização excede 255 caracteres';
        }

        if (isset($data['bedrooms']) && (int) $data['bedrooms'] < 0) {
            $errors[] = 'Quartos não pode ser negativo';
        }

        if (isset($data['bathrooms']) && (int) $data['bathrooms'] < 0) {
            $errors[] = 'Casas de banho não pode ser negativo';
        }

        if (isset($data['area']) && $data['area'] !== null && $data['area'] !== '' && (float) $data['area'] < 0) {
            $errors[] = 'Área não pode ser negativa';
        }

        if (isset($data['owner_bonus_pct']) && ((float) $data['owner_bonus_pct'] < 0 || (float) $data['owner_bonus_pct'] > 99.99)) {
            $errors[] = 'Acréscimo de comissão deve estar entre 0 e 99.99';
        }

        if (isset($data['images']) && $data['images'] !== '' && $data['images'] !== null) {
            $decoded = json_decode((string) $data['images'], true);
            if (!is_array($decoded)) {
                $errors[] = 'Formato de imagens inválido';
            }
        }

        return $errors;
    }

    public static function countByOwner($ownerId) {
        $db = new self();
        $sql = "SELECT COUNT(*) AS total FROM {$db->table} WHERE affiliate_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$ownerId]);
        return (int) ($stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public static function getStatusStats(): array {
        $db = new self();
        $sql = "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) AS pendente,
            SUM(CASE WHEN status = 'disponivel' THEN 1 ELSE 0 END) AS disponivel,
            SUM(CASE WHEN status = 'vendido' THEN 1 ELSE 0 END) AS vendido,
            SUM(CASE WHEN status = 'alugado' THEN 1 ELSE 0 END) AS alugado,
            SUM(CASE WHEN status = 'rejeitado' THEN 1 ELSE 0 END) AS rejeitado,
            SUM(CASE WHEN created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN 1 ELSE 0 END) AS new_this_month,
            ROUND(
                SUM(CASE WHEN status = 'disponivel' THEN 1 ELSE 0 END) /
                NULLIF(SUM(CASE WHEN status IN ('disponivel','rejeitado') THEN 1 ELSE 0 END), 0) * 100, 1
            ) AS approval_rate
        FROM {$db->table}";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    }
}