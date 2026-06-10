<?php

declare(strict_types=1);

/**
 * Detects likely missing `use` imports in app/controller PHP files.
 * Usage: php scripts/audit-controller-imports.php
 */

$root = dirname(__DIR__);
$scanFiles = array_merge(
    glob($root . '/app/controller/*.php') ?: [],
    glob($root . '/app/traits/*.php') ?: []
);

$knownFqns = [];
foreach (glob($root . '/app/model/*.php') ?: [] as $file) {
    $name = basename($file, '.php');
    $knownFqns[$name] = 'App\\model\\' . $name;
}
foreach (glob($root . '/src/classes/*.php') ?: [] as $file) {
    $name = basename($file, '.php');
    $knownFqns[$name] = 'Src\\classes\\' . $name;
}
foreach (glob($root . '/app/services/*.php') ?: [] as $file) {
    $name = basename($file, '.php');
    $knownFqns[$name] = 'App\\services\\' . $name;
}

$skipClasses = [
    'self' => true,
    'parent' => true,
    'static' => true,
    'true' => true,
    'false' => true,
    'null' => true,
];

$issues = [];

foreach ($scanFiles as $file) {
    $content = (string) file_get_contents($file);
    $relative = str_replace('\\', '/', substr($file, strlen($root) + 1));
    $imports = parseImports($content);
    $refs = findClassReferences($content);

    foreach ($refs as $class) {
        $lower = strtolower($class);
        if (isset($skipClasses[$lower])) {
            continue;
        }
        if (isset($imports[$class])) {
            continue;
        }
        if (!isset($knownFqns[$class])) {
            continue;
        }
        if (hasRootQualifiedReference($content, $class)) {
            continue;
        }

        $issues[] = [
            'file' => $relative,
            'message' => "missing `use {$knownFqns[$class]};` (uses {$class}::)",
        ];
    }

    foreach (findFauxQualifiedReferences($content) as $message) {
        $issues[] = [
            'file' => $relative,
            'message' => $message,
        ];
    }
}

if ($issues === []) {
    echo "AUDIT CONTROLLER IMPORTS: OK\n";
    exit(0);
}

echo 'AUDIT CONTROLLER IMPORTS: ' . count($issues) . " issue(s)\n";
foreach ($issues as $issue) {
    echo '- ' . $issue['file'] . ': ' . $issue['message'] . "\n";
}
exit(1);

function parseImports(string $content): array
{
    $imports = [];
    if (!preg_match_all('/^use\s+([^;]+);/m', $content, $matches)) {
        return $imports;
    }

    foreach ($matches[1] as $useStmt) {
        $useStmt = trim($useStmt);
        if (str_contains($useStmt, ' as ')) {
            [$fqn, $alias] = preg_split('/\s+as\s+/', $useStmt, 2);
            $imports[trim($alias)] = trim($fqn);
        } else {
            $parts = explode('\\', $useStmt);
            $imports[end($parts)] = $useStmt;
        }
    }

    return $imports;
}

/**
 * @return list<string>
 */
function findClassReferences(string $content): array
{
    $tokens = token_get_all($content);
    $refs = [];
    $count = count($tokens);

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        if (!is_array($token) || $token[0] !== T_STRING) {
            continue;
        }

        $name = $token[1];
        $next = nextNonWhitespaceToken($tokens, $i + 1);
        if ($next === '::' || $next === '(') {
            $refs[$name] = true;
        }
    }

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        if (!is_array($token) || $token[0] !== T_NEW) {
            continue;
        }

        $next = nextMeaningfulToken($tokens, $i + 1);
        if (is_array($next) && $next[0] === T_STRING) {
            $refs[$next[1]] = true;
        }
    }

    return array_keys($refs);
}

function hasRootQualifiedReference(string $content, string $class): bool
{
    return (bool) preg_match('/\\\\' . preg_quote($class, '/') . '(::|\()/', $content);
}

/**
 * Catches `Src\classes\Foo::` without a leading root backslash inside namespaced code.
 *
 * @return list<string>
 */
function findFauxQualifiedReferences(string $content): array
{
    $issues = [];
    if (!preg_match_all('/(?<!\\\\)(?<!use )((?:App\\\\model|Src\\\\classes|App\\\\services)\\\\[A-Za-z0-9_]+)::/', $content, $matches, PREG_OFFSET_CAPTURE)) {
        return $issues;
    }

    foreach ($matches[1] as $match) {
        $fqn = $match[0];
        $issues[] = "invalid namespace-relative reference `{$fqn}::` (use `use {$fqn};` or `\\\\{$fqn}::`)";
    }

    return array_values(array_unique($issues));
}

function nextNonWhitespaceToken(array $tokens, int $start): string|array|null
{
    $count = count($tokens);
    for ($i = $start; $i < $count; $i++) {
        $token = $tokens[$i];
        if (is_array($token) && ($token[0] === T_WHITESPACE || $token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT)) {
            continue;
        }
        return $token;
    }

    return null;
}

function nextMeaningfulToken(array $tokens, int $start): string|array|null
{
    return nextNonWhitespaceToken($tokens, $start);
}
