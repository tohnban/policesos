<?php

namespace Src\classes;

final class TechnicalLogger
{
    private const DEFAULT_CHANNEL = 'file';

    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        if (!self::shouldLog($level)) {
            return;
        }

        $channel = strtolower((string) (defined('LOG_CHANNEL') ? LOG_CHANNEL : self::DEFAULT_CHANNEL));
        if ($channel === 'file' || $channel === '') {
            self::writeFile($level, $message, $context);
        }
    }

    private static function shouldLog(string $level): bool
    {
        $configured = strtolower((string) (defined('LOG_LEVEL') ? LOG_LEVEL : 'info'));
        $priorities = [
            'debug' => 10,
            'info' => 20,
            'notice' => 25,
            'warning' => 30,
            'error' => 40,
            'critical' => 50,
            'alert' => 60,
            'emergency' => 70,
        ];

        $levelPriority = $priorities[strtolower($level)] ?? 20;
        $configuredPriority = $priorities[$configured] ?? 20;

        return $levelPriority >= $configuredPriority;
    }

    private static function writeFile(string $level, string $message, array $context): void
    {
        $dir = rtrim(DIRREQ, '/\\') . '/storage/logs';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            error_log('[imobil] unable to create log directory: ' . $dir);
            return;
        }

        $file = $dir . '/app.log';
        $requestId = ErrorHandler::requestId();
        $payload = [
            'timestamp' => date('c'),
            'level' => strtolower($level),
            'message' => $message,
            'request_id' => $requestId !== '' ? $requestId : null,
            'context' => self::sanitizeContext($context),
        ];

        if (!empty($_SERVER['REQUEST_METHOD'])) {
            $payload['http_method'] = (string) $_SERVER['REQUEST_METHOD'];
        }
        if (!empty($_SERVER['REQUEST_URI'])) {
            $payload['request_uri'] = (string) $_SERVER['REQUEST_URI'];
        }

        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            $line = '{"timestamp":"' . date('c') . '","level":"error","message":"log encoding failed"}';
        }

        @file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private static function sanitizeContext(array $context): array
    {
        $sanitized = [];
        foreach ($context as $key => $value) {
            $key = (string) $key;
            if (in_array(strtolower($key), ['password', 'pass', 'token', 'csrf_token', 'secret'], true)) {
                $sanitized[$key] = '[redacted]';
                continue;
            }
            if ($value instanceof \Throwable) {
                $sanitized[$key] = [
                    'class' => get_class($value),
                    'message' => $value->getMessage(),
                    'file' => $value->getFile(),
                    'line' => $value->getLine(),
                ];
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $sanitized[$key] = $value;
                continue;
            }
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeContext($value);
                continue;
            }
            $sanitized[$key] = is_object($value) ? get_class($value) : (string) $value;
        }
        return $sanitized;
    }
}
