<?php
/**
 * Verifica links estaticos em views contra rotas declarativas.
 * Uso: c:\xampp\php\php.exe scripts/audit-view-routes.php
 */
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once dirname(__DIR__) . '/config/config.php';
require_once DIRREQ . 'src/vendor/autoload.php';

$viewRoot = DIRREQ . 'app/view';
$patterns = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewRoot));

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $content = (string) file_get_contents($file->getPathname());
    if (preg_match_all("#DIRPAGE\s*\.\s*'([a-zA-Z0-9_][a-zA-Z0-9_./-]*)'#", $content, $matches)) {
        foreach ($matches[1] as $path) {
            $path = trim($path, '/');
            if ($path !== '') {
                $patterns[$path] = true;
            }
        }
    }
    if (preg_match_all('#DIRPAGE\s*\.\s*"([a-zA-Z0-9_][a-zA-Z0-9_./-]*)"#', $content, $matches)) {
        foreach ($matches[1] as $path) {
            $path = trim($path, '/');
            if ($path !== '') {
                $patterns[$path] = true;
            }
        }
    }
}

ksort($patterns);

function normalize_for_route_match(string $path): string
{
    $parts = $path === '' ? [] : explode('/', $path);
    foreach ($parts as $i => $part) {
        if ($part !== '' && ctype_digit($part)) {
            $parts[$i] = '{id}';
        }
    }
    return implode('/', $parts);
}

function route_template_matches(string $template, string $normalizedPath): bool
{
    $template = trim($template, '/');
    $normalizedPath = trim($normalizedPath, '/');

    if ($template === $normalizedPath) {
        return true;
    }

    $regexBody = '';
    $offset = 0;
    while (preg_match('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $template, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $literal = substr($template, $offset, $m[0][1] - $offset);
        if ($literal !== '') {
            $regexBody .= preg_quote($literal, '#');
        }
        $regexBody .= '[^/]+';
        $offset = $m[0][1] + strlen($m[0][0]);
    }
    $regexBody .= preg_quote(substr($template, $offset), '#');

    return (bool) preg_match('#^' . $regexBody . '$#i', $normalizedPath);
}

$declarativePaths = [];
foreach (Src\classes\RouteRegistry::all() as $route) {
    if (!is_array($route)) {
        continue;
    }
    $path = (string) ($route['path'] ?? '');
    $declarativePaths[] = $path === '/' ? '' : trim($path, '/');
}

$legacySegments = array_keys(Src\classes\ClassRoutes::LEGACY_SEGMENT_CONTROLLERS);

echo 'Static view paths: ' . count($patterns) . "\n";
echo str_repeat('-', 72) . "\n";

$unmapped = [];
foreach (array_keys($patterns) as $path) {
    $normalized = normalize_for_route_match($path);
    $matched = false;

    foreach ($declarativePaths as $declarativePath) {
        if (route_template_matches($declarativePath, $normalized)) {
            $matched = true;
            break;
        }
    }

    if (!$matched) {
        $first = explode('/', $path, 2)[0];
        if (in_array($first, $legacySegments, true)) {
            continue;
        }
        $unmapped[] = $path;
    }
}

if ($unmapped === []) {
    echo "Todos os links estaticos das views tem rota declarativa ou legacy conhecida.\n";
    exit(0);
}

echo "Links sem rota declarativa (nem legacy de primeiro segmento):\n";
foreach ($unmapped as $path) {
    echo " - /{$path}\n";
}
exit(1);
