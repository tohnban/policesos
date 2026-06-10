<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only." . PHP_EOL;
    exit(1);
}

$rootDir = dirname(__DIR__);
if (!isset($_SERVER['DOCUMENT_ROOT']) || $_SERVER['DOCUMENT_ROOT'] === '') {
    $_SERVER['DOCUMENT_ROOT'] = $rootDir;
}
require_once $rootDir . '/config/config.php';

$methodName = 'Depósito/Transferência Bancária';
$methodCode = 'bank_transfer';
$fieldsConfig = '{"account_name":true,"account_number":true,"iban":true,"bank_name":true,"wallet_provider":false,"phone_number":false}';

try {
    $pdo = new PDO(
        'mysql:host=' . HOST . ';dbname=' . DB . ';charset=utf8mb4',
        USER,
        PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->beginTransaction();

    $existing = $pdo->prepare('SELECT id FROM payment_methods WHERE code = ? AND deleted_at IS NULL LIMIT 1');
    $existing->execute([$methodCode]);
    $bankId = (int) ($existing->fetchColumn() ?: 0);

    if ($bankId <= 0) {
        $insert = $pdo->prepare(
            'INSERT INTO payment_methods (code, name, direction, audience, requires_reference, fields_config, is_active)
             VALUES (?, ?, ?, ?, 1, ?, 1)'
        );
        $insert->execute([$methodCode, $methodName, 'both', 'both', $fieldsConfig]);
        $bankId = (int) $pdo->lastInsertId();
    } else {
        $pdo->prepare(
            'UPDATE payment_methods SET name = ?, is_active = 1, fields_config = ?, deleted_at = NULL WHERE id = ?'
        )->execute([$methodName, $fieldsConfig, $bankId]);
    }

    $pdo->prepare(
        'UPDATE system_payment_channels SET is_active = 0 WHERE method_id != ?'
    )->execute([$bankId]);

    $pdo->prepare(
        'UPDATE payment_methods SET is_active = 0, deleted_at = NOW() WHERE id != ? AND deleted_at IS NULL'
    )->execute([$bankId]);

    $pdo->commit();

    $stmt = $pdo->query(
        "SELECT id, code, name, is_active FROM payment_methods WHERE deleted_at IS NULL ORDER BY id"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'active_method' => $methodName,
        'methods' => $rows,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(1);
}
