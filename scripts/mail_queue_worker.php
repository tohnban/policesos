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
use Src\classes\ClassMailer;
use Src\classes\ClassSettings;

$workerId = 'mail-worker-' . getmypid() . '-' . bin2hex(random_bytes(3));
$batchSize = max(1, ClassSettings::int('mail_queue_batch_size', 20));
$lockTimeout = max(60, ClassSettings::int('mail_queue_lock_timeout_seconds', 300));

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

try {
    $result['requeued_stale'] = BackgroundJob::requeueStaleProcessing($lockTimeout);
    $jobs = BackgroundJob::claimBatch('mail', $workerId, $batchSize);
    $result['claimed'] = count($jobs);

    foreach ($jobs as $job) {
        $jobId = (int) ($job['id'] ?? 0);
        $payload = (array) ($job['payload'] ?? []);

        $toEmail = (string) ($payload['to_email'] ?? '');
        $toName = (string) ($payload['to_name'] ?? '');
        $subject = (string) ($payload['subject'] ?? '');
        $htmlBody = (string) ($payload['html_body'] ?? '');
        $textBody = (string) ($payload['text_body'] ?? '');

        if ($jobId <= 0 || $toEmail === '' || $subject === '' || $htmlBody === '') {
            BackgroundJob::markFailedOrRetry($jobId, 'Payload inválido para envio de email.');
            $result['failed']++;
            continue;
        }

        $sent = ClassMailer::send($toEmail, $toName, $subject, $htmlBody, $textBody);

        if ($sent) {
            BackgroundJob::markCompleted($jobId);
            $result['completed']++;
            continue;
        }

        BackgroundJob::markFailedOrRetry($jobId, 'Falha no envio SMTP/PHPMailer.');
        $result['failed']++;
    }
} catch (\Throwable $e) {
    $result['ok'] = false;
    $result['error'] = $e->getMessage();
}

Log::create([
    'user_id' => null,
    'action' => 'mail_queue_worker_run',
    'entity_type' => 'background_job',
    'entity_id' => null,
    'details' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
]);

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($result['ok'] ? 0 : 1);
