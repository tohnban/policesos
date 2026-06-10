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

    $stmt = $pdo->query('SELECT * FROM payment_methods');
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo 'Total de métodos: ' . count($methods) . "\n";

    if (empty($methods)) {
        echo "Tabela vazia! Inserindo dados...\n";

        $insert = $pdo->prepare('INSERT INTO payment_methods (name, code, direction, audience, requires_reference, is_active) VALUES (?, ?, ?, ?, ?, ?)');

        $methods_to_insert = [
            ['Depósito/Transferência Bancária', 'bank_transfer', 'both', 'both', 1, 1],
        ];

        foreach ($methods_to_insert as $method) {
            $insert->execute($method);
            echo 'Inserido: ' . $method[0] . "\n";
        }

        $stmt = $pdo->query('SELECT * FROM payment_methods');
        $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\nMétodos após inserção: " . count($methods) . "\n";
    }

    echo "\nMétodos no banco:\n";
    foreach ($methods as $m) {
        echo "- {$m['name']} ({$m['code']}): {$m['direction']}, público: {$m['audience']}\n";
    }
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}
