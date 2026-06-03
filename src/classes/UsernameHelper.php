<?php
namespace Src\classes;

use App\model\User;

class UsernameHelper {
    public const MIN_LENGTH = 3;
    public const MAX_LENGTH = 32;
    public const CHANGE_COOLDOWN_DAYS = 90;

    private const RESERVED = [
        'admin', 'administrador', 'suporte', 'support', 'moderador', 'mod',
        'financeiro', 'imobil', 'imobilfacil', 'sistema', 'system', 'root',
        'api', 'www', 'mail', 'help', 'ajuda', 'null', 'undefined',
    ];

    public static function normalize(string $username): string {
        return strtolower(trim($username));
    }

    public static function slugFromName(string $name): string {
        $name = trim($name);
        if ($name === '') {
            return 'utilizador';
        }

        if (function_exists('transliterator_transliterate')) {
            $name = transliterator_transliterate(
                'Any-Latin; Latin-ASCII; Lower()',
                $name
            );
        } else {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
            if ($converted !== false) {
                $name = $converted;
            }
            $name = strtolower($name);
        }

        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
        $slug = trim((string) $slug, '-');
        $slug = preg_replace('/-+/', '-', $slug);

        if ($slug === '') {
            return 'utilizador';
        }

        if (strlen($slug) > self::MAX_LENGTH) {
            $slug = substr($slug, 0, self::MAX_LENGTH);
            $slug = rtrim($slug, '-');
        }

        if (strlen($slug) < self::MIN_LENGTH) {
            $slug = str_pad($slug, self::MIN_LENGTH, '0');
        }

        return $slug;
    }

    public static function validate(string $username): ?string {
        $username = self::normalize($username);

        if ($username === '') {
            return 'username_required';
        }
        if (strlen($username) < self::MIN_LENGTH) {
            return 'username_too_short';
        }
        if (strlen($username) > self::MAX_LENGTH) {
            return 'username_too_long';
        }
        if (!preg_match('/^[a-z0-9][a-z0-9._-]*[a-z0-9]$/', $username) && !preg_match('/^[a-z0-9]{3}$/', $username)) {
            return 'username_invalid_format';
        }
        if (self::isReserved($username)) {
            return 'username_reserved';
        }

        return null;
    }

    public static function isReserved(string $username): bool {
        $username = self::normalize($username);

        return in_array($username, self::RESERVED, true)
            || str_starts_with($username, 'admin')
            || str_starts_with($username, 'u') && preg_match('/^u\d+$/', $username);
    }

    public static function generateUniqueFromName(string $name, ?int $excludeUserId = null): string {
        $base = self::slugFromName($name);
        if (self::validate($base) === 'username_reserved') {
            $base = 'utilizador';
        }

        $candidate = $base;
        $suffix = 0;

        while (self::isTaken($candidate, $excludeUserId)) {
            $suffix++;
            $suffixPart = (string) $suffix;
            $maxBase = self::MAX_LENGTH - strlen($suffixPart) - 1;
            if ($maxBase < self::MIN_LENGTH) {
                $maxBase = self::MIN_LENGTH;
            }
            $trimmedBase = substr($base, 0, $maxBase);
            $trimmedBase = rtrim($trimmedBase, '-');
            if ($trimmedBase === '') {
                $trimmedBase = 'user';
            }
            $candidate = $trimmedBase . '-' . $suffixPart;
        }

        return $candidate;
    }

    public static function isTaken(string $username, ?int $exceptUserId = null): bool {
        $username = self::normalize($username);
        if ($username === '') {
            return true;
        }

        if ($exceptUserId !== null && $exceptUserId > 0) {
            return User::findByUsernameExceptId($username, $exceptUserId) !== null;
        }

        return User::findByUsername($username) !== null;
    }

    /** Primeira alteração manual: permitida; seguintes: 90 dias após username_changed_at. */
    public static function canChangeUsername(array $user): bool {
        $changedAt = $user['username_changed_at'] ?? null;
        if ($changedAt === null || $changedAt === '') {
            return true;
        }

        $nextEligible = strtotime((string) $changedAt . ' +' . self::CHANGE_COOLDOWN_DAYS . ' days');

        return $nextEligible !== false && time() >= $nextEligible;
    }

    public static function nextChangeEligibleAt(array $user): ?\DateTimeImmutable {
        $changedAt = $user['username_changed_at'] ?? null;
        if ($changedAt === null || $changedAt === '') {
            return null;
        }

        $ts = strtotime((string) $changedAt . ' +' . self::CHANGE_COOLDOWN_DAYS . ' days');
        if ($ts === false) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp($ts);
    }

    public static function profileErrorMessage(string $code): string {
        $messages = [
            'username_required' => 'Indique o nome de utilizador.',
            'username_too_short' => 'O nome de utilizador deve ter pelo menos ' . self::MIN_LENGTH . ' caracteres.',
            'username_too_long' => 'O nome de utilizador não pode exceder ' . self::MAX_LENGTH . ' caracteres.',
            'username_invalid_format' => 'Use apenas letras minúsculas, números, pontos, hífens e underscores. Deve começar e terminar com letra ou número.',
            'username_reserved' => 'Este nome de utilizador não está disponível.',
            'username_taken' => 'Este nome de utilizador já está em uso.',
            'username_cooldown' => 'Só pode alterar o nome de utilizador novamente 90 dias após a última alteração.',
            'username_locked' => 'O nome de utilizador não pode ser alterado neste momento.',
        ];

        return $messages[$code] ?? 'Não foi possível actualizar o nome de utilizador.';
    }

    public static function publicLabel(array $user): string {
        $username = trim((string) ($user['username'] ?? ''));
        if ($username !== '') {
            return $username;
        }

        $rawName = trim((string) ($user['name'] ?? 'Utilizador'));
        $nameParts = preg_split('/\s+/', $rawName, -1, PREG_SPLIT_NO_EMPTY);

        if (!empty($nameParts)) {
            return implode(' ', array_slice($nameParts, 0, 2));
        }

        return $rawName;
    }
}
