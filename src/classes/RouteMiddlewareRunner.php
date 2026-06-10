<?php

namespace Src\classes;

final class RouteMiddlewareRunner
{
    /**
     * @param array<int, string> $middlewares
     */
    public static function run(array $middlewares, ResolvedRoute $route): void
    {
        foreach ($middlewares as $spec) {
            self::apply(trim((string) $spec), $route);
        }
    }

    private static function apply(string $spec, ResolvedRoute $route): void
    {
        if ($spec === '' || $spec === 'web') {
            return;
        }

        if ($spec === 'auth') {
            ClassAuth::requireAuth();
            return;
        }

        if ($spec === 'csrf') {
            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
                return;
            }
            if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
                $fallback = $route->path !== ''
                    ? $route->path
                    : ($route->routeKey !== '' ? $route->routeKey : 'dashboard');
                ClassCsrf::rejectPost($fallback);
            }
            return;
        }

        if (strpos($spec, 'can:') === 0) {
            $permission = trim(substr($spec, 4));
            if ($permission !== '') {
                ClassAccess::requirePermission($permission);
            }
            return;
        }

        if ($spec === 'super_admin') {
            ClassAccess::requireSuperAdmin();
            return;
        }

        if ($spec === 'admin') {
            ClassAccess::requireAdmin();
        }
    }
}
