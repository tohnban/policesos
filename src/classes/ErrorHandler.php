<?php

namespace Src\classes;

final class ErrorHandler
{
    private static ?string $requestId = null;

    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;
        self::$requestId = self::generateRequestId();

        if (!headers_sent()) {
            header('X-Request-Id: ' . self::$requestId, false);
        }

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function requestId(): string
    {
        return self::$requestId ?? '';
    }

    public static function handleException(\Throwable $exception): void
    {
        self::reportThrowable($exception, false);

        if (!headers_sent()) {
            http_response_code(500);
        }

        self::renderFailureResponse($exception);
        exit(1);
    }

    public static function handleError(int $severity, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array((int) ($error['type'] ?? 0), $fatalTypes, true)) {
            return;
        }

        $exception = new \ErrorException(
            (string) ($error['message'] ?? 'Fatal error'),
            0,
            (int) ($error['type'] ?? E_ERROR),
            (string) ($error['file'] ?? ''),
            (int) ($error['line'] ?? 0)
        );

        self::reportThrowable($exception, true);

        if (!headers_sent()) {
            http_response_code(500);
        }

        self::renderFailureResponse($exception);
    }

    public static function reportThrowable(\Throwable $exception, bool $isShutdown): void
    {
        TechnicalLogger::error($isShutdown ? 'Fatal shutdown error' : 'Unhandled exception', [
            'exception' => $exception,
            'shutdown' => $isShutdown,
        ]);
    }

    private static function renderFailureResponse(\Throwable $exception): void
    {
        $debug = defined('APP_DEBUG') && APP_DEBUG;
        $isApi = self::isApiRequest();

        if ($isApi) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }

            $payload = [
                'success' => false,
                'error' => $debug ? $exception->getMessage() : 'Erro interno do servidor.',
                'request_id' => self::requestId(),
            ];
            if ($debug) {
                $payload['exception'] = get_class($exception);
                $payload['file'] = $exception->getFile();
                $payload['line'] = $exception->getLine();
            }

            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        if ($debug) {
            echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="utf-8"><title>Erro</title></head><body>';
            echo '<h1>Erro na aplicação</h1>';
            echo '<p><strong>Request ID:</strong> ' . htmlspecialchars(self::requestId(), ENT_QUOTES, 'UTF-8') . '</p>';
            echo '<pre style="white-space:pre-wrap;">' . htmlspecialchars((string) $exception, ENT_QUOTES, 'UTF-8') . '</pre>';
            echo '</body></html>';
            return;
        }

        echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="utf-8"><title>Erro interno</title></head><body>';
        echo '<h1>Algo correu mal</h1>';
        echo '<p>Não foi possível concluir o pedido. Tente novamente mais tarde.</p>';
        echo '<p><small>Referência: ' . htmlspecialchars(self::requestId(), ENT_QUOTES, 'UTF-8') . '</small></p>';
        echo '</body></html>';
    }

    private static function isApiRequest(): bool
    {
        $url = trim((string) ($_GET['url'] ?? ''), '/');
        if ($url === '') {
            return false;
        }

        return stripos($url, 'api/') === 0 || strtolower($url) === 'api';
    }

    private static function generateRequestId(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Throwable $e) {
            return sha1(uniqid('req_', true));
        }
    }
}
