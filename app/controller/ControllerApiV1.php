<?php

namespace App\controller;

use Src\traits\TraitUrlParser;

class ControllerApiV1
{
    use ApiControllerSupport;
    use TraitUrlParser;

    public function root(): void
    {
        $apiToken = $this->beginV1Request();
        $this->logApiRequest($apiToken, 'api.root', 200, 'API v1 root accessed');
        $this->respond(true, [
            'version' => 'v1',
            'resources' => ['/api/v1/properties', '/api/v1/property/{id}'],
        ]);
    }

    /**
     * Legacy dispatcher for /api/v1/* when resolved via ClassRoutes + addMethod.
     */
    public function v1(): void
    {
        $segments = $this->parseUrl();
        $subPath = $segments[2] ?? null;
        $resource = $segments[3] ?? null;

        if ($subPath === null) {
            $this->root();
            return;
        }

        $properties = new ControllerApiV1Properties();
        if ($subPath === 'properties') {
            $properties->properties();
            return;
        }

        if ($subPath === 'property' && $resource !== null && is_numeric($resource)) {
            $properties->property((int) $resource);
            return;
        }

        $tokens = new ControllerApiV1Tokens();
        if ($subPath === 'tokens') {
            if ($resource !== null && is_numeric($resource) && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'DELETE') {
                $tokens->revoke((int) $resource);
                return;
            }

            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET') {
                $tokens->list();
                return;
            }

            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
                $tokens->create();
                return;
            }
        }

        $savedSearches = new ControllerApiV1SavedSearches();
        if ($subPath === 'saved_searches') {
            if ($resource !== null && is_numeric($resource) && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'DELETE') {
                $savedSearches->delete((int) $resource);
                return;
            }

            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET') {
                $savedSearches->list();
                return;
            }

            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
                $savedSearches->create();
                return;
            }
        }

        $notifications = new ControllerApiV1Notifications();
        if ($subPath === 'notifications' && $resource === 'archive') {
            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET') {
                $notifications->archive();
                return;
            }
        }

        if ($subPath === 'notifications' && $resource === null) {
            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET') {
                $notifications->inbox();
                return;
            }

            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
                $notifications->mutate();
                return;
            }
        }

        if ($subPath === 'notifications' && $resource !== null && is_numeric($resource) && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'DELETE') {
            $notifications->delete((int) $resource);
            return;
        }

        $apiToken = $this->beginV1Request();
        $this->logApiRequest($apiToken, 'api.unknown', 404, 'Resource not found');
        $this->respond(false, null, 'Resource not found', 404);
    }
}
