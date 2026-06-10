<?php

namespace App\controller;

use App\model\Notification;
use Src\classes\ClassAuth;
use Src\classes\ClassCsrf;

class ControllerDashboardNotifications
{
    public function markNotificationsRead()
    {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'dashboard?error=Token inválido');
            exit;
        }

        Notification::markAllAsReadByUser((int) $user['id']);
        header('Location: ' . DIRPAGE . 'dashboard?success=Notificações marcadas como lidas');
        exit;
    }

    public function markNotificationRead($id)
    {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        $notificationId = (int) $id;
        $isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'Token inválido',
                    'csrf_token' => ClassCsrf::get(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            header('Location: ' . DIRPAGE . 'dashboard?error=Token inválido');
            exit;
        }

        $marked = false;
        if ($notificationId > 0) {
            $marked = Notification::markAsReadByUser($notificationId, (int) $user['id']);
        }

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => $marked,
                'unread_count' => Notification::countUnreadByUser((int) $user['id']),
                'csrf_token' => ClassCsrf::get(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        header('Location: ' . DIRPAGE . 'notification/inbox');
        exit;
    }

    public function markNotificationUnread($id)
    {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        $notificationId = (int) $id;
        $isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'Token inválido',
                    'csrf_token' => ClassCsrf::get(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            header('Location: ' . DIRPAGE . 'dashboard?error=Token inválido');
            exit;
        }

        $marked = false;
        if ($notificationId > 0) {
            $marked = Notification::markAsUnreadByUser($notificationId, (int) $user['id']);
        }

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => $marked,
                'unread_count' => Notification::countUnreadByUser((int) $user['id']),
                'csrf_token' => ClassCsrf::get(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        header('Location: ' . DIRPAGE . 'notification/inbox');
        exit;
    }

    public function notificationsFeed()
    {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        header('Content-Type: application/json; charset=utf-8');

        $notifications = Notification::getLatestByUser((int) $user['id'], 5);
        $payload = array_map(static function (array $notification): array {
            return [
                'id' => (int) ($notification['id'] ?? 0),
                'title' => (string) ($notification['title'] ?? ''),
                'message' => (string) ($notification['message'] ?? ''),
                'type' => (string) ($notification['type'] ?? ''),
                'type_label' => (string) ($notification['type_label'] ?? ''),
                'target_url' => (string) ($notification['target_url'] ?? (DIRPAGE . 'notification/inbox')),
                'mark_read_url' => DIRPAGE . 'dashboard/markNotificationRead/' . (int) ($notification['id'] ?? 0),
                'mark_unread_url' => DIRPAGE . 'dashboard/markNotificationUnread/' . (int) ($notification['id'] ?? 0),
                'action_label' => (string) ($notification['action_label'] ?? 'Abrir'),
                'is_read' => !empty($notification['is_read']),
                'created_at' => (string) ($notification['created_at'] ?? ''),
                'created_at_label' => (string) ($notification['created_at_label'] ?? ''),
                'relative_time' => (string) ($notification['relative_time'] ?? ''),
                'type_icon' => (string) ($notification['type_icon'] ?? ''),
                'type_tone' => (string) ($notification['type_tone'] ?? ''),
            ];
        }, $notifications);

        echo json_encode([
            'unread_count' => Notification::countUnreadByUser((int) $user['id']),
            'notifications' => $payload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
