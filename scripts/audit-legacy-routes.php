<?php
/**
 * Valida o mapa legacy minimo e cobertura declarativa de URLs de um segmento.
 * Uso: c:\xampp\php\php.exe scripts/audit-legacy-routes.php
 */
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once dirname(__DIR__) . '/config/config.php';
require_once DIRREQ . 'src/vendor/autoload.php';

$legacyFile = DIRREQ . 'src/classes/ClassRoutes.php';
$legacySource = (string) file_get_contents($legacyFile);
if (!preg_match_all("/(?:''|'([^']*)')\s*=>\s*'([^']+)'/", $legacySource, $matches, PREG_SET_ORDER)) {
    fwrite(STDERR, "Nao foi possivel ler entradas de ClassRoutes.\n");
    exit(1);
}

$legacyMap = [];
foreach ($matches as $match) {
    $legacyMap[$match[1] ?? ''] = $match[2];
}

$requiredLegacy = Src\classes\ClassRoutes::LEGACY_SEGMENT_CONTROLLERS;
$declarativePaths = [];
foreach (Src\classes\RouteRegistry::all() as $route) {
    if (!is_array($route)) {
        continue;
    }
    $path = (string) ($route['path'] ?? '');
    $declarativePaths[] = $path === '/' ? '' : trim($path, '/');
}

$singleSegmentPaths = [
    '',
    'home',
    'login',
    'register',
    'logout',
    'recover',
    'verify',
    'properties',
    'featured',
    'cookies',
    'privacidade',
    'termos',
    'sitemap',
    'robots.txt',
    'dashboard',
    'profile',
    'requests',
    'commissions',
    'referrals',
    'favorites',
    'settings',
    'admin_subscriptions',
    'moderate',
    'moderate_users',
    'payment_accounts',
    'payment_methods',
    'payment_channels',
    'payment_transactions',
    'api',
];

echo "Legacy nested dispatch (" . count($legacyMap) . " entradas)\n";
echo str_repeat('-', 72) . "\n";

$failures = [];

foreach ($requiredLegacy as $segment => $controller) {
    $actual = $legacyMap[$segment] ?? null;
    $ok = $actual === $controller;
    printf("%-22s %-28s %s\n", $segment, (string) $actual, $ok ? 'ok' : 'MISSING');
    if (!$ok) {
        $failures[] = "Segmento legacy obrigatorio ausente: {$segment} => {$controller}";
    }
}

foreach ($legacyMap as $segment => $controller) {
    if (!isset($requiredLegacy[$segment])) {
        $failures[] = "Entrada legacy inesperada: {$segment} => {$controller}";
    }
}

echo str_repeat('-', 72) . "\n";
echo "Single-segment declarative coverage\n";
echo str_repeat('-', 72) . "\n";

foreach ($singleSegmentPaths as $path) {
    $label = $path === '' ? '(root)' : $path;
    $covered = in_array($path, $declarativePaths, true);
    printf("%-22s %s\n", $label, $covered ? 'declarative' : 'MISSING');
    if (!$covered) {
        $failures[] = "Sem rota declarativa para /{$path}";
    }
}

echo str_repeat('-', 72) . "\n";
echo 'Declarative routes: ' . count($declarativePaths) . "\n";

if ($failures !== []) {
    echo "\nFalhas:\n";
    foreach ($failures as $failure) {
        echo " - {$failure}\n";
    }
    exit(1);
}

echo "\nLegacy minimo e cobertura declarativa: OK\n";
exit(0);
