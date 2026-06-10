<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only." . PHP_EOL;
    exit(1);
}

$rootDir = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $rootDir;
$_SERVER['HTTP_HOST'] = 'localhost';

require_once $rootDir . '/src/vendor/autoload.php';
require_once $rootDir . '/config/config.php';

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

$to = $argv[1] ?? MAIL_FROM_ADDRESS;
$subject = 'Teste SMTP Imobil — ' . date('Y-m-d H:i:s');
$htmlBody = '<p>Este é um email de teste enviado pelo script <code>send_test_email.php</code>.</p>'
    . '<p>Se recebeu esta mensagem, o SMTP da Hostinger está configurado correctamente.</p>';
$textBody = "Teste SMTP Imobil.\nSe recebeu esta mensagem, o SMTP está OK.";

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->Port = (int) SMTP_PORT;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    if (defined('SMTP_SECURE') && SMTP_SECURE) {
        $mail->SMTPSecure = SMTP_SECURE;
    }
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    $mail->addAddress($to, $to);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $htmlBody;
    $mail->AltBody = $textBody;
    $mail->send();

    echo json_encode([
        'ok' => true,
        'to' => $to,
        'from' => MAIL_FROM_ADDRESS,
        'subject' => $subject,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
} catch (MailException $e) {
    echo json_encode([
        'ok' => false,
        'error' => $mail->ErrorInfo,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(1);
}
