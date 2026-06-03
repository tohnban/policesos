<?php
namespace Src\classes;

use App\model\BackgroundJob;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ClassMailer {
    public static function isEnabled(): bool {
        return defined('EMAIL_ENABLED') && filter_var(EMAIL_ENABLED, FILTER_VALIDATE_BOOLEAN);
    }

    public static function isQueueEnabled(): bool {
        return ClassSettings::int('mail_queue_enabled', 1) === 1;
    }

    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool {
        if (!self::isEnabled()) {
            return false;
        }

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
            $mail->addAddress($toEmail, $toName ?: $toEmail);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody ?: trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $htmlBody)));
            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }

    public static function sendQueued(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool {
        if (!self::isEnabled()) {
            return false;
        }

        if (!self::isQueueEnabled()) {
            return self::send($toEmail, $toName, $subject, $htmlBody, $textBody);
        }

        try {
            $maxAttempts = max(1, ClassSettings::int('mail_queue_max_attempts', 5));
            $jobId = BackgroundJob::enqueue('mail', [
                'to_email' => $toEmail,
                'to_name' => $toName,
                'subject' => $subject,
                'html_body' => $htmlBody,
                'text_body' => $textBody,
            ], 7, $maxAttempts);

            if ($jobId > 0) {
                return true;
            }
        } catch (\Throwable $e) {
            // Fallback para envio síncrono quando a fila falha.
        }

        return self::send($toEmail, $toName, $subject, $htmlBody, $textBody);
    }

    public static function sendNotification(string $toEmail, string $toName, string $title, string $message): bool {
        $subject = '[Imobil] ' . $title;
        $htmlBody = '<h2 style="margin:0 0 12px; color:#10203a;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<p style="margin:0; color:#334; line-height:1.6;">' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>';
        return self::sendQueued($toEmail, $toName, $subject, $htmlBody, $message);
    }
}