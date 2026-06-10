<?php

namespace Src\classes;

final class RouteRegistry
{
    /** @var array<int, array{path:string,regex:string,paramOrder:array<int,string>,route:array}>|null */
    private static ?array $compiled = null;

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(): array
    {
        $file = DIRREQ . 'config/routes.php';
        if (!is_file($file)) {
            return [];
        }

        $routes = require $file;
        return is_array($routes) ? $routes : [];
    }

    public static function match(string $httpMethod, array $urlSegments): ?ResolvedRoute
    {
        $path = self::segmentsToPath($urlSegments);

        $httpMethod = strtoupper(trim($httpMethod));
        if ($httpMethod === '') {
            $httpMethod = 'GET';
        }

        foreach (self::compiledRoutes() as $compiled) {
            if (!preg_match($compiled['regex'], $path, $matches)) {
                continue;
            }

            $route = $compiled['route'];
            $methods = $route['methods'] ?? ['GET', 'POST'];
            if (!is_array($methods)) {
                $methods = ['GET', 'POST'];
            }
            $methods = array_map('strtoupper', $methods);
            if (!in_array($httpMethod, $methods, true)) {
                continue;
            }

            $controllerShort = (string) ($route['controller'] ?? '');
            $controllerClass = self::resolveControllerClass($controllerShort);
            if ($controllerClass === null) {
                continue;
            }

            $params = [];
            foreach ($compiled['paramOrder'] as $paramName) {
                $params[$paramName] = (string) ($matches[$paramName] ?? '');
            }

            $segments = explode('/', $path);
            $routeKey = $segments[0] ?? '';

            return new ResolvedRoute(
                $controllerClass,
                self::shortNameFromClass($controllerClass),
                (string) ($route['action'] ?? ''),
                $params,
                $compiled['paramOrder'],
                is_array($route['middleware'] ?? null) ? $route['middleware'] : [],
                $routeKey,
                $httpMethod,
                $path
            );
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::definitions();
    }

    /**
     * @param array<int, string> $segments
     */
    private static function segmentsToPath(array $segments): string
    {
        $parts = [];
        foreach ($segments as $segment) {
            $segment = trim((string) $segment);
            if ($segment !== '') {
                $parts[] = $segment;
            }
        }
        return implode('/', $parts);
    }

    /**
     * @return array<int, array{path:string,regex:string,paramOrder:array<int,string>,route:array}>
     */
    private static function compiledRoutes(): array
    {
        if (self::$compiled !== null) {
            return self::$compiled;
        }

        self::$compiled = [];
        foreach (self::definitions() as $route) {
            if (!is_array($route)) {
                continue;
            }
            $rawPath = (string) ($route['path'] ?? '');
            $path = $rawPath === '/' ? '' : trim($rawPath, '/');

            $paramOrder = [];
            $regexBody = '';
            $offset = 0;
            while (preg_match('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $path, $m, PREG_OFFSET_CAPTURE, $offset)) {
                $literal = substr($path, $offset, $m[0][1] - $offset);
                if ($literal !== '') {
                    $regexBody .= preg_quote($literal, '#');
                }
                $paramOrder[] = $m[1][0];
                $regexBody .= '(?P<' . $m[1][0] . '>[^/]+)';
                $offset = $m[0][1] + strlen($m[0][0]);
            }
            $regexBody .= preg_quote(substr($path, $offset), '#');
            $regex = $path === '' ? '#^$#i' : '#^' . $regexBody . '$#i';

            self::$compiled[] = [
                'path' => $path,
                'regex' => $regex,
                'paramOrder' => $paramOrder,
                'route' => $route + ['path' => $path],
            ];
        }

        return self::$compiled;
    }

    private static function resolveControllerClass(string $controller): ?string
    {
        $controller = trim($controller);
        if ($controller === '') {
            return null;
        }

        if (strpos($controller, '\\') !== false) {
            return class_exists($controller) ? $controller : null;
        }

        $class = 'App\\controller\\' . $controller;
        return class_exists($class) ? $class : null;
    }

    /**
     * @param class-string $class
     */
    private static function shortNameFromClass(string $class): string
    {
        $pos = strrpos($class, '\\');
        return $pos === false ? $class : substr($class, $pos + 1);
    }
}
