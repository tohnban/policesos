<?php
namespace Src\classes;

class ClassRateLimiter {
    private const DEFAULT_MAX_REQUESTS = 60;
    private const DEFAULT_WINDOW_SECONDS = 60;

    public static function enforceGlobalPost(): void {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return;
        }

        $ip = self::getClientIp();
        $route = self::getRouteKey();

        $maxRequests = max(1, ClassSettings::int('rate_limit_post_max', self::DEFAULT_MAX_REQUESTS));
        $windowSecs  = max(1, ClassSettings::int('rate_limit_post_window_seconds', self::DEFAULT_WINDOW_SECONDS));

        if (!self::consume($ip, $route, $maxRequests, $windowSecs)) {
            self::deny($windowSecs);
        }
    }

    public static function enforceScope(
        string $scope,
        string $maxRequestsSettingKey,
        string $windowSecondsSettingKey,
        int $defaultMaxRequests,
        int $defaultWindowSeconds
    ): void {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return;
        }

        $ip = self::getClientIp();
        $route = self::getRouteKey() . '|scope:' . trim($scope);

        $maxRequests = max(1, ClassSettings::int($maxRequestsSettingKey, $defaultMaxRequests));
        $windowSecs  = max(1, ClassSettings::int($windowSecondsSettingKey, $defaultWindowSeconds));

        if (!self::consume($ip, $route, $maxRequests, $windowSecs)) {
            self::deny($windowSecs);
        }
    }

    public static function enforceScopeAllMethods(
        string $scope,
        string $maxRequestsSettingKey,
        string $windowSecondsSettingKey,
        int $defaultMaxRequests,
        int $defaultWindowSeconds
    ): void {
        $ip = self::getClientIp();
        $route = self::getRouteKey() . '|scope:' . trim($scope);

        $maxRequests = max(1, ClassSettings::int($maxRequestsSettingKey, $defaultMaxRequests));
        $windowSecs  = max(1, ClassSettings::int($windowSecondsSettingKey, $defaultWindowSeconds));

        if (!self::consume($ip, $route, $maxRequests, $windowSecs)) {
            self::deny($windowSecs);
        }
    }

    private static function deny(int $windowSecs): void {
        http_response_code(429);
        header('Retry-After: ' . $windowSecs);
        echo 'Muitas requisições. Tente novamente em instantes.';
        exit;
    }

    private static function consume(string $ip, string $route, int $maxRequests, int $windowSecs): bool {
        $dir = rtrim(DIRREQ, '/\\') . '/storage/cache/rate_limits';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            // Fail-open if cache folder is unavailable to avoid hard downtime.
            return true;
        }

        $key = sha1($ip . '|' . $route);
        $file = $dir . '/' . $key . '.json';
        $now = time();

        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            return true;
        }

        try {
            if (!flock($fp, LOCK_EX)) {
                fclose($fp);
                return true;
            }

            $raw = stream_get_contents($fp);
            $state = json_decode((string) $raw, true);
            if (!is_array($state)) {
                $state = ['window_start' => $now, 'count' => 0];
            }

            $windowStart = (int) ($state['window_start'] ?? $now);
            $count = (int) ($state['count'] ?? 0);

            if (($now - $windowStart) >= $windowSecs) {
                $windowStart = $now;
                $count = 0;
            }

            $count++;
            $allowed = $count <= $maxRequests;

            $nextState = json_encode([
                'window_start' => $windowStart,
                'count' => $count,
            ]);

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $nextState !== false ? $nextState : '{"window_start":0,"count":0}');
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            return $allowed;
        } catch (\Throwable $e) {
            @flock($fp, LOCK_UN);
            @fclose($fp);
            return true;
        }
    }

    private static function getClientIp(): string {
        $candidates = [
            (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''),
            (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }
            $parts = explode(',', $candidate);
            $ip = trim($parts[0]);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    private static function getRouteKey(): string {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '/';
        }

        return $path;
    }
}
