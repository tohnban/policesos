<?php

namespace App\model;

class RequestChatMessage extends ManipularBanco
{
    protected $table = 'request_chat_messages';

    public static function findByAttachmentPath(string $attachmentPath): ?array
    {
        $attachmentPath = trim((string) $attachmentPath);
        if ($attachmentPath === '') {
            return null;
        }

        $db = new self();
        $sql = "SELECT m.*, t.request_id
                FROM {$db->table} m
                JOIN request_chat_threads t ON t.id = m.thread_id
                WHERE m.attachment_path = ?
                  AND m.deleted_at IS NULL
                ORDER BY m.created_at DESC, m.id DESC
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$attachmentPath]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public static function getSummariesByRequestIds(array $requestIds, int $userId = 0): array
    {
        $requestIds = array_values(array_unique(array_map('intval', array_filter($requestIds, static function ($id): bool {
            return (int) $id > 0;
        }))));
        if (empty($requestIds)) {
            return [];
        }

        $db = new self();
        $placeholders = implode(',', array_fill(0, count($requestIds), '?'));
        $params = $requestIds;
        $unreadSql = '0 AS unread_count';
        if ($userId > 0) {
            $unreadSql = "COALESCE((
                        SELECT COUNT(*)
                        FROM {$db->table} um
                        WHERE um.thread_id = t.id
                          AND um.sender_user_id <> ?
                          AND um.deleted_at IS NULL
                          AND um.created_at > COALESCE(chat_reads.last_read_at, '1970-01-01 00:00:00')
                    ), 0) AS unread_count";
            $params = array_merge([$userId], $requestIds);
        }

        $sql = "SELECT
                    t.request_id,
                    t.id AS thread_id,
                    t.last_message_at,
                    COALESCE(cnt.total_messages, 0) AS total_messages,
                    last_msg.message_text AS last_message_text,
                    last_msg.message_type AS last_message_type,
                    sender.username AS last_sender_username,
                    sender.name AS last_sender_name,
                    {$unreadSql}
                FROM request_chat_threads t
                LEFT JOIN (
                    SELECT thread_id, COUNT(*) AS total_messages
                    FROM {$db->table}
                    WHERE deleted_at IS NULL
                    GROUP BY thread_id
                ) cnt ON cnt.thread_id = t.id
                LEFT JOIN request_chat_reads chat_reads
                    ON chat_reads.thread_id = t.id
                   AND chat_reads.user_id = " . (int) $userId . "
                LEFT JOIN {$db->table} last_msg
                    ON last_msg.id = (
                        SELECT m2.id
                        FROM {$db->table} m2
                        WHERE m2.thread_id = t.id
                          AND m2.deleted_at IS NULL
                        ORDER BY m2.created_at DESC, m2.id DESC
                        LIMIT 1
                    )
                LEFT JOIN users sender ON sender.id = last_msg.sender_user_id
                WHERE t.request_id IN ({$placeholders})";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $summaries = [];
        foreach ($rows as $row) {
            $summaries[(int) ($row['request_id'] ?? 0)] = $row;
        }

        return $summaries;
    }

    public static function getByThreadId(int $threadId, int $limit = 200): array
    {
        $db = new self();
        $sql = "SELECT m.*, u.username AS sender_username, u.name AS sender_name, u.email AS sender_email
                FROM {$db->table} m
                JOIN users u ON u.id = m.sender_user_id
                WHERE m.thread_id = ?
                  AND m.deleted_at IS NULL
                ORDER BY m.created_at ASC
                LIMIT ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$threadId, max(1, $limit)]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function createForThread(int $threadId, int $senderUserId, string $messageText, string $messageType = 'text', ?string $attachmentPath = null): int|false
    {
        $db = new self();
        $data = [
            'thread_id' => $threadId,
            'sender_user_id' => $senderUserId,
            'message_text' => $messageText,
            'message_type' => $messageType,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($attachmentPath !== null && $attachmentPath !== '') {
            $data['attachment_path'] = (string) $attachmentPath;
        }

        $created = $db->Salvar($data, $db->table);
        if ($created) {
            RequestChatThread::touch($threadId);
            RequestChatRead::markThreadRead($threadId, $senderUserId);
            \App\services\HeaderShellService::invalidateChat($senderUserId);
        }

        return is_int($created) ? $created : false;
    }

    public static function createSystemForRequest(int $requestId, int $senderUserId, string $messageText, ?string $attachmentPath = null): int|false
    {
        $thread = RequestChatThread::getOrCreateByRequestId($requestId);
        if (!$thread || empty($thread['id'])) {
            return false;
        }

        return self::createForThread((int) $thread['id'], $senderUserId, $messageText, 'system', $attachmentPath);
    }

    public static function countUnreadByUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $db = new self();

        $sql = "SELECT COALESCE(SUM(unread_in_thread), 0) AS total_unread
                FROM (
                    SELECT COUNT(*) AS unread_in_thread
                    FROM {$db->table} m
                    WHERE m.sender_user_id <> ?
                      AND m.deleted_at IS NULL
                      AND m.thread_id IN (
                        SELECT DISTINCT thread_id
                        FROM request_chat_reads
                        WHERE user_id = ?
                      )
                      AND m.created_at > COALESCE((
                        SELECT MAX(cr.last_read_at)
                        FROM request_chat_reads cr
                        WHERE cr.thread_id = m.thread_id
                          AND cr.user_id = ?
                      ), '1970-01-01 00:00:00')
                    GROUP BY m.thread_id
                ) unread_threads";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $userId, $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int) ($result['total_unread'] ?? 0);
    }

    public static function countUnreadForVisibleRequests(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $db = new self();

        $sql = "SELECT COALESCE(SUM(unread_in_thread), 0) AS total_unread
                FROM (
                    SELECT COUNT(*) AS unread_in_thread
                    FROM {$db->table} m
                    WHERE m.sender_user_id <> ?
                      AND m.deleted_at IS NULL
                      AND m.thread_id IN (
                        SELECT DISTINCT rt.id
                        FROM request_chat_threads rt
                        JOIN requests r ON rt.request_id = r.id
                        JOIN request_chat_reads cr ON rt.id = cr.thread_id
                        WHERE cr.user_id = ?
                          AND (r.user_id = ? OR r.property_id IN (
                            SELECT p.id FROM properties p WHERE p.affiliate_id = ?
                          ))
                      )
                      AND m.created_at > COALESCE((
                        SELECT MAX(cr.last_read_at)
                        FROM request_chat_reads cr
                        WHERE cr.thread_id = m.thread_id
                          AND cr.user_id = ?
                      ), '1970-01-01 00:00:00')
                    GROUP BY m.thread_id
                ) unread_threads";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $userId, $userId, $userId, $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int) ($result['total_unread'] ?? 0);
    }
}
