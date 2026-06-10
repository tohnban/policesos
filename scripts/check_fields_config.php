<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'This script must be executed via CLI.' . PHP_EOL;
    exit(1);
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/config.php';

$out = [];
try {
    $pdo = new PDO('mysql:host=' . HOST . ';dbname=' . DB, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query('SELECT id, code, name, fields_config FROM payment_methods ORDER BY id');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out[] = 'Total: ' . count($rows);
    foreach ($rows as $r) {
        $out[] = sprintf('[%d] %s (%s) => %s', (int) $r['id'], $r['name'], $r['code'], (string) $r['fields_config']);
    }
} catch (Exception $e) {
    $out[] = 'Erro: ' . $e->getMessage();
}

$text = implode(PHP_EOL, $out);
$logPath = $rootDir . '/storage/logs/check_fields_config.log';
file_put_contents($logPath, $text);
echo $text . PHP_EOL;
