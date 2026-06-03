<?php

namespace App\model;

class MetricEvent extends ManipularBanco
{
    protected string $table = 'metric_events';

    public static function track(string $eventType, array $payload = []): bool
    {
        $db = new self();
        $data = [
            'event_type'  => $eventType,
            'entity_type' => $payload['entity_type'] ?? null,
            'entity_id'   => $payload['entity_id'] ?? null,
            'user_id'     => $payload['user_id'] ?? null,
            'metadata'    => isset($payload['metadata']) ? json_encode($payload['metadata']) : null,
            'created_at'  => date('Y-m-d H:i:s'),
        ];

        return $db->Salvar($data, $db->table);
    }

    public static function getSummary(string $eventType, string $start, string $end): array
    {
        $db = new self();
        $sql = "SELECT COUNT(*) AS total, entity_type, entity_id
                FROM {$db->table}
                WHERE event_type = ?
                  AND created_at BETWEEN ? AND ?
                GROUP BY entity_type, entity_id
                ORDER BY total DESC
                LIMIT 50";
        $stmt = $db->prepare($sql);
        $stmt->execute([$eventType, $start, $end]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
