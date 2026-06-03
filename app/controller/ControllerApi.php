<?php

namespace App\controller;

use App\model\ApiToken;
use App\model\Log;
use App\model\Notification;
use App\model\Property;
use Src\classes\ClassRateLimiter;
use Src\traits\TraitUrlParser;

class ControllerApi
{
    use TraitUrlParser;

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

    public function index(): void
    {
        ClassRateLimiter::enforceScopeAllMethods('api_index', 'rate_limit_api_max', 'api_rate_limit_window_seconds', 120, 60);

        $this->respond(true, [
            'name' => 'Imobil API',
            'routes' => [
                '/api/health',
                '/api/v1',
                '/api/v1/properties',
                '/api/v1/property/{id}',
            ],
        ]);
    }

    public function health(): void
    {
        ClassRateLimiter::enforceScopeAllMethods('api_health', 'rate_limit_api_max', 'api_rate_limit_window_seconds', 120, 60);

        $this->respond(true, [
            'status' => 'ok',
            'service' => 'imobil-api',
            'environment' => defined('APP_ENV') ? APP_ENV : 'unknown',
        ]);
    }

    public function v1(): void
    {
        $apiToken = $this->authenticate();
        $segments = $this->parseUrl();
        $subPath = $segments[2] ?? null;
        $resource = $segments[3] ?? null;

        $owner = 'token:' . substr((string) ($apiToken['token'] ?? ''), 0, 8);
        $this->enforceApiRateLimit('v1', $owner);

        if ($subPath === null) {
            $this->logApiRequest($apiToken, 'api.root', 200, 'API v1 root accessed');
            $this->respond(true, [
                'version' => 'v1',
                'resources' => ['/api/v1/properties', '/api/v1/property/{id}'],
            ]);
        }

        if ($subPath === 'properties') {
            $this->assertScope($apiToken, 'read:properties');
            $this->properties($apiToken);
        }

        if ($subPath === 'property' && $resource !== null && is_numeric($resource)) {
            $this->assertScope($apiToken, 'read:properties');
            $this->property((int) $resource, $apiToken);
        }

        if ($subPath === 'tokens') {
            // Token management endpoints: require manage:tokens scope
            $this->assertScope($apiToken, 'manage:tokens');

            // /api/v1/tokens -> list (GET) or create (POST)
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->tokensList($apiToken);
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->tokensCreate($apiToken);
            }

            // /api/v1/tokens/{id} -> revoke (DELETE)
            if ($resource !== null && is_numeric($resource) && strtoupper($_SERVER['REQUEST_METHOD']) === 'DELETE') {
                $this->tokensRevoke((int) $resource, $apiToken);
            }
        }

        if ($subPath === 'saved_searches') {
            // user-specific saved searches
            $userId = (int) ($apiToken['user_id'] ?? 0);
            if ($userId <= 0) {
                $this->respond(false, null, 'User-bound tokens required', 403);
            }

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $items = \App\model\SavedSearch::getByUser($userId);
                $this->logApiRequest($apiToken, 'api.saved_searches.list', 200, 'Saved searches listed');
                $this->respond(true, ['saved_searches' => $items]);
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true) ?: [];
                $name = trim((string) ($input['name'] ?? 'Saved Search'));
                $filters = (array) ($input['filters'] ?? []);
                $ok = \App\model\SavedSearch::createForUser($userId, $name, $filters);
                if (!$ok) {
                    $this->logApiRequest($apiToken, 'api.saved_searches.create', 500, 'Failed creating saved search');
                    $this->respond(false, null, 'Failed to create saved search', 500);
                }
                $this->logApiRequest($apiToken, 'api.saved_searches.create', 201, 'Saved search created');
                $this->respond(true, ['created' => true], null, 201);
            }

            if ($resource !== null && is_numeric($resource) && strtoupper($_SERVER['REQUEST_METHOD']) === 'DELETE') {
                $id = (int) $resource;
                $ok = \App\model\SavedSearch::deleteById($id, $userId);
                if (!$ok) {
                    $this->logApiRequest($apiToken, 'api.saved_searches.delete', 500, 'Failed deleting saved search');
                    $this->respond(false, null, 'Failed to delete saved search', 500);
                }
                $this->logApiRequest($apiToken, 'api.saved_searches.delete', 200, 'Saved search deleted');
                $this->respond(true, ['deleted' => true]);
            }
        }

        if ($subPath === 'notifications' && $resource === 'archive') {
            $userId = (int) ($apiToken['user_id'] ?? 0);
            if ($userId <= 0) {
                $this->respond(false, null, 'User-bound tokens required', 403);
            }

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->notificationsArchive($apiToken, $userId);
                return;
            }
        }

        if ($subPath === 'notifications' && $resource === null) {
            $userId = (int) ($apiToken['user_id'] ?? 0);
            if ($userId <= 0) {
                $this->respond(false, null, 'User-bound tokens required', 403);
            }

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->notificationsInbox($apiToken, $userId);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true) ?: [];
                $action = (string) ($input['action'] ?? 'mark_as_read');
                $notificationId = (int) ($input['notification_id'] ?? 0);

                if ($action === 'mark_as_read' && $notificationId > 0) {
                    Notification::markAsReadByUser($notificationId, $userId);
                    $this->logApiRequest($apiToken, 'api.notifications.mark_as_read', 200, 'Marked as read');
                    $this->respond(true, ['marked' => true]);
                }

                if ($action === 'mark_all_as_read') {
                    Notification::markAllAsReadByUser($userId);
                    $this->logApiRequest($apiToken, 'api.notifications.mark_all_as_read', 200, 'Marked all as read');
                    $this->respond(true, ['marked_all' => true]);
                }

                if ($action === 'archive' && $notificationId > 0) {
                    Notification::archiveByUser($notificationId, $userId);
                    $this->logApiRequest($apiToken, 'api.notifications.archive', 200, 'Archived');
                    $this->respond(true, ['archived' => true]);
                }

                if ($action === 'archive_all') {
                    Notification::archiveAllByUser($userId);
                    $this->logApiRequest($apiToken, 'api.notifications.archive_all', 200, 'Archived all');
                    $this->respond(true, ['archived_all' => true]);
                }

                if ($action === 'unarchive' && $notificationId > 0) {
                    Notification::unarchiveByUser($notificationId, $userId);
                    $this->logApiRequest($apiToken, 'api.notifications.unarchive', 200, 'Unarchived');
                    $this->respond(true, ['unarchived' => true]);
                }

                $this->respond(false, null, 'Invalid action', 400);
            }
        }

        if ($subPath === 'notifications' && $resource !== null && is_numeric($resource) && strtoupper($_SERVER['REQUEST_METHOD']) === 'DELETE') {
            $userId = (int) ($apiToken['user_id'] ?? 0);
            if ($userId <= 0) {
                $this->respond(false, null, 'User-bound tokens required', 403);
            }

            $notificationId = (int) $resource;
            $ok = Notification::deleteArchivedByUser($notificationId, $userId);
            if (!$ok) {
                $this->logApiRequest($apiToken, 'api.notifications.delete', 404, 'Notification not found or not archived');
                $this->respond(false, null, 'Notification not found or not archived', 404);
            }

            $this->logApiRequest($apiToken, 'api.notifications.delete', 200, 'Archived notification deleted');
            $this->respond(true, ['deleted' => true]);
        }

        $this->logApiRequest($apiToken, 'api.unknown', 404, 'Resource not found');
        $this->respond(false, null, 'Resource not found', 404);
    }

    private function properties(array $apiToken): void
    {
        $cursorRaw = isset($_GET['cursor']) ? (string) $_GET['cursor'] : '';
        $cursor = $this->decodeCursor($cursorRaw);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int) ($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $filters = [];

        foreach (['type', 'purpose', 'min_price', 'max_price', 'location', 'country_id', 'region_id', 'keyword'] as $field) {
            if (isset($_GET[$field]) && $_GET[$field] !== '') {
                $filters[$field] = $_GET[$field];
            }
        }

        if ($cursor) {
            $properties = Property::getFilteredCursor(
                $filters,
                $limit,
                isset($cursor['created_at']) ? (string) $cursor['created_at'] : null,
                isset($cursor['id']) ? (int) $cursor['id'] : null
            );
            $total = null;
        } else {
            $properties = Property::getFiltered($filters, $limit, $offset);
            $total = Property::countFiltered($filters);
        }

        $nextCursor = null;
        if (!empty($properties)) {
            $last = $properties[count($properties) - 1];
            $nextCursor = $this->encodeCursor($last['created_at'] ?? null, isset($last['id']) ? (int) $last['id'] : null);
        }

        $this->logApiRequest($apiToken, 'api.properties.list', 200, 'Properties list retrieved');
        $this->respond(true, [
            'page' => $cursor ? null : $page,
            'per_page' => $limit,
            'total' => $total,
            'cursor' => $cursorRaw !== '' ? $cursorRaw : null,
            'next_cursor' => $nextCursor,
            'properties' => $properties,
        ]);
    }

    private function property(int $id, array $apiToken): void
    {
        $property = Property::find($id);
        if (!$property || ($property['status'] ?? '') !== 'disponivel') {
            $this->logApiRequest($apiToken, 'api.property.detail', 404, 'Property not found');
            $this->respond(false, null, 'Property not found', 404);
        }

        $this->logApiRequest($apiToken, 'api.property.detail', 200, 'Property details retrieved');
        $this->respond(true, ['property' => $property]);
    }

    /* Token management */
    private function tokensList(array $apiToken): void
    {
        $userId = (int) ($apiToken['user_id'] ?? 0);
        $tokens = [];
        if ($userId > 0) {
            $tokens = \App\model\ApiToken::getByUser($userId);
        }
        $this->logApiRequest($apiToken, 'api.tokens.list', 200, 'Token list retrieved');
        $this->respond(true, ['tokens' => $tokens]);
    }

    private function tokensCreate(array $apiToken): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $name = trim((string) ($input['name'] ?? 'API Token'));
        $scopes = trim((string) ($input['scopes'] ?? 'read:properties'));
        $expiresAt = isset($input['expires_at']) ? trim((string) $input['expires_at']) : null;

        $userId = (int) ($apiToken['user_id'] ?? 0);
        $newToken = \App\model\ApiToken::createToken($userId, $name, $scopes, $expiresAt);
        if (!$newToken) {
            $this->logApiRequest($apiToken, 'api.tokens.create', 500, 'Failed creating token');
            $this->respond(false, null, 'Failed to create token', 500);
        }

        $this->logApiRequest($apiToken, 'api.tokens.create', 201, 'Token created');
        $this->respond(true, ['token' => $newToken], null, 201);
    }

    private function tokensRevoke(int $id, array $apiToken): void
    {
        $ok = \App\model\ApiToken::revoke($id);
        if (!$ok) {
            $this->logApiRequest($apiToken, 'api.tokens.revoke', 500, 'Failed to revoke token');
            $this->respond(false, null, 'Failed to revoke token', 500);
        }

        $this->logApiRequest($apiToken, 'api.tokens.revoke', 200, 'Token revoked');
        $this->respond(true, ['revoked' => true]);
    }

    private function notificationsInbox(array $apiToken, int $userId): void
    {
        $cursorRaw = isset($_GET['cursor']) ? (string) $_GET['cursor'] : '';
        $cursor = $this->decodeCursor($cursorRaw);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(20, max(1, (int) ($_GET['per_page'] ?? 10)));
        $offset = ($page - 1) * $limit;

        if ($cursor) {
            $notifications = Notification::getInboxByUserCursor(
                $userId,
                $limit,
                isset($cursor['created_at']) ? (string) $cursor['created_at'] : null,
                isset($cursor['id']) ? (int) $cursor['id'] : null
            );
            $total = null;
        } else {
            $notifications = Notification::getInboxByUser($userId, $limit, $offset);
            $total = Notification::countInboxByUser($userId);
        }
        $unread = Notification::countUnreadByUser($userId);

        $nextCursor = null;
        if (!empty($notifications)) {
            $last = $notifications[count($notifications) - 1];
            $nextCursor = $this->encodeCursor($last['created_at'] ?? null, isset($last['id']) ? (int) $last['id'] : null);
        }

        $this->logApiRequest($apiToken, 'api.notifications.inbox', 200, 'Inbox retrieved');
        $this->respond(true, [
            'page' => $cursor ? null : $page,
            'per_page' => $limit,
            'total' => $total,
            'unread' => $unread,
            'cursor' => $cursorRaw !== '' ? $cursorRaw : null,
            'next_cursor' => $nextCursor,
            'notifications' => $notifications,
        ]);
    }

    private function notificationsArchive(array $apiToken, int $userId): void
    {
        $cursorRaw = isset($_GET['cursor']) ? (string) $_GET['cursor'] : '';
        $cursor = $this->decodeCursor($cursorRaw);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(30, max(1, (int) ($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $typeFilter = trim((string) ($_GET['type'] ?? ''));
        $typeFilter = $typeFilter === '' ? null : $typeFilter;

        if ($cursor) {
            $notifications = Notification::getArchiveByUserCursor(
                $userId,
                $limit,
                isset($cursor['created_at']) ? (string) $cursor['created_at'] : null,
                isset($cursor['id']) ? (int) $cursor['id'] : null,
                $typeFilter
            );
            $total = null;
        } else {
            $notifications = Notification::getArchiveByUser($userId, $limit, $offset, $typeFilter);
            $total = Notification::countArchiveByUser($userId, $typeFilter);
        }

        $nextCursor = null;
        if (!empty($notifications)) {
            $last = $notifications[count($notifications) - 1];
            $nextCursor = $this->encodeCursor($last['created_at'] ?? null, isset($last['id']) ? (int) $last['id'] : null);
        }

        $this->logApiRequest($apiToken, 'api.notifications.archive', 200, 'Archive retrieved');
        $this->respond(true, [
            'page' => $cursor ? null : $page,
            'per_page' => $limit,
            'total' => $total,
            'cursor' => $cursorRaw !== '' ? $cursorRaw : null,
            'next_cursor' => $nextCursor,
            'notifications' => $notifications,
        ]);
    }
}
