<?php

namespace App\controller;

use Src\classes\ClassRateLimiter;

class ControllerApiPages
{
    use ApiControllerSupport;

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
}
