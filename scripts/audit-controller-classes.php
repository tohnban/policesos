<?php

declare(strict_types=1);

/**
 * Verifies controller classes/methods from routes and scans for unresolved class references at runtime.
 * Usage: php scripts/audit-controller-classes.php
 */

$root = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $root;
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

require_once $root . '/src/vendor/autoload.php';
require_once $root . '/config/config.php';

$issues = [];

foreach (glob($root . '/app/controller/*.php') ?: [] as $file) {
    $basename = basename($file, '.php');
    if (str_ends_with($basename, 'Support')) {
        continue;
    }
    $fqn = 'App\\controller\\' . $basename;
    if (!class_exists($fqn)) {
        $issues[] = "Class not autoloadable: {$fqn}";
        continue;
    }

    $source = (string) file_get_contents($file);
    if (!preg_match_all('/\b([A-Z][A-Za-z0-9_]+)::/', $source, $matches)) {
        continue;
    }

    $skip = ['self' => true, 'parent' => true, 'static' => true];
    foreach (array_unique($matches[1]) as $class) {
        if (isset($skip[$class])) {
            continue;
        }
        if (str_contains($source, 'use ' . $class) || str_contains($source, 'use App\\model\\' . $class)) {
            continue;
        }
        if (str_contains($source, 'use Src\\classes\\' . $class)) {
            continue;
        }
        if (str_contains($source, 'use App\\services\\' . $class)) {
            continue;
        }
        if (str_contains($source, '\\' . $class . '::')) {
            continue;
        }

        $candidates = [
            'App\\controller\\' . $class,
            'App\\model\\' . $class,
            'Src\\classes\\' . $class,
            'App\\services\\' . $class,
        ];

        $resolved = false;
        foreach ($candidates as $candidate) {
            if (class_exists($candidate) || interface_exists($candidate) || trait_exists($candidate)) {
                if ($candidate === 'App\\controller\\' . $class && $class !== $basename) {
                    $issues[] = "{$basename}: likely missing import for {$class} (resolves to wrong namespace App\\controller\\{$class})";
                }
                $resolved = true;
                break;
            }
        }

        if (!$resolved) {
            continue;
        }
    }
}

$routeIssues = [];
foreach (Src\classes\RouteRegistry::all() as $route) {
    if (!is_array($route)) {
        continue;
    }
    $controller = (string) ($route['controller'] ?? '');
    $action = (string) ($route['action'] ?? 'index');
    if ($controller === '' || $action === '') {
        continue;
    }

    $fqn = 'App\\controller\\' . $controller;
    if (!class_exists($fqn)) {
        $routeIssues[] = "Route {$route['path']}: controller {$fqn} not found";
        continue;
    }
    if (!method_exists($fqn, $action)) {
        $routeIssues[] = "Route {$route['path']}: method {$fqn}::{$action}() not found";
    }
}

if ($issues === [] && $routeIssues === []) {
    echo "AUDIT CONTROLLER CLASSES: OK\n";
    exit(0);
}

echo 'AUDIT CONTROLLER CLASSES: ' . (count($issues) + count($routeIssues)) . " issue(s)\n";
foreach ($issues as $issue) {
    echo "- {$issue}\n";
}
foreach ($routeIssues as $issue) {
    echo "- {$issue}\n";
}
exit(1);
