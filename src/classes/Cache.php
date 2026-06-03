<?php

namespace Src\classes;

class Cache
{
    private static $redis = null;

    private static function redis(): ?\Redis
    {
        if (self::$redis !== null) {
            return self::$redis;
        }
        if (!extension_loaded('redis')) {
            self::$redis = null;
            return null;
        }

        try {
            $r = new \Redis();
            $host = defined('REDIS_HOST') ? REDIS_HOST : '127.0.0.1';
            $port = defined('REDIS_PORT') ? (int) REDIS_PORT : 6379;
            $timeout = 1.0;
            $r->connect($host, $port, $timeout);
            self::$redis = $r;
            return self::$redis;
        } catch (\Throwable $e) {
            self::$redis = null;
            return null;
        }
    }

    public static function get(string $key)
    {
        $r = self::redis();
        if ($r) {
            $v = $r->get($key);
            if ($v === false) {
                return null;
            }
            return unserialize($v);
        }

        // fallback: file cache
        $dir = rtrim(DIRREQ, '/\\') . '/storage/cache/';
        $file = $dir . 'cache_' . sha1($key) . '.php';
        if (!is_file($file)) {
            return null;
        }
        $data = @file_get_contents($file);
        if ($data === false) {
            return null;
        }
        $payload = @unserialize($data);
        if (!is_array($payload) || !isset($payload['expires']) || $payload['expires'] < time()) {
            @unlink($file);
            return null;
        }
        return $payload['value'];
    }

    public static function set(string $key, $value, int $ttlSeconds = 3600): bool
    {
        $r = self::redis();
        if ($r) {
            return $r->setex($key, max(1, $ttlSeconds), serialize($value));
        }

        $dir = rtrim(DIRREQ, '/\\') . '/storage/cache/';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }
        $file = $dir . 'cache_' . sha1($key) . '.php';
        $payload = ['expires' => time() + max(1, $ttlSeconds), 'value' => $value];
        return (bool) @file_put_contents($file, serialize($payload));
    }

    public static function remember(string $key, int $ttlSeconds, callable $callback)
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }
        $value = $callback();
        try {
            self::set($key, $value, $ttlSeconds);
        } catch (\Throwable $_) {
        }
        return $value;
    }

    public static function delete(string $key): bool
    {
        $r = self::redis();
        if ($r) {
            return (bool) $r->del($key);
        }

        $dir = rtrim(DIRREQ, '/\\') . '/storage/cache/';
        $file = $dir . 'cache_' . sha1($key) . '.php';
        if (is_file($file)) {
            return @unlink($file);
        }
        return false;
    }

    public static function increment(string $key, int $step = 1): int
    {
        $r = self::redis();
        if ($r) {
            return (int) $r->incrBy($key, max(1, $step));
        }

        $dir = rtrim(DIRREQ, '/\\') . '/storage/cache/';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return 0;
        }
        $file = $dir . 'cache_' . sha1($key) . '.php';
        $value = 0;
        if (is_file($file)) {
            $data = @file_get_contents($file);
            $payload = @unserialize($data);
            if (is_array($payload) && isset($payload['value']) && is_numeric($payload['value'])) {
                $value = (int) $payload['value'];
            }
        }
        $value += max(1, $step);
        $payload = ['expires' => time() + 31536000, 'value' => $value];
        @file_put_contents($file, serialize($payload));
        return $value;
    }
}
