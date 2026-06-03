<?php
namespace App\model;

class LoginAttempt extends ManipularBanco {
    protected $table = 'login_attempts';

    public static function registerFailure(string $loginIdentifier, string $ipAddress): bool {
        $db = new self();
        $sql = "INSERT INTO {$db->table} (login_identifier, ip_address, attempted_at) VALUES (?, ?, NOW())";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$loginIdentifier, $ipAddress]);
    }

    public static function clearAttempts(string $loginIdentifier): bool {
        $db = new self();
        $sql = "DELETE FROM {$db->table} WHERE login_identifier = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$loginIdentifier]);
    }

    public static function isBlocked(
        string $loginIdentifier,
        string $ipAddress,
        int $maxAttempts = 5,
        int $windowMinutes = 15,
        int $lockoutMinutes = 15
    ): bool {
        $db = new self();
        $sql = "SELECT attempted_at
                FROM {$db->table}
                WHERE (login_identifier = ? OR ip_address = ?)
                  AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
                ORDER BY attempted_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$loginIdentifier, $ipAddress, $windowMinutes]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (count($rows) < $maxAttempts) {
            return false;
        }

        $latestAttemptAt = (string) ($rows[0]['attempted_at'] ?? '');
        if ($latestAttemptAt === '') {
            return false;
        }

        $latestTs = strtotime($latestAttemptAt);
        if ($latestTs === false) {
            return false;
        }

        $lockoutUntilTs = strtotime('+' . $lockoutMinutes . ' minutes', $latestTs);
        if ($lockoutUntilTs === false) {
            return false;
        }

        return time() < $lockoutUntilTs;
    }
}
