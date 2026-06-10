<?php

namespace App\controller;

use App\model\ApiToken;

class ControllerApiV1Tokens
{
    use ApiControllerSupport;

    public function list(): void
    {
        $apiToken = $this->beginV1Request();
        $this->assertScope($apiToken, 'manage:tokens');

        $userId = (int) ($apiToken['user_id'] ?? 0);
        $tokens = $userId > 0 ? ApiToken::getByUser($userId) : [];

        $this->logApiRequest($apiToken, 'api.tokens.list', 200, 'Token list retrieved');
        $this->respond(true, ['tokens' => $tokens]);
    }

    public function create(): void
    {
        $apiToken = $this->beginV1Request();
        $this->assertScope($apiToken, 'manage:tokens');

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $name = trim((string) ($input['name'] ?? 'API Token'));
        $scopes = trim((string) ($input['scopes'] ?? 'read:properties'));
        $expiresAt = isset($input['expires_at']) ? trim((string) $input['expires_at']) : null;

        $userId = (int) ($apiToken['user_id'] ?? 0);
        $newToken = ApiToken::createToken($userId, $name, $scopes, $expiresAt);
        if (!$newToken) {
            $this->logApiRequest($apiToken, 'api.tokens.create', 500, 'Failed creating token');
            $this->respond(false, null, 'Failed to create token', 500);
        }

        $this->logApiRequest($apiToken, 'api.tokens.create', 201, 'Token created');
        $this->respond(true, ['token' => $newToken], null, 201);
    }

    public function revoke($id): void
    {
        $apiToken = $this->beginV1Request();
        $this->assertScope($apiToken, 'manage:tokens');

        $ok = ApiToken::revoke((int) $id);
        if (!$ok) {
            $this->logApiRequest($apiToken, 'api.tokens.revoke', 500, 'Failed to revoke token');
            $this->respond(false, null, 'Failed to revoke token', 500);
        }

        $this->logApiRequest($apiToken, 'api.tokens.revoke', 200, 'Token revoked');
        $this->respond(true, ['revoked' => true]);
    }
}
