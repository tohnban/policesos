<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $root;
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

require_once $root . '/src/vendor/autoload.php';
require_once $root . '/config/config.php';

$path = $argv[1] ?? 'property/moderate';
$method = strtoupper($argv[2] ?? 'GET');
$segments = $path === '' ? [] : explode('/', trim($path, '/'));

$resolved = Src\classes\RouteRegistry::match($method, $segments);
if ($resolved === null) {
    echo "NO MATCH for {$method} /{$path}\n";
    exit(1);
}

echo $resolved->controllerClass . '::' . $resolved->action . " ({$resolved->path})\n";
exit(0);
