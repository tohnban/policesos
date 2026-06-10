<?php

namespace App\controller;

use App\model\Notification;
use Src\classes\ClassAccess;
use Src\classes\ClassCsrf;

class ControllerNotificationActions
{

    public function markAsRead()
    {
        $user = ClassAccess::requireAuthenticatedAccount('login', 'Você precisa estar autenticado');
        $userId = (int) ($user['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'notification/inbox');
            exit;
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

        if ($archived) {
            header('Location: ' . DIRPAGE . 'notification/inbox?success=' . urlencode('Notificação arquivada'));
        } else {
            header('Location: ' . DIRPAGE . 'notification/inbox?error=' . urlencode('Não foi possível arquivar a notificação'));
        }
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
