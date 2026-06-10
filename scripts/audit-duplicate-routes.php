<?php

declare(strict_types=1);

/**
 * Finds duplicate declarative routes (same path + HTTP methods).
 * Usage: php scripts/audit-duplicate-routes.php
 */

$root = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $root;
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
require_once $root . '/src/vendor/autoload.php';
require_once $root . '/config/config.php';

$routes = Src\classes\RouteRegistry::all();
$seen = [];
$issues = [];

foreach ($routes as $index => $route) {
    if (!is_array($route)) {
        continue;
    }
    $path = (string) ($route['path'] ?? '');
    $methods = $route['methods'] ?? ['GET', 'POST'];
    if (!is_array($methods)) {
        $methods = ['GET', 'POST'];
    }
    $methods = array_map('strtoupper', $methods);
    sort($methods);
    $key = $path . '|' . implode(',', $methods);

    if (isset($seen[$key])) {
        $prev = $seen[$key];
        $issues[] = sprintf(
            'Duplicate %s [%s]: lines ~%d (%s::%s) and ~%d (%s::%s)',
            $path,
            implode(',', $methods),
            $prev['line'],
            $prev['controller'],
            $prev['action'],
            $index + 1,
            (string) ($route['controller'] ?? ''),
            (string) ($route['action'] ?? '')
        );
        continue;
    }

    $seen[$key] = [
        'line' => $index + 1,
        'controller' => (string) ($route['controller'] ?? ''),
        'action' => (string) ($route['action'] ?? ''),
    ];
}

if ($issues === []) {
    echo "AUDIT DUPLICATE ROUTES: OK\n";
    exit(0);
}

echo 'AUDIT DUPLICATE ROUTES: ' . count($issues) . " issue(s)\n";
foreach ($issues as $issue) {
    echo '- ' . $issue . "\n";
}
exit(1);
