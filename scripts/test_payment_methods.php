<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'This script must be executed via CLI.' . PHP_EOL;
    exit(1);
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/config.php';

$output = [];
$output[] = "=== Payment Methods Check ===\n";

try {
    $pdo = new PDO('mysql:host=' . HOST . ';dbname=' . DB, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query('SELECT COUNT(*) as total FROM payment_methods');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $result['total'];
    $output[] = "Total de métodos: $total\n";

    if ($total == 0) {
        $output[] = "Tabela vazia! Inserindo dados...\n";
        $insert = $pdo->prepare('INSERT INTO payment_methods (code, name, direction, audience, requires_reference, is_active) VALUES (?, ?, ?, ?, ?, ?)');
        $methods = [
            ['bank_transfer', 'Depósito/Transferência Bancária', 'both', 'both', 1, 1],
        ];
        foreach ($methods as $m) {
            $insert->execute($m);
            $output[] = '  ✓ ' . $m[1] . "\n";
        }
        $output[] = "Dados inseridos com sucesso!\n";
    }

    $stmt = $pdo->query('SELECT id, code, name, direction, audience FROM payment_methods ORDER BY id');
    $output[] = "\nMétodos:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $output[] = "  [{$row['id']}] {$row['name']} ({$row['code']}) - {$row['direction']}/{$row['audience']}\n";
    }
} catch (Exception $e) {
    $output[] = 'Erro: ' . $e->getMessage() . "\n";
}

$contents = implode('', $output);
$logPath = $rootDir . '/storage/logs/test_payment_methods.log';
file_put_contents($logPath, $contents);
echo $contents;
