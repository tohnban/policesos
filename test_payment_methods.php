<?php
require_once 'config/config.php';

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
            ['bank_transfer', 'Transferencia Bancaria', 'both', 'both', 1, 1],
            ['multicaixa_express', 'Multicaixa Express', 'both', 'both', 1, 1],
            ['mobile_wallet', 'Carteira Movel', 'both', 'both', 1, 1],
            ['cash', 'Numerario', 'incoming', 'system', 0, 1],
        ];
        foreach ($methods as $m) {
            $insert->execute($m);
            $output[] = "  ✓ " . $m[1] . "\n";
        }
        $output[] = "Dados inseridos com sucesso!\n";
    }
    
    $stmt = $pdo->query('SELECT id, code, name, direction, audience FROM payment_methods ORDER BY id');
    $output[] = "\nMétodos:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $output[] = "  [{$row['id']}] {$row['name']} ({$row['code']}) - {$row['direction']}/{$row['audience']}\n";
    }
} catch (Exception $e) {
    $output[] = "Erro: " . $e->getMessage() . "\n";
}

$contents = implode('', $output);
file_put_contents('test_payment_methods.log', $contents);
echo $contents;
?>
