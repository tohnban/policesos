<?php
require_once 'config/config.php';

try {
    $pdo = new PDO('mysql:host=' . HOST . ';dbname=' . DB, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM payment_methods');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Total de métodos: ' . $result['total'] . PHP_EOL;
    
    if ($result['total'] == 0) {
        echo 'Inserindo dados...' . PHP_EOL;
        $insert = $pdo->prepare('INSERT INTO payment_methods (code, name, direction, audience, requires_reference, is_active) VALUES (?, ?, ?, ?, ?, ?)');
        $methods = [
            ['bank_transfer', 'Transferencia Bancaria', 'both', 'both', 1, 1],
            ['multicaixa_express', 'Multicaixa Express', 'both', 'both', 1, 1],
            ['mobile_wallet', 'Carteira Movel', 'both', 'both', 1, 1],
            ['cash', 'Numerario', 'incoming', 'system', 0, 1],
        ];
        foreach ($methods as $m) {
            $insert->execute($m);
            echo '  ✓ ' . $m[1] . PHP_EOL;
        }
        echo 'Dados inseridos com sucesso!' . PHP_EOL;
    }
    
    $stmt = $pdo->query('SELECT * FROM payment_methods ORDER BY id');
    echo PHP_EOL . 'Métodos:' . PHP_EOL;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '  - ' . $row['name'] . ' (' . $row['code'] . ')' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}
?>
