<?php
namespace App\model;

class BackgroundJob extends ManipularBanco {
    protected $table = 'background_jobs';

    public static function enqueue(
        string $queueName,
        array $payload,
        int $priority = 5,
        int $maxAttempts = 5,
        ?string $runAfter = null
    ): int {
        $db = new self();
        $sql = "INSERT INTO {$db->table}
                (queue_name, payload, status, priority, attempts, max_attempts, run_after, created_at, updated_at)
                VALUES (?, ?, 'pending', ?, 0, ?, COALESCE(?, NOW()), NOW(), NOW())";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            $queueName,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            max(1, min(10, $priority)),
            max(1, $maxAttempts),
            $runAfter,
        ]);

        return (int) $db->ConexaoDB()->lastInsertId();
    }

    public static function requeueStaleProcessing(int $lockTimeoutSeconds): int {
        $db = new self();
        $sql = "UPDATE {$db->table}
                SET status = 'pending',
                    locked_at = NULL,
                    locked_by = NULL,
                    updated_at = NOW()
                WHERE status = 'processing'
                  AND locked_at IS NOT NULL
                  AND locked_at < DATE_SUB(NOW(), INTERVAL ? SECOND)";
        $stmt = $db->prepare($sql);
        $stmt->execute([max(1, $lockTimeoutSeconds)]);
        return (int) $stmt->rowCount();
    }

    public static function claimBatch(string $queueName, string $workerId, int $limit): array {
        $db = new self();
        $limit = max(1, $limit);

        $selectSql = "SELECT id
                      FROM {$db->table}
                      WHERE queue_name = ?
                        AND status = 'pending'
                        AND attempts < max_attempts
                        AND run_after <= NOW()
                      ORDER BY priority DESC, id ASC
                      LIMIT {$limit}";
        $selectStmt = $db->prepare($selectSql);
        $selectStmt->execute([$queueName]);
        $ids = array_map('intval', array_column($selectStmt->fetchAll(\PDO::FETCH_ASSOC), 'id'));

        if (empty($ids)) {
            return [];
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $updateSql = "UPDATE {$db->table}
                      SET status = 'processing',
                          attempts = attempts + 1,
                          locked_at = NOW(),
                          locked_by = ?,
                          updated_at = NOW()
                      WHERE status = 'pending'
                        AND id IN ({$in})";

        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute(array_merge([$workerId], $ids));

        $fetchSql = "SELECT *
                     FROM {$db->table}
                     WHERE status = 'processing'
                       AND locked_by = ?
                       AND id IN ({$in})
                     ORDER BY priority DESC, id ASC";
        $fetchStmt = $db->prepare($fetchSql);
        $fetchStmt->execute(array_merge([$workerId], $ids));
        $rows = $fetchStmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['payload'] = json_decode((string) ($row['payload'] ?? '{}'), true) ?: [];
        }
        unset($row);

        return $rows;
    }

    public static function markCompleted(int $id): bool {
        $db = new self();
        $sql = "UPDATE {$db->table}
                SET status = 'completed',
                    completed_at = NOW(),
                    locked_at = NULL,
                    locked_by = NULL,
                    updated_at = NOW()
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$id]);
    }

    public static function markFailedOrRetry(int $id, string $error): bool {
        $db = new self();

        $sql = "UPDATE {$db->table}
                SET status = CASE WHEN attempts >= max_attempts THEN 'failed' ELSE 'pending' END,
                    failed_at = CASE WHEN attempts >= max_attempts THEN NOW() ELSE failed_at END,
                    last_error = ?,
                    locked_at = NULL,
                    locked_by = NULL,
                    run_after = CASE WHEN attempts >= max_attempts THEN run_after ELSE DATE_ADD(NOW(), INTERVAL LEAST(attempts * 2, 30) MINUTE) END,
                    updated_at = NOW()
                WHERE id = ?";

        $stmt = $db->prepare($sql);
        return $stmt->execute([substr($error, 0, 2000), $id]);
    }
}
