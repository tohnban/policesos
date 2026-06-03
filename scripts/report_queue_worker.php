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

use App\model\BackgroundJob;
use App\model\Log;
use Src\classes\ClassSettings;

$workerId = 'report-worker-' . getmypid() . '-' . bin2hex(random_bytes(3));
$batchSize = max(1, ClassSettings::int('report_queue_batch_size', 5));
$lockTimeout = max(60, ClassSettings::int('report_queue_lock_timeout_seconds', 600));

$result = [
    'timestamp' => date('c'),
    'ok' => true,
    'worker' => $workerId,
    'requeued_stale' => 0,
    'claimed' => 0,
    'completed' => 0,
    'failed' => 0,
    'error' => null,
];

function generateReport(array $payload): ?string {
    $root = rtrim(dirname(__DIR__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $reportType = $payload['report_type'] ?? 'generic';
    $params = $payload['params'] ?? [];

    $reportsDir = $root . 'storage/reports/';
    if (!is_dir($reportsDir) && !mkdir($reportsDir, 0755, true) && !is_dir($reportsDir)) {
        return null;
    }

    $filename = 'report_' . $reportType . '_' . time() . '.csv';
    $path = $reportsDir . $filename;

    try {
        $fh = fopen($path, 'w');
        if ($fh === false) return null;

        // Simple header for generic report; consumers can supply custom report generator.
        fputcsv($fh, ['generated_at', date('c')]);
        fputcsv($fh, ['report_type', $reportType]);

        // If params contain rows, write them
        if (!empty($params) && is_array($params) && count($params) > 0) {
            fputcsv($fh, []);
            fputcsv($fh, array_keys((array) $params[0]));
            foreach ($params as $row) {
                fputcsv($fh, (array) $row);
            }
        }

        fclose($fh);
        return 'storage/reports/' . $filename;
    } catch (\Throwable $e) {
        return null;
    }
}

try {
    $result['requeued_stale'] = BackgroundJob::requeueStaleProcessing($lockTimeout);
    $jobs = BackgroundJob::claimBatch('report_generation', $workerId, $batchSize);
    $result['claimed'] = count($jobs);

    foreach ($jobs as $job) {
        $jobId = (int) ($job['id'] ?? 0);
        $payload = (array) ($job['payload'] ?? []);

        if ($jobId <= 0) {
            $result['failed']++;
            continue;
        }

        $out = generateReport($payload);
        if ($out !== null) {
            BackgroundJob::markCompleted($jobId);
            $result['completed']++;
            continue;
        }

        BackgroundJob::markFailedOrRetry($jobId, 'Report generation failed.');
        $result['failed']++;
    }
} catch (\Throwable $e) {
    $result['ok'] = false;
    $result['error'] = $e->getMessage();
}

Log::create([
    'user_id' => null,
    'action' => 'report_queue_worker_run',
    'entity_type' => 'background_job',
    'entity_id' => null,
    'details' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
]);

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($result['ok'] ? 0 : 1);
