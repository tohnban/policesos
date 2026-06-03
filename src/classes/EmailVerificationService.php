<?php
namespace Src\classes;

use App\model\ManipularBanco;
use App\model\User;

class EmailVerificationService {
    private const TOKEN_TTL_HOURS = 24;

    public static function normalizeEmail(string $email): string {
        return strtolower(trim($email));
    }

    public static function getPendingEmailChange(int $userId): ?array {
        if ($userId <= 0) {
            return null;
        }

        $db = new ManipularBanco();
        $stmt = $db->prepare(
            'SELECT * FROM email_verifications
             WHERE user_id = ?
               AND pending_email IS NOT NULL
               AND TRIM(pending_email) <> ""
               AND expires_at > NOW()
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function cancelPendingEmailChange(int $userId): void {
        if ($userId <= 0) {
            return;
        }

        $db = new ManipularBanco();
        $stmt = $db->prepare(
            'DELETE FROM email_verifications WHERE user_id = ? AND pending_email IS NOT NULL'
        );
        $stmt->execute([$userId]);
    }

    /**
     * @return array{status: string, pending_email?: string}
     */
    public static function requestEmailChange(int $userId, string $newEmail, string $recipientName): array {
        $newEmail = self::normalizeEmail($newEmail);
        if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'invalid'];
        }

        $user = User::findById($userId);
        if (!$user) {
            return ['status' => 'invalid'];
        }

        $currentEmail = self::normalizeEmail((string) ($user['email'] ?? ''));
        if ($newEmail === $currentEmail) {
            self::cancelPendingEmailChange($userId);

            return ['status' => 'unchanged'];
        }

        if (User::findByEmail($newEmail)) {
            return ['status' => 'taken'];
        }

        self::cancelPendingEmailChange($userId);

        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            return ['status' => 'failed'];
        }

        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_TTL_HOURS . ' hours'));
        $db = new ManipularBanco();
        $db->Salvar([
            'user_id'       => $userId,
            'pending_email' => $newEmail,
            'token'         => $token,
            'expires_at'    => $expiresAt,
        ], 'email_verifications');

        $verifyLink = DIRPAGE . 'verify?token=' . $token;
        $subject = 'Confirme o seu novo email – Imobil';
        $body = "Olá {$recipientName},\n\n"
            . "Recebemos um pedido para alterar o email da sua conta para {$newEmail}.\n\n"
            . "Clique no link abaixo para confirmar. Se não foi você, ignore este email — o email actual da conta mantém-se.\n\n"
            . "{$verifyLink}\n\n"
            . "O link é válido por " . self::TOKEN_TTL_HOURS . " horas.";

        ClassMailer::sendQueued(
            $newEmail,
            $recipientName,
            $subject,
            nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')),
            $body
        );

        return ['status' => 'sent', 'pending_email' => $newEmail];
    }

    /**
     * Confirma alteração de email pendente. Devolve o email activado ou null em falha.
     */
    public static function confirmPendingEmailChange(array $verificationRow): ?string {
        $userId = (int) ($verificationRow['user_id'] ?? 0);
        $pendingEmail = self::normalizeEmail((string) ($verificationRow['pending_email'] ?? ''));

        if ($userId <= 0 || $pendingEmail === '') {
            return null;
        }

        if (User::findByEmailExceptId($pendingEmail, $userId)) {
            self::cancelPendingEmailChange($userId);

            return null;
        }

        $db = new ManipularBanco();
        $stmt = $db->prepare(
            'UPDATE users SET email = ?, email_verified_at = NOW() WHERE id = ?'
        );
        if (!$stmt->execute([$pendingEmail, $userId])) {
            return null;
        }

        $del = $db->prepare('DELETE FROM email_verifications WHERE user_id = ?');
        $del->execute([$userId]);

        if (ClassSession::has('user_id') && (int) ClassSession::get('user_id') === $userId) {
            ClassSession::set('user_email', $pendingEmail);
        }

        return $pendingEmail;
    }

    public static function confirmRegistrationEmail(int $userId): bool {
        if ($userId <= 0) {
            return false;
        }

        $db = new ManipularBanco();
        $stmt = $db->prepare(
            'UPDATE users SET email_verified_at = NOW() WHERE id = ? AND email_verified_at IS NULL'
        );
        if (!$stmt->execute([$userId])) {
            return false;
        }

        $del = $db->prepare('DELETE FROM email_verifications WHERE user_id = ?');
        $del->execute([$userId]);

        return true;
    }
}
