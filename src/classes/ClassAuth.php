<?php
namespace Src\classes;

use App\model\LoginAttempt;
use App\model\User;

class ClassAuth {
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const ATTEMPT_WINDOW_MINUTES = 15;
    private const LOCKOUT_MINUTES = 15;

    private static $lastError = null;

    private static function getClientIp(): string {
        $candidates = [
            (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''),
            (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            $parts = explode(',', $candidate);
            $ip = trim($parts[0]);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    private static function normalizeLogin($login): string {
        return strtolower(trim((string) $login));
    }

    public static function getLastError(): ?string {
        return self::$lastError;
    }

    public static function attempt($login, $password) {
        self::$lastError = null;
        $loginInput = trim((string) $login);
        $normalizedLogin = self::normalizeLogin($loginInput);
        $ipAddress = self::getClientIp();

        if (LoginAttempt::isBlocked(
            $normalizedLogin,
            $ipAddress,
            self::MAX_LOGIN_ATTEMPTS,
            self::ATTEMPT_WINDOW_MINUTES,
            self::LOCKOUT_MINUTES
        )) {
            self::$lastError = 'blocked';
            return false;
        }

        $user = filter_var($loginInput, FILTER_VALIDATE_EMAIL)
            ? User::findByEmail($normalizedLogin)
            : User::findByPhone($loginInput);

        if ($user && !password_verify($password, $user['password'])) {
            LoginAttempt::registerFailure($normalizedLogin, $ipAddress);
            self::$lastError = 'invalid_password';
            return false;
        }

        if ($user && password_verify($password, $user['password'])) {
            if (empty($user['email_verified_at'])) {
                self::$lastError = 'unverified_email';
                return false;
            }

            $status = (string) ($user['status'] ?? '');

            if (UserAccountState::isSuspended($user)) {
                LoginAttempt::registerFailure($normalizedLogin, $ipAddress);
                self::$lastError = 'suspended';
                return false;
            }

            if (!in_array($status, UserAccountState::allowedLoginStatuses(), true)) {
                LoginAttempt::registerFailure($normalizedLogin, $ipAddress);
                self::$lastError = 'inactive';
                return false;
            }

            LoginAttempt::clearAttempts($normalizedLogin);
            ClassSession::set('user_id', $user['id']);
            ClassSession::set('user_email', $user['email']);
            ClassSession::regenerate();
            ClassCsrf::generate();
            return true;
        }

        LoginAttempt::registerFailure($normalizedLogin, $ipAddress);
        self::$lastError = 'invalid_credentials';
        return false;
    }

    public static function check() {
        if (!ClassSession::has('user_id')) {
            return false;
        }
        $user = User::findById(ClassSession::get('user_id'));
        if (!$user) {
            return false;
        }

        $status = (string) ($user['status'] ?? '');
        if (!in_array($status, UserAccountState::allowedLoginStatuses(), true)) {
            return false;
        }

        if (UserAccountState::isSuspended($user)) {
            return false;
        }

        return true;
    }

    public static function user() {
        if (!self::check()) {
            return null;
        }
        return User::findById(ClassSession::get('user_id'));
    }

    public static function logout() {
        ClassSession::remove('user_id');
        ClassSession::remove('user_email');
        ClassSession::destroy();
    }

    public static function requireAuth() {
        if (!self::check()) {
            header('Location: ' . DIRPAGE . 'login');
            exit;
        }
    }

    public static function verifyCurrentPassword(?array $user, string $password): bool {
        if (!is_array($user) || $password === '') {
            return false;
        }

        $hash = (string) ($user['password'] ?? '');

        return $hash !== '' && password_verify($password, $hash);
    }
}