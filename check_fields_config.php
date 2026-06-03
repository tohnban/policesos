<?php
require_once 'config/config.php';

$out = [];
try {
    $pdo = new PDO('mysql:host=' . HOST . ';dbname=' . DB, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT id, code, name, fields_config FROM payment_methods ORDER BY id");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out[] = 'Total: ' . count($rows);
    foreach ($rows as $r) {
        $out[] = sprintf('[%d] %s (%s) => %s', (int)$r['id'], $r['name'], $r['code'], (string)$r['fields_config']);
    }
} catch (Exception $e) {
    $out[] = 'Erro: ' . $e->getMessage();
}

file_put_contents('check_fields_config.log', implode(PHP_EOL, $out));
echo nl2br(htmlspecialchars(implode(PHP_EOL, $out)));
