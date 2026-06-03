<?php
namespace Src\classes;

class UserDisplay {
    public static function legalName(array $user): string {
        return trim((string) ($user['name'] ?? ''));
    }

    public static function username(array $user): string {
        return trim((string) ($user['username'] ?? ''));
    }

    public static function publicLabel(array $user): string {
        return UsernameHelper::publicLabel($user);
    }

    public static function handleWithAt(array $user): string {
        $username = self::username($user);

        return $username !== '' ? '@' . $username : self::publicLabel($user);
    }

    /** Rótulo público a partir de colunas SQL (username + nome legal opcional). */
    public static function publicHandleFromRow(
        array $row,
        string $usernameKey,
        string $nameKey = '',
        string $fallback = '-'
    ): string {
        $username = trim((string) ($row[$usernameKey] ?? ''));
        if ($username !== '') {
            return '@' . $username;
        }

        if ($nameKey !== '') {
            $name = trim((string) ($row[$nameKey] ?? ''));
            if ($name !== '') {
                return self::publicLabel(['name' => $name]);
            }
        }

        return $fallback;
    }
}
