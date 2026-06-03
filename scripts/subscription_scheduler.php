<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be executed via CLI." . PHP_EOL;
    exit(1);
}

$rootDir = dirname(__DIR__);
if (!isset($_SERVER['DOCUMENT_ROOT']) || $_SERVER['DOCUMENT_ROOT'] === '') {
    $_SERVER['DOCUMENT_ROOT'] = $rootDir;
}
if (!isset($_SERVER['HTTP_HOST']) || $_SERVER['HTTP_HOST'] === '') {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once $rootDir . '/src/vendor/autoload.php';
require_once $rootDir . '/config/config.php';

use App\model\UserSubscription;

$result = [
    'timestamp' => date('c'),
    'ok' => true,
    'invoiced' => 0,
    'skipped' => 0,
    'failed' => 0,
    'downgraded' => 0,
    'error' => null,
];

try {
    $stats = UserSubscription::runRenewalCycle(300);
    $result['invoiced'] = (int) ($stats['invoiced'] ?? 0);
    $result['skipped'] = (int) ($stats['skipped'] ?? 0);
    $result['failed'] = (int) ($stats['failed'] ?? 0);
    $result['downgraded'] = (int) ($stats['downgraded'] ?? 0);
} catch (\Throwable $e) {
    $result['ok'] = false;
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($result['ok'] ? 0 : 1);
