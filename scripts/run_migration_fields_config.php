<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'This script must be executed via CLI.' . PHP_EOL;
    exit(1);
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/config.php';

try {
    $pdo = new PDO('mysql:host=' . HOST . ';dbname=' . DB, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec('ALTER TABLE payment_methods
        ADD COLUMN IF NOT EXISTS fields_config JSON NULL AFTER requires_reference');
    echo "Coluna fields_config adicionada.\n";

    $updates = [
        ['bank_transfer', '{"account_name":true,"account_number":true,"iban":true,"bank_name":true,"wallet_provider":false,"phone_number":false}'],
    ];
    $upd = $pdo->prepare('UPDATE payment_methods SET fields_config = ? WHERE code = ? AND fields_config IS NULL');
    foreach ($updates as [$code, $json]) {
        $upd->execute([$json, $code]);
        echo "  Config definida para: $code\n";
    }

    $stmt = $pdo->query('SELECT code, fields_config FROM payment_methods');
    echo "\nConfigs:\n";
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "  [{$row['code']}] {$row['fields_config']}\n";
    }
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
    exit(1);
}
