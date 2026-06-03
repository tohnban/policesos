<?php

namespace App\services;

use App\model\Commission;
use App\model\Favorite;
use App\model\Notification;
use App\model\RequestChatMessage;
use Src\classes\Cache;
use Src\classes\ClassAccess;
use Src\classes\RequestContext;
use Src\classes\UserDisplay;

final class HeaderShellService
{
    public const CACHE_TTL_SECONDS = 20;

    public static function forGuest(): array
    {
        return self::emptyShell();
    }

    public static function forAuthenticatedUser(array $user): array
    {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return self::emptyShell();
        }

        $cacheKey = self::cacheKey($userId);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, static function () use ($user, $userId): array {
            return self::buildUncached($user, $userId);
        });
    }

    public static function invalidateForUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }
        Cache::delete(self::cacheKey($userId));
        RequestContext::clearCommissionBlockReason($userId);
    }

    public static function invalidateNotifications(int $userId): void
    {
        self::invalidateForUser($userId);
    }

    public static function invalidateChat(int $userId): void
    {
        self::invalidateForUser($userId);
    }

    public static function invalidateFavorites(int $userId): void
    {
        self::invalidateForUser($userId);
    }

    public static function invalidateCommissionBlock(int $userId): void
    {
        RequestContext::clearCommissionBlockReason($userId);
        self::invalidateForUser($userId);
    }

    private static function cacheKey(int $userId): string
    {
        return 'header_shell:v1:' . $userId;
    }

    private static function emptyShell(): array
    {
        return [
            'unread_notifications' => 0,
            'unread_chat_messages' => 0,
            'favorite_count' => 0,
            'notifications_preview' => [],
            'overdue_commission' => [
                'show_banner' => false,
                'message' => '',
                'action_label' => 'Pagar comissões',
                'action_href' => DIRPAGE . 'dashboard/commissionPayments',
            ],
            'display' => [
                'name' => '',
                'role_label' => 'Utilizador',
                'profile_photo' => null,
            ],
            'flags' => [
                'is_limited' => false,
                'show_favorites_icon' => false,
                'show_chat_icon' => false,
                'show_notifications_menu' => false,
            ],
        ];
    }

    private static function buildUncached(array $user, int $userId): array
    {
        $isAdmin = !empty($user['is_admin']);
        $isLimited = ClassAccess::hasLimitedPlatformAccess($user);

        $notifPayload = Notification::getHeaderPayload($userId, 5);
        $unreadNotifications = (int) ($notifPayload['unread_count'] ?? 0);
        $headerNotifications = $notifPayload['preview'] ?? [];

        $unreadChatMessages = 0;
        if (!$isLimited) {
            $unreadChatMessages = $isAdmin
                ? RequestChatMessage::countUnreadByUser($userId)
                : RequestChatMessage::countUnreadForVisibleRequests($userId);
        }

        $favoriteCount = 0;
        if (!$isAdmin && !$isLimited) {
            $favoriteCount = Favorite::countByUser($userId);
        }

        $overdue = self::buildOverdueCommission($user, $userId);

        return [
            'unread_notifications' => $unreadNotifications,
            'unread_chat_messages' => $unreadChatMessages,
            'favorite_count' => $favoriteCount,
            'notifications_preview' => $headerNotifications,
            'overdue_commission' => $overdue,
            'display' => [
                'name' => UserDisplay::publicLabel($user),
                'role_label' => self::headerRoleLabel($user),
                'profile_photo' => !empty($user['profile_photo']) ? (string) $user['profile_photo'] : null,
            ],
            'flags' => [
                'is_limited' => $isLimited,
                'show_favorites_icon' => !$isAdmin && !$isLimited,
                'show_chat_icon' => !$isLimited,
                'show_notifications_menu' => !$isLimited,
            ],
        ];
    }

    /**
     * @return array{show_banner:bool,message:string,action_label:string,action_href:string}
     */
    private static function buildOverdueCommission(array $user, int $userId): array
    {
        $default = [
            'show_banner' => false,
            'message' => '',
            'action_label' => 'Pagar comissões',
            'action_href' => DIRPAGE . 'dashboard/commissionPayments',
        ];

        if (!ClassAccess::canSubmitPropertyRequest($user)) {
            return $default;
        }

        $overdueReason = RequestContext::commissionBlockReason($userId);
        if ($overdueReason === null) {
            return $default;
        }

        $actionLabel = 'Pagar comissões';
        if ($overdueReason === Commission::OVERDUE_BLOCK_AGUARDANDO_VALIDACAO) {
            $actionLabel = 'Ver pagamentos';
        }

        return [
            'show_banner' => true,
            'message' => Commission::overdueBlockMessage($overdueReason),
            'action_label' => $actionLabel,
            'action_href' => DIRPAGE . 'dashboard/commissionPayments',
        ];
    }

    private static function headerRoleLabel(array $user): string
    {
        $roleValue = ClassAccess::getRole($user);
        if ($roleValue === 'super_admin') {
            return 'Super Admin';
        }
        if ($roleValue === 'moderador') {
            return 'Moderador';
        }
        if ($roleValue === 'financeiro') {
            return 'Financeiro';
        }
        if ($roleValue === 'suporte') {
            return 'Suporte';
        }
        if (!empty($user['is_affiliate'])) {
            return 'Afiliado';
        }

        return 'Utilizador';
    }
}
