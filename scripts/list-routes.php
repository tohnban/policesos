<?php
/**
 * Lista rotas declarativas (config/routes.php) para auditoria.
 * Uso: c:\xampp\php\php.exe scripts/list-routes.php
 */
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once dirname(__DIR__) . '/config/config.php';
require_once DIRREQ . 'src/vendor/autoload.php';

$routes = Src\classes\RouteRegistry::all();
$count = count($routes);

echo "Declarative routes ({$count})\n";
echo str_repeat('-', 72) . "\n";

foreach ($routes as $route) {
    if (!is_array($route)) {
        continue;
    }
    $methods = $route['methods'] ?? ['GET', 'POST'];
    if (!is_array($methods)) {
        $methods = ['GET', 'POST'];
    }
    $methods = implode(',', array_map('strtoupper', $methods));
    $path = (string) ($route['path'] ?? '');
    $controller = (string) ($route['controller'] ?? '');
    $action = (string) ($route['action'] ?? '');
    $middleware = $route['middleware'] ?? [];
    $mw = is_array($middleware) ? implode(', ', $middleware) : '';

    printf("%-8s %-42s %s::%s\n", $methods, $path, $controller, $action);
    if ($mw !== '') {
        echo "         middleware: {$mw}\n";
    }
}

echo str_repeat('-', 72) . "\n";
echo "Legacy dispatch still handles URLs not listed above.\n";
