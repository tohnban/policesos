<?php
/**
 * CLI scheduler: expire approved boost campaigns and unfeature properties when needed.
 * Usage: php scripts/boost_expiration_scheduler.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Forbidden\n";
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/src/vendor/autoload.php';
require_once $root . '/config/config.php';

use App\model\PropertyBoostRequest;

$startedAt = date('Y-m-d H:i:s');
$result = [
    'ok' => true,
    'started_at' => $startedAt,
    'expired_boosts' => 0,
    'properties_unfeatured' => 0,
    'error' => null,
];

try {
    $stats = PropertyBoostRequest::expireDueBoosts();
    $result['expired_boosts'] = (int) ($stats['expired_boosts'] ?? 0);
    $result['properties_unfeatured'] = (int) ($stats['properties_unfeatured'] ?? 0);
} catch (\Throwable $e) {
    $result['ok'] = false;
    $result['error'] = $e->getMessage();
}

$result['finished_at'] = date('Y-m-d H:i:s');

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($result['ok'] ? 0 : 1);
