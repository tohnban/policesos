<?php

namespace App\controller;

use App\model\Notification;

class ControllerApiV1Notifications
{
    use ApiControllerSupport;

    private function requireUserId(array $apiToken): int
    {
        $userId = (int) ($apiToken['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->respond(false, null, 'User-bound tokens required', 403);
        }

        return $userId;
    }

    public function inbox(): void
    {
        $apiToken = $this->beginV1Request();
        $userId = $this->requireUserId($apiToken);

        $cursorRaw = isset($_GET['cursor']) ? (string) $_GET['cursor'] : '';
        $cursor = $this->decodeCursor($cursorRaw);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(20, max(1, (int) ($_GET['per_page'] ?? 10)));
        $offset = ($page - 1) * $limit;

        if ($cursor) {
            $notifications = Notification::getInboxByUserCursor(
                $userId,
                $limit,
                isset($cursor['created_at']) ? (string) $cursor['created_at'] : null,
                isset($cursor['id']) ? (int) $cursor['id'] : null
            );
            $total = null;
        } else {
            $notifications = Notification::getInboxByUser($userId, $limit, $offset);
            $total = Notification::countInboxByUser($userId);
        }
        $unread = Notification::countUnreadByUser($userId);

        $nextCursor = null;
        if (!empty($notifications)) {
            $last = $notifications[count($notifications) - 1];
            $nextCursor = $this->encodeCursor($last['created_at'] ?? null, isset($last['id']) ? (int) $last['id'] : null);
        }

        $this->logApiRequest($apiToken, 'api.notifications.inbox', 200, 'Inbox retrieved');
        $this->respond(true, [
            'page' => $cursor ? null : $page,
            'per_page' => $limit,
            'total' => $total,
            'unread' => $unread,
            'cursor' => $cursorRaw !== '' ? $cursorRaw : null,
            'next_cursor' => $nextCursor,
            'notifications' => $notifications,
        ]);
    }

    public function mutate(): void
    {
        $apiToken = $this->beginV1Request();
        $userId = $this->requireUserId($apiToken);

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = (string) ($input['action'] ?? 'mark_as_read');
        $notificationId = (int) ($input['notification_id'] ?? 0);

        if ($action === 'mark_as_read' && $notificationId > 0) {
            Notification::markAsReadByUser($notificationId, $userId);
            $this->logApiRequest($apiToken, 'api.notifications.mark_as_read', 200, 'Marked as read');
            $this->respond(true, ['marked' => true]);
        }

        if ($action === 'mark_all_as_read') {
            Notification::markAllAsReadByUser($userId);
            $this->logApiRequest($apiToken, 'api.notifications.mark_all_as_read', 200, 'Marked all as read');
            $this->respond(true, ['marked_all' => true]);
        }

        if ($action === 'archive' && $notificationId > 0) {
            Notification::archiveByUser($notificationId, $userId);
            $this->logApiRequest($apiToken, 'api.notifications.archive', 200, 'Archived');
            $this->respond(true, ['archived' => true]);
        }

        if ($action === 'archive_all') {
            Notification::archiveAllByUser($userId);
            $this->logApiRequest($apiToken, 'api.notifications.archive_all', 200, 'Archived all');
            $this->respond(true, ['archived_all' => true]);
        }

        if ($action === 'unarchive' && $notificationId > 0) {
            Notification::unarchiveByUser($notificationId, $userId);
            $this->logApiRequest($apiToken, 'api.notifications.unarchive', 200, 'Unarchived');
            $this->respond(true, ['unarchived' => true]);
        }

        $this->respond(false, null, 'Invalid action', 400);
    }

    public function archive(): void
    {
        $apiToken = $this->beginV1Request();
        $userId = $this->requireUserId($apiToken);

        $cursorRaw = isset($_GET['cursor']) ? (string) $_GET['cursor'] : '';
        $cursor = $this->decodeCursor($cursorRaw);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(30, max(1, (int) ($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $typeFilter = trim((string) ($_GET['type'] ?? ''));
        $typeFilter = $typeFilter === '' ? null : $typeFilter;

        if ($cursor) {
            $notifications = Notification::getArchiveByUserCursor(
                $userId,
                $limit,
                isset($cursor['created_at']) ? (string) $cursor['created_at'] : null,
                isset($cursor['id']) ? (int) $cursor['id'] : null,
                $typeFilter
            );
            $total = null;
        } else {
            $notifications = Notification::getArchiveByUser($userId, $limit, $offset, $typeFilter);
            $total = Notification::countArchiveByUser($userId, $typeFilter);
        }

        $nextCursor = null;
        if (!empty($notifications)) {
            $last = $notifications[count($notifications) - 1];
            $nextCursor = $this->encodeCursor($last['created_at'] ?? null, isset($last['id']) ? (int) $last['id'] : null);
        }

        $this->logApiRequest($apiToken, 'api.notifications.archive', 200, 'Archive retrieved');
        $this->respond(true, [
            'page' => $cursor ? null : $page,
            'per_page' => $limit,
            'total' => $total,
            'cursor' => $cursorRaw !== '' ? $cursorRaw : null,
            'next_cursor' => $nextCursor,
            'notifications' => $notifications,
        ]);
    }

    public function delete($id): void
    {
        $apiToken = $this->beginV1Request();
        $userId = $this->requireUserId($apiToken);

        $notificationId = (int) $id;
        $ok = Notification::deleteArchivedByUser($notificationId, $userId);
        if (!$ok) {
            $this->logApiRequest($apiToken, 'api.notifications.delete', 404, 'Notification not found or not archived');
            $this->respond(false, null, 'Notification not found or not archived', 404);
        }

        $this->logApiRequest($apiToken, 'api.notifications.delete', 200, 'Archived notification deleted');
        $this->respond(true, ['deleted' => true]);
    }
}
