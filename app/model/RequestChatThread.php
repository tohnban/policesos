<?php

namespace App\model;

class RequestChatThread extends ManipularBanco
{
    protected $table = 'request_chat_threads';

    public static function findByRequestId(int $requestId): ?array
    {
        $db = new self();
        $sql = "SELECT * FROM {$db->table} WHERE request_id = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$requestId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getOrCreateByRequestId(int $requestId): ?array
    {
        $existing = self::findByRequestId($requestId);
        if ($existing) {
            return $existing;
        }

        $db = new self();
        $data = [
            'request_id' => $requestId,
            'status' => 'ativo',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'last_message_at' => null,
        ];

        $created = false;
        try {
            $id = $db->Salvar($data, $db->table);
            if ($id) {
                $created = true;
            }
        } catch (\PDOException $e) {
            if ((string) $e->getCode() !== '23000') {
                throw $e;
            }
        }

        $thread = self::findByRequestId($requestId);
        if ($thread && $created) {
            RequestChatMessage::ensureNegotiationContactPolicyMessage($requestId);
        }

        return $thread;
    }

    public static function touch(int $threadId): bool
    {
        $db = new self();
        $sql = "UPDATE {$db->table}
                SET last_message_at = NOW(), updated_at = NOW()
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        if (!$stmt->execute([$threadId])) {
            return false;
        }

        $lookup = $db->prepare("SELECT request_id FROM {$db->table} WHERE id = ? LIMIT 1");
        $lookup->execute([$threadId]);
        $requestId = (int) $lookup->fetchColumn();
        if ($requestId > 0) {
            Request::touchLastInteraction($requestId);
        }

        return true;
    }
}
