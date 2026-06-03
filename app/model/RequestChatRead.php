<?php

namespace App\model;

class RequestChatRead extends ManipularBanco
{
    protected $table = 'request_chat_reads';

    public static function markThreadRead(int $threadId, int $userId): bool
    {
        if ($threadId <= 0 || $userId <= 0) {
            return false;
        }

        $db = new self();
        $sql = "INSERT INTO {$db->table} (thread_id, user_id, last_read_at, created_at, updated_at)
                VALUES (?, ?, NOW(), NOW(), NOW())
                ON DUPLICATE KEY UPDATE last_read_at = NOW(), updated_at = NOW()";
        $stmt = $db->prepare($sql);
        $ok = (bool) $stmt->execute([$threadId, $userId]);
        if ($ok) {
            \App\services\HeaderShellService::invalidateChat($userId);
        }
        return $ok;
    }
}
