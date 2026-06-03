<?php
// Quick check for payment methods
require_once 'config/config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT * FROM payment_methods");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total de métodos: " . count($methods) . "\n";
    
    if (empty($methods)) {
        echo "Tabela vazia! Inserindo dados...\n";
        
        $insert = $pdo->prepare("INSERT INTO payment_methods (name, code, direction, audience, requires_reference, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        
        $methods_to_insert = [
            ['Transferência Bancária', 'bank_transfer', 'both', 'both', 1, 1],
            ['Multicaixa Express', 'multicaixa_express', 'both', 'both', 1, 1],
            ['Carteira Móvel', 'mobile_wallet', 'both', 'both', 1, 1],
            ['Dinheiro', 'cash', 'both', 'both', 0, 1],
        ];
        
        foreach ($methods_to_insert as $method) {
            $insert->execute($method);
            echo "Inserido: " . $method[0] . "\n";
        }
        
        // Verify
        $stmt = $pdo->query("SELECT * FROM payment_methods");
        $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\nMétodos após inserção: " . count($methods) . "\n";
    }
    
    echo "\nMétodos no banco:\n";
    foreach ($methods as $m) {
        echo "- {$m['name']} ({$m['code']}): {$m['direction']}, público: {$m['audience']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
