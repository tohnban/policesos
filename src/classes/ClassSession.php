<?php
namespace Src\classes;

class ClassSession {
    /**
     * True when the current request is served over HTTPS (direct or via proxy).
     */
    public static function isHttpsRequest(): bool {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            // Secure cookies only over HTTPS; HTTP LAN access (e.g. 192.168.x.x) must still keep sessions.
            ini_set('session.cookie_secure', self::isHttpsRequest() ? '1' : '0');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
            session_start();
        }
    }

    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove($key) {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy() {
        self::start();
        session_destroy();
        $_SESSION = [];
    }

    public static function regenerate() {
        self::start();
        session_regenerate_id(true);
    }

    public static function isActive() {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public static function getDiscoverySeed(): string {
        self::start();
        $existing = (string) ($_SESSION['discovery_seed'] ?? '');
        if ($existing !== '') {
            return $existing;
        }

        try {
            $seed = bin2hex(random_bytes(8));
        } catch (\Throwable $e) {
            $seed = sha1(uniqid('discovery_', true) . microtime(true));
        }

        $_SESSION['discovery_seed'] = $seed;
        return $seed;
    }

    public static function getBehaviorProfileVersion(): int {
        self::start();
        return (int) ($_SESSION['behavior_profile_version'] ?? 0);
    }

    public static function bumpBehaviorProfileVersion(): int {
        self::start();
        $next = self::getBehaviorProfileVersion() + 1;
        $_SESSION['behavior_profile_version'] = $next;
        return $next;
    }

    public static function getOrCreateVisitorKey(): string {
        self::start();

        $existing = (string) ($_SESSION['visitor_key'] ?? '');
        if ($existing !== '') {
            return $existing;
        }

        try {
            $key = bin2hex(random_bytes(16));
        } catch (\Throwable $e) {
            $key = sha1(uniqid('visitor_', true) . microtime(true));
        }

        $_SESSION['visitor_key'] = $key;
        return $key;
    }
}