<?php

namespace App\controller;

use App\model\SavedSearch;

class ControllerApiV1SavedSearches
{
    use ApiControllerSupport;

    private function requireUserId(array $apiToken): int
    {
        $userId = (int) ($apiToken['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->respond(false, null, 'User-bound tokens required', 403);
        }

        return $userId;
    }

    public function list(): void
    {
        $apiToken = $this->beginV1Request();
        $userId = $this->requireUserId($apiToken);

        $items = SavedSearch::getByUser($userId);
        $this->logApiRequest($apiToken, 'api.saved_searches.list', 200, 'Saved searches listed');
        $this->respond(true, ['saved_searches' => $items]);
    }

    public function create(): void
    {
        $apiToken = $this->beginV1Request();
        $userId = $this->requireUserId($apiToken);

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $name = trim((string) ($input['name'] ?? 'Saved Search'));
        $filters = (array) ($input['filters'] ?? []);
        $ok = SavedSearch::createForUser($userId, $name, $filters);
        if (!$ok) {
            $this->logApiRequest($apiToken, 'api.saved_searches.create', 500, 'Failed creating saved search');
            $this->respond(false, null, 'Failed to create saved search', 500);
        }

        $this->logApiRequest($apiToken, 'api.saved_searches.create', 201, 'Saved search created');
        $this->respond(true, ['created' => true], null, 201);
    }

    public function delete($id): void
    {
        $apiToken = $this->beginV1Request();
        $userId = $this->requireUserId($apiToken);

        $ok = SavedSearch::deleteById((int) $id, $userId);
        if (!$ok) {
            $this->logApiRequest($apiToken, 'api.saved_searches.delete', 500, 'Failed deleting saved search');
            $this->respond(false, null, 'Failed to delete saved search', 500);
        }

        $this->logApiRequest($apiToken, 'api.saved_searches.delete', 200, 'Saved search deleted');
        $this->respond(true, ['deleted' => true]);
    }
}
