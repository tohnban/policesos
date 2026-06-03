<?php

namespace Src\classes;

final class ResolvedRoute
{
    /** @var class-string */
    public string $controllerClass;

    public string $controllerShortName;

    public string $action;

    /** @var array<string, string> */
    public array $params;

    /** @var array<int, string> */
    public array $paramOrder;

    /** @var array<int, string> */
    public array $middleware;

    public string $routeKey;

    public string $httpMethod;

    public string $path;

    /**
     * @param class-string $controllerClass
     * @param array<string, string> $params
     * @param array<int, string> $paramOrder
     * @param array<int, string> $middleware
     */
    public function __construct(
        string $controllerClass,
        string $controllerShortName,
        string $action,
        array $params,
        array $paramOrder,
        array $middleware,
        string $routeKey,
        string $httpMethod,
        string $path
    ) {
        $this->controllerClass = $controllerClass;
        $this->controllerShortName = $controllerShortName;
        $this->action = $action;
        $this->params = $params;
        $this->paramOrder = $paramOrder;
        $this->middleware = $middleware;
        $this->routeKey = $routeKey;
        $this->httpMethod = $httpMethod;
        $this->path = $path;
    }

    /**
     * @return array<int, string>
     */
    public function orderedParams(): array
    {
        $values = [];
        foreach ($this->paramOrder as $name) {
            $values[] = $this->params[$name] ?? '';
        }
        return $values;
    }
}
