<?php

namespace Src\classes;

class PageCache
{
    public static function isEnabled(): bool
    {
        return ClassSettings::int('page_cache_enabled', 1) === 1;
    }

    public static function capture(string $namespace, int $ttlSeconds, callable $callback): string
    {
        if (
            !self::isEnabled()
            || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || ClassAuth::check()
        ) {
            // Never cache HTML for authenticated users: responses embed CSRF tokens
            // and user-specific UI (favorites, notifications, etc.).
            return $callback();
        }

        $cacheKey = self::buildKey($namespace);
        return Cache::remember($cacheKey, $ttlSeconds, $callback);
    }

    /**
     * Clears file-based page cache entries (e.g. after deploy or CSRF policy change).
     */
    public static function flush(): void
    {
        $dir = rtrim(DIRREQ, '/\\') . '/storage/cache/';
        foreach (glob($dir . 'cache_*.php') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    public static function invalidate(string $namespace): void
    {
        Cache::delete(self::buildKey($namespace));
    }

    private static function buildKey(string $namespace): string
    {
        $uri = ($_SERVER['REQUEST_URI'] ?? '/');
        $auth = ClassAuth::check() ? 'auth:' . (int) ClassAuth::user()['id'] : 'guest';
        return sprintf('page:%s:%s:%s', $namespace, $auth, sha1($uri));
    }
}
