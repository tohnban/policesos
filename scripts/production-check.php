<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only." . PHP_EOL;
    exit(1);
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/src/vendor/autoload.php';

$envFile = $rootDir . '/.env';
$errors = [];
$warnings = [];

if (!file_exists($envFile)) {
    $errors[] = 'Missing .env file.';
} else {
    Src\classes\ClassEnv::load($envFile);
}

$appEnv = strtolower((string) Src\classes\ClassEnv::get('APP_ENV', 'development'));
$appUrl = trim((string) Src\classes\ClassEnv::get('APP_URL', ''));
$appDebug = Src\classes\ClassEnv::get('APP_DEBUG', null);
$emailEnabled = Src\classes\ClassEnv::get('EMAIL_ENABLED', false);

if ($appEnv !== 'production') {
    $warnings[] = "APP_ENV is '{$appEnv}' (expected 'production' for go-live).";
}

if ($appUrl === '') {
    $errors[] = 'APP_URL must be set in production.';
} elseif (!preg_match('#^https://#i', $appUrl)) {
    $warnings[] = 'APP_URL should use HTTPS in production.';
}

if ($appDebug === null || $appDebug === '') {
    if ($appEnv === 'production') {
        // config.php defaults debug off in production
    }
} elseif (filter_var($appDebug, FILTER_VALIDATE_BOOLEAN)) {
    $errors[] = 'APP_DEBUG must be false in production.';
}

if (!filter_var($emailEnabled, FILTER_VALIDATE_BOOLEAN)) {
    $warnings[] = 'EMAIL_ENABLED is false — transactional email disabled.';
}

$dbPass = (string) Src\classes\ClassEnv::get('DB_PASS', '');
if ($dbPass === '') {
    $warnings[] = 'DB_PASS is empty.';
}

$requiredDirs = [
    'storage/cache',
    'storage/logs',
    'storage/documents',
    'public/storage/uploads',
];

foreach ($requiredDirs as $dir) {
    $path = $rootDir . '/' . $dir;
    if (!is_dir($path)) {
        $errors[] = "Missing directory: {$dir} (run ensure-storage-dirs.php)";
    } elseif (!is_writable($path)) {
        $errors[] = "Directory not writable: {$dir}";
    }
}

$vendorAutoload = $rootDir . '/src/vendor/autoload.php';
if (!is_file($vendorAutoload)) {
    $errors[] = 'Missing src/vendor/autoload.php — run composer install --no-dev in src/';
}

$htaccess = $rootDir . '/.htaccess';
if (!is_file($htaccess) || strpos((string) file_get_contents($htaccess), 'storage/documents') === false) {
    $warnings[] = 'Root .htaccess may be missing production hardening rules.';
}

$result = [
    'ok' => count($errors) === 0,
    'environment' => $appEnv,
    'errors' => $errors,
    'warnings' => $warnings,
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit($result['ok'] ? 0 : 1);
