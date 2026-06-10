<?php

declare(strict_types=1);

/**
 * Detects declarative routes shadowed by earlier parametric routes (e.g. property/{id} before property/moderate).
 * Usage: php scripts/audit-route-shadows.php
 */

$root = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $root;
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

require_once $root . '/src/vendor/autoload.php';
require_once $root . '/config/config.php';

$routes = Src\classes\RouteRegistry::all();
$compiled = [];

foreach ($routes as $index => $route) {
    if (!is_array($route)) {
        continue;
    }
    $path = trim((string) ($route['path'] ?? ''), '/');
    if ($path === '') {
        continue;
    }
    if (!str_contains($path, '{')) {
        continue;
    }

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
    $regex = '#^' . $regexBody . '$#i';

    $compiled[] = [
        'index' => $index,
        'path' => $path,
        'regex' => $regex,
        'methods' => array_map('strtoupper', is_array($route['methods'] ?? null) ? $route['methods'] : ['GET', 'POST']),
    ];
}

$literalRoutes = [];
foreach ($routes as $index => $route) {
    if (!is_array($route)) {
        continue;
    }
    $path = trim((string) ($route['path'] ?? ''), '/');
    if ($path === '' || str_contains($path, '{')) {
        continue;
    }
    $literalRoutes[] = [
        'index' => $index,
        'path' => $path,
        'methods' => array_map('strtoupper', is_array($route['methods'] ?? null) ? $route['methods'] : ['GET', 'POST']),
    ];
}

$issues = [];
foreach ($compiled as $paramRoute) {
    foreach ($literalRoutes as $literalRoute) {
        if ($literalRoute['index'] <= $paramRoute['index']) {
            continue;
        }
        if (!preg_match($paramRoute['regex'], $literalRoute['path'])) {
            continue;
        }
        $sharedMethods = array_intersect($paramRoute['methods'], $literalRoute['methods']);
        if ($sharedMethods === []) {
            continue;
        }
        $issues[] = sprintf(
            '%s is shadowed by earlier %s (shared methods: %s)',
            $literalRoute['path'],
            $paramRoute['path'],
            implode(',', $sharedMethods)
        );
    }
}

if ($issues === []) {
    echo "AUDIT ROUTE SHADOWS: OK\n";
    exit(0);
}

echo 'AUDIT ROUTE SHADOWS: ' . count($issues) . " issue(s)\n";
foreach ($issues as $issue) {
    echo '- ' . $issue . "\n";
}
exit(1);
