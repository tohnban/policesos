<?php

namespace Src\classes;

use App\model\Commission;

class ClassCommissionGuard
{
    private const EXEMPT_DASHBOARD_METHODS = [
        'commissionPayments',
        'commissionPayment',
        'submitCommissionPayment',
        'getPromoterTerms',
        'accountStatus',
        'update',
        'resubmitDocument',
        'submitAccountDocument',
    ];

    private const EXEMPT_ROUTE_KEYS = [
        'logout',
        'authenticate',
        'login',
        'register',
        'recover',
        'reset',
        'verify',
        'cookies',
        'sitemap',
    ];

    private const BLOCKED_ROUTE_KEYS = [
        'requests',
        'commissions',
        'referrals',
        'profile',
        'payment_accounts',
        'favorites',
        'payment_methods',
        'payment_channels',
        'payment_transactions',
        'settings',
        'moderate_users',
        'admin_subscriptions',
        'moderate',
    ];

    private const PROPERTY_READ_METHODS = [
        'index',
        'properties',
        'show',
        'featured',
        'agency',
        'list',
    ];

    public static function ownerHasBlockingOverdue(int $userId): bool
    {
        return $userId > 0 && RequestContext::commissionBlockReason($userId) !== null;
    }

    public static function ownerOverdueBlockMessage(int $userId): string
    {
        $reason = $userId > 0 ? RequestContext::commissionBlockReason($userId) : null;

        return Commission::overdueBlockMessage($reason);
    }

    public static function currentUserHasBlockingOverdue(): bool
    {
        if (!ClassAuth::check()) {
            return false;
        }

        $user = ClassAuth::user();
        if (ClassAccess::isAdmin($user)) {
            return false;
        }

        return self::ownerHasBlockingOverdue((int) ($user['id'] ?? 0));
    }

    public static function enforce(string $controller, array $url): void
    {
        if (!self::currentUserHasBlockingOverdue()) {
            return;
        }

        if (self::isExemptRoute($controller, $url)) {
            return;
        }

        if (!self::shouldBlock($controller, $url)) {
            return;
        }

        self::redirectBlocked();
    }

    public static function requireCanSubmitPropertyRequest(): void
    {
        if (!ClassAuth::check()) {
            return;
        }

        if (!ClassAccess::canSubmitPropertyRequest()) {
            $user = ClassAuth::user();
            if (ClassAccess::hasLimitedPlatformAccess($user)) {
                ClassLimitedAccountGuard::redirectLimited('Quando a sua conta estiver activa, pode pedir visita ou reservar este imóvel.');
            }
            header('Location: ' . DIRPAGE . 'dashboard?error=' . rawurlencode('Contas da equipa não podem enviar pedidos de compra ou aluguer. Utilize o painel de solicitações para acompanhar negócios.'));
            exit;
        }

        if (!self::currentUserHasBlockingOverdue()) {
            return;
        }

        self::redirectBlocked();
    }

    private static function redirectBlocked(): void
    {
        header('Location: ' . DIRPAGE . 'dashboard/commissionPayments');
        exit;
    }

    private static function isExemptRoute(string $controller, array $url): bool
    {
        $routeKey = strtolower((string) ($url[0] ?? ''));

        if (in_array($routeKey, self::EXEMPT_ROUTE_KEYS, true)) {
            return true;
        }

        if ($routeKey !== 'dashboard' && $controller !== 'ControllerDashboard') {
            return false;
        }

        $method = self::resolveUrlMethod($url[1] ?? '');

        return in_array($method, self::EXEMPT_DASHBOARD_METHODS, true);
    }

    private static function shouldBlock(string $controller, array $url): bool
    {
        $routeKey = strtolower((string) ($url[0] ?? ''));

        if ($routeKey === 'request') {
            return true;
        }

        if (in_array($routeKey, self::BLOCKED_ROUTE_KEYS, true)) {
            return true;
        }

        if ($routeKey === 'dashboard' || $controller === 'ControllerDashboard') {
            return true;
        }

        if ($controller === 'ControllerRequest') {
            return true;
        }

        if ($controller === 'ControllerPayment') {
            return true;
        }

        if ($controller === 'ControllerNotification') {
            return true;
        }

        if ($controller === 'ControllerApi') {
            return true;
        }

        if ($controller === 'ControllerProperty') {
            return self::shouldBlockPropertyRoute($url);
        }

        return false;
    }

    private static function shouldBlockPropertyRoute(array $url): bool
    {
        $routeKey = strtolower((string) ($url[0] ?? ''));
        if ($routeKey !== 'property' && $routeKey !== 'properties' && $routeKey !== 'featured' && $routeKey !== 'agency') {
            return true;
        }

        if ($routeKey === 'properties' || $routeKey === 'featured') {
            return false;
        }

        if ($routeKey === 'agency') {
            $method = self::resolveUrlMethod($url[1] ?? '');
            return $method !== '' && $method !== 'show' && !is_numeric((string) ($url[1] ?? ''));
        }

        $segment = (string) ($url[1] ?? '');
        if ($segment === '') {
            return false;
        }

        if (is_numeric($segment)) {
            return false;
        }

        $method = self::resolveUrlMethod($segment);

        return !in_array($method, self::PROPERTY_READ_METHODS, true);
    }

    private static function resolveUrlMethod(string $segment): string
    {
        $segment = trim($segment);
        if ($segment === '') {
            return '';
        }

        if (strpos($segment, '_') !== false) {
            return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $segment))));
        }

        return $segment;
    }
}
