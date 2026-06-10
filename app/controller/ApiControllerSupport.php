<?php

namespace App\controller;

use App\model\ApiToken;
use App\model\Log;
use Src\classes\ClassRateLimiter;

trait ApiControllerSupport
{
    private function respond(bool $success, $data = null, ?string $error = null, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => $success,
            'data' => $data,
            'error' => $error,
            'timestamp' => date('c'),
            'version' => 'v1',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function getBearerToken(): ?string
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders() ?: [];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers() ?: [];
        }

        $authHeader = null;
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        } elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if ($authHeader === null) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', trim($authHeader), $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function authenticate(): ?array
    {
        $token = $this->getBearerToken();
        if ($token === null) {
            $this->respond(false, null, 'Authorization token missing', 401);
        }

        $apiToken = ApiToken::validateToken($token);
        if (!$apiToken) {
            $this->respond(false, null, 'Invalid or expired API token', 401);
        }

        ApiToken::markUsed((int) $apiToken['id']);
        return $apiToken;
    }

    private function assertScope(array $apiToken, string $requiredScope): void
    {
        if (!ApiToken::hasScope($apiToken, $requiredScope)) {
            $this->respond(false, null, 'Insufficient API token scope', 403);
        }
    }

    private function logApiRequest(array $apiToken, string $action, int $statusCode, ?string $details = null): void
    {
        Log::create([
            'user_id' => $apiToken['user_id'] ?? null,
            'action' => $action,
            'entity_type' => 'api_token',
            'entity_id' => $apiToken['id'] ?? null,
            'details' => json_encode([
                'route' => $_SERVER['REQUEST_URI'] ?? '',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                'status' => $statusCode,
                'message' => $details,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function enforceApiRateLimit(string $route, string $owner = 'anonymous'): void
    {
        $key = 'api:' . $route . '|' . $owner;
        ClassRateLimiter::enforceScopeAllMethods($key, 'api_rate_limit_max', 'api_rate_limit_window_seconds', 300, 60);
    }

    private function beginV1Request(string $route = 'v1'): array
    {
        $apiToken = $this->authenticate();
        $owner = 'token:' . substr((string) ($apiToken['token'] ?? ''), 0, 8);
        $this->enforceApiRateLimit($route, $owner);

        return $apiToken;
    }

    private function decodeCursor(?string $cursor): ?array
    {
        $cursor = trim((string) $cursor);
        if ($cursor === '') {
            return null;
        }

        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);
        if ($decoded === false || $decoded === '') {
            return null;
        }

        $data = json_decode($decoded, true);
        return is_array($data) ? $data : null;
    }

    private function encodeCursor(?string $createdAt, ?int $id): ?string
    {
        $createdAt = $createdAt !== null ? trim((string) $createdAt) : '';
        $id = (int) ($id ?? 0);
        if ($createdAt === '' || $id <= 0) {
            return null;
        }
        $payload = json_encode(['created_at' => $createdAt, 'id' => $id], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $b64 = base64_encode((string) $payload);
        return rtrim(strtr($b64, '+/', '-_'), '=');
    }
}
