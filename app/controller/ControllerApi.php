<?php

namespace App\controller;

/**
 * Legacy facade for /api/* URLs not matched by declarative routes.
 */
class ControllerApi
{
    private ?ControllerApiPages $pages = null;

    private function pages(): ControllerApiPages
    {
        return $this->pages ??= new ControllerApiPages();
    }

    public function index(): void
    {
        $this->pages()->index();
    }

    public function health(): void
    {
        $this->pages()->health();
    }

    private ?ControllerApiV1 $v1 = null;

    private function v1Controller(): ControllerApiV1
    {
        return $this->v1 ??= new ControllerApiV1();
    }

    public function v1(): void
    {
        $this->v1Controller()->v1();
    }
}
