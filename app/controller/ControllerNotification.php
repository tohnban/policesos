<?php

namespace App\controller;

use App\model\Notification;
use Src\classes\ClassAccess;
use Src\classes\ClassCsrf;
use Src\classes\ClassRender;
use Src\traits\TraitUrlParser;

class ControllerNotification
{
    use TraitUrlParser;

    private function decodeCursor(?string $cursor): ?array
    {
        $cursor = trim((string) $cursor);
        if ($cursor === '') {
            return null;
        }

        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);
        if ($decoded === false || $decoded === '') {
            return null;
        }

        $data = json_decode($decoded, true);
        return is_array($data) ? $data : null;
    }

    private function encodeCursor(?string $createdAt, ?int $id): ?string
    {
        $createdAt = $createdAt !== null ? trim((string) $createdAt) : '';
        $id = (int) ($id ?? 0);
        if ($createdAt === '' || $id <= 0) {
            return null;
        }
        $payload = json_encode(['created_at' => $createdAt, 'id' => $id], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $b64 = base64_encode((string) $payload);
        return rtrim(strtr($b64, '+/', '-_'), '=');
    }

    public function inbox()
    {
        $user = ClassAccess::requireAuthenticatedAccount('login', 'Você precisa estar autenticado');
        $userId = (int) ($user['id'] ?? 0);

        $cursorRaw = isset($_GET['cursor']) ? (string) $_GET['cursor'] : '';
        $cursor = $this->decodeCursor($cursorRaw);
        $cursorMode = $cursor !== null;

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $nextCursor = null;
        if ($cursorMode) {
            $notifications = Notification::getInboxByUserCursor(
                $userId,
                $limit,
                isset($cursor['created_at']) ? (string) $cursor['created_at'] : null,
                isset($cursor['id']) ? (int) $cursor['id'] : null
            );
            if (!empty($notifications)) {
                $last = $notifications[count($notifications) - 1];
                $nextCursor = $this->encodeCursor($last['created_at'] ?? null, isset($last['id']) ? (int) $last['id'] : null);
            }
            $total = count($notifications);
            $totalPages = 1;
            $page = 1;
        } else {
            $total = Notification::countInboxByUser($userId);
            $notifications = Notification::getInboxByUser($userId, $limit, $offset);
            $totalPages = max(1, (int) ceil($total / $limit));
        }

        if (!$cursorMode && $page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $limit;
            $notifications = Notification::getInboxByUser($userId, $limit, $offset);
        }

        $render = new ClassRender();
        $render->setTitle('Notificações - Inbox');
        $render->setDescription('Gerencie suas notificações');
        $render->setData([
            'notifications' => $notifications,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'total' => $total,
            'unread' => Notification::countUnreadByUser($userId),
            'cursorMode' => $cursorMode,
            'cursor' => $cursorRaw,
            'nextCursor' => $nextCursor,
        ]);
        $render->setDir('dashboard/notification_inbox');
        $render->renderLayout();
    }

    public function archive()
    {
        $user = ClassAccess::requireAuthenticatedAccount('login', 'Você precisa estar autenticado');
        $userId = (int) ($user['id'] ?? 0);

        $cursorRaw = isset($_GET['cursor']) ? (string) $_GET['cursor'] : '';
        $cursor = $this->decodeCursor($cursorRaw);
        $cursorMode = $cursor !== null;

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $typeFilter = trim((string) ($_GET['type'] ?? ''));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $typeFilter = $typeFilter === '' ? null : $typeFilter;
        $nextCursor = null;
        if ($cursorMode) {
            $notifications = Notification::getArchiveByUserCursor(
                $userId,
                $limit,
                isset($cursor['created_at']) ? (string) $cursor['created_at'] : null,
                isset($cursor['id']) ? (int) $cursor['id'] : null,
                $typeFilter
            );
            if (!empty($notifications)) {
                $last = $notifications[count($notifications) - 1];
                $nextCursor = $this->encodeCursor($last['created_at'] ?? null, isset($last['id']) ? (int) $last['id'] : null);
            }
            $total = count($notifications);
            $totalPages = 1;
            $page = 1;
        } else {
            $total = Notification::countArchiveByUser($userId, $typeFilter);
            $notifications = Notification::getArchiveByUser($userId, $limit, $offset, $typeFilter);
            $totalPages = max(1, (int) ceil($total / $limit));
        }

        if (!$cursorMode && $page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $limit;
            $notifications = Notification::getArchiveByUser($userId, $limit, $offset, $typeFilter);
        }

        $render = new ClassRender();
        $render->setTitle('Notificações - Arquivo');
        $render->setDescription('Veja suas notificações arquivadas');
        $render->setData([
            'notifications' => $notifications,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'total' => $total,
            'typeFilter' => $typeFilter,
            'cursorMode' => $cursorMode,
            'cursor' => $cursorRaw,
            'nextCursor' => $nextCursor,
        ]);
        $render->setDir('dashboard/notification_archive');
        $render->renderLayout();
    }

    public function markAsRead()
    {
        $user = ClassAccess::requireAuthenticatedAccount('login', 'Você precisa estar autenticado');
        $userId = (int) ($user['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'notification/inbox');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::failRedirect('notification/inbox', 'Token inválido');
        }

        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if ($notificationId <= 0) {
            header('Location: ' . DIRPAGE . 'notification/inbox?error=Invalid notification');
            exit;
        }

        Notification::markAsReadByUser($notificationId, $userId);
        header('Location: ' . DIRPAGE . 'notification/inbox');
        exit;
    }

    public function markAllAsRead()
    {
        $user = ClassAccess::requireAuthenticatedAccount('login', 'Você precisa estar autenticado');
        $userId = (int) ($user['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'notification/inbox');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::failRedirect('notification/inbox', 'Token inválido');
        }

        Notification::markAllAsReadByUser($userId);
        header('Location: ' . DIRPAGE . 'notification/inbox');
        exit;
    }

    public function archiveItem()
    {
        $user = ClassAccess::requireAuthenticatedAccount('login', 'Você precisa estar autenticado');
        $userId = (int) ($user['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'notification/inbox');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::failRedirect('notification/inbox', 'Token inválido');
        }

        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if ($notificationId <= 0) {
            header('Location: ' . DIRPAGE . 'notification/inbox?error=' . urlencode('Notificação inválida'));
            exit;
        }

        $archived = Notification::archiveByUser($notificationId, $userId);
        $isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => $archived,
                'csrf_token' => ClassCsrf::get(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        header('Location: ' . DIRPAGE . 'notification/inbox');
        exit;
    }

    public function archiveAll()
    {
        $user = ClassAccess::requireAuthenticatedAccount('login', 'Você precisa estar autenticado');
        $userId = (int) ($user['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'notification/inbox');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::failRedirect('notification/inbox', 'Token inválido');
        }

        Notification::archiveAllByUser($userId);
        header('Location: ' . DIRPAGE . 'notification/inbox');
        exit;
    }

    public function unarchive()
    {
        $user = ClassAccess::requireAuthenticatedAccount('login', 'Você precisa estar autenticado');
        $userId = (int) ($user['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'notification/archive');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::failRedirect('notification/archive', 'Token inválido');
        }

        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if ($notificationId <= 0) {
            header('Location: ' . DIRPAGE . 'notification/archive?error=Invalid notification');
            exit;
        }

        Notification::unarchiveByUser($notificationId, $userId);
        header('Location: ' . DIRPAGE . 'notification/archive');
        exit;
    }

    public function delete()
    {
        $user = ClassAccess::requireAuthenticatedAccount('login', 'Você precisa estar autenticado');
        $userId = (int) ($user['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'notification/archive');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::failRedirect('notification/archive', 'Token inválido');
        }

        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if ($notificationId <= 0) {
            header('Location: ' . DIRPAGE . 'notification/archive?error=Invalid notification');
            exit;
        }

        Notification::deleteArchivedByUser($notificationId, $userId);
        header('Location: ' . DIRPAGE . 'notification/archive');
        exit;
    }
}
