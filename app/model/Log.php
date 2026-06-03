<?php

namespace App\model;

class Log extends ManipularBanco
{
    protected $table = 'logs';

    public static function create($data)
    {
        $db = new self();
        $data['ip_address'] = self::getClientIp();
        $data['created_at'] = date('Y-m-d H:i:s');
        return $db->Salvar($data, $db->table);
    }

    private static function getClientIp(): string
    {
        $candidates = [
            (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''),
            (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }
            $ip = trim(explode(',', $candidate)[0]);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    public static function getRecent(int $limit = 50, int $offset = 0): array
    {
        $db = new self();
        $sql = "SELECT l.*, u.id AS actor_id, u.name AS actor_name
                FROM {$db->table} l
                LEFT JOIN users u ON l.user_id = u.id
                ORDER BY l.created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cursor (keyset) pagination for recent logs.
     */
    public static function getRecentCursor(int $limit = 50, ?string $cursorCreatedAt = null, ?int $cursorId = null): array
    {
        $db = new self();
        $limit = min(200, max(1, (int) $limit));
        $sql = "SELECT l.*, u.id AS actor_id, u.name AS actor_name
                FROM {$db->table} l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE 1=1";
        $params = [];
        if ($cursorCreatedAt !== null && trim($cursorCreatedAt) !== '' && $cursorId !== null && $cursorId > 0) {
            $sql .= ' AND (l.created_at < ? OR (l.created_at = ? AND l.id < ?))';
            $params[] = $cursorCreatedAt;
            $params[] = $cursorCreatedAt;
            $params[] = (int) $cursorId;
        }
        $sql .= ' ORDER BY l.created_at DESC, l.id DESC LIMIT ?';
        $params[] = $limit;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getByEntity(string $entityType, int $entityId, int $limit = 0): array
    {
        $db = new self();
        $sql = "SELECT l.*, u.id AS actor_id, u.name AS actor_name
                FROM {$db->table} l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE l.entity_type = ? AND l.entity_id = ?
                ORDER BY l.created_at DESC";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) max(1, $limit);
        }
        $stmt = $db->prepare($sql);
        $stmt->execute([$entityType, $entityId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch logs for multiple entities in a single query and group them by entity_id.
     * Applies a soft cap (limitTotal) and enforces limitPerEntity in PHP.
     */
    public static function getByEntities(string $entityType, array $entityIds, int $limitPerEntity = 20, int $limitTotal = 0): array
    {
        $entityIds = array_values(array_unique(array_map('intval', array_filter($entityIds, static function ($id): bool {
            return (int) $id > 0;
        }))));
        if (empty($entityIds)) {
            return [];
        }

        $limitPerEntity = max(1, (int) $limitPerEntity);
        if ($limitTotal <= 0) {
            $limitTotal = min(5000, count($entityIds) * $limitPerEntity);
        }

        $db = new self();
        $placeholders = implode(',', array_fill(0, count($entityIds), '?'));
        $sql = "SELECT l.*, u.id AS actor_id, u.name AS actor_name
                FROM {$db->table} l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE l.entity_type = ?
                  AND l.entity_id IN ({$placeholders})
                ORDER BY l.entity_id ASC, l.created_at DESC, l.id DESC
                LIMIT " . (int) max(1, $limitTotal);
        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge([$entityType], $entityIds));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $grouped = [];
        $counts = [];
        foreach ($rows as $row) {
            $eid = (int) ($row['entity_id'] ?? 0);
            if ($eid <= 0) {
                continue;
            }
            $counts[$eid] = (int) ($counts[$eid] ?? 0);
            if ($counts[$eid] >= $limitPerEntity) {
                continue;
            }
            $counts[$eid]++;
            if (!isset($grouped[$eid])) {
                $grouped[$eid] = [];
            }
            $grouped[$eid][] = $row;
        }

        return $grouped;
    }

    public static function getByActor(int $userId, int $limit = 50): array
    {
        $db = new self();
        $sql = "SELECT l.*, u.id AS actor_id, u.name AS actor_name
                FROM {$db->table} l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE l.user_id = ?
                ORDER BY l.created_at DESC
                LIMIT ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function countAll(): int
    {
        $db = new self();
        $sql = "SELECT COUNT(*) AS total FROM {$db->table}";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return (int) ($stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public static function sensitiveRead(?int $actorId, string $entityType, ?int $entityId, string $details = ''): bool
    {
        $db = new self();
        $data = [
            'user_id' => $actorId,
            'action' => 'sensitive_read',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        return (bool) $db->Salvar($data, $db->table);
    }
}
