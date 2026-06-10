<?php

namespace App\controller;

use App\model\Log;
use Src\classes\ClassAccess;
use Src\classes\ClassAuth;
use Src\classes\ClassCsrf;
use Src\classes\ClassRateLimiter;

class ControllerAuthSession
{

    public function authenticate()
    {
        ClassRateLimiter::enforceScope('auth_login', 'rate_limit_auth_login_max', 'rate_limit_auth_login_window_seconds', 10, 60);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'login');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::generate();
            header('Location: ' . DIRPAGE . 'login?error=' . urlencode('Token inválido. Tente novamente.'));
            exit;
        }

        $login    = $_POST['login'] ?? '';
        $password = $_POST['password'] ?? '';

        if (ClassAuth::attempt($login, $password)) {
            $user = ClassAuth::user();
            Log::create([
                'user_id' => (int) ($user['id'] ?? 0),
                'action' => 'auth_login_success',
                'entity_type' => 'auth',
                'entity_id' => (int) ($user['id'] ?? 0),
                'details' => json_encode([
                    'login_identifier' => (string) $login,
                    'result' => 'success',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            $redirectTo = ClassAccess::canUseAccountStatusPage($user)
                ? DIRPAGE . 'dashboard/accountStatus'
                : DIRPAGE;
            header('Location: ' . $redirectTo);
            exit;
        } else {
            $lastError = ClassAuth::getLastError();
            Log::create([
                'user_id' => null,
                'action' => 'auth_login_failed',
                'entity_type' => 'auth',
                'entity_id' => null,
                'details' => json_encode([
                    'login_identifier' => (string) $login,
                    'result' => 'failed',
                    'reason' => (string) ($lastError ?? 'unknown'),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            $knownErrors = [
                'blocked',
                'unverified_email',
                'account_rejected',
                'suspended',
                'inactive',
                'invalid_password',
                'invalid_credentials',
            ];
            $errorCode = in_array($lastError, $knownErrors, true) ? $lastError : 'invalid_credentials';
            header('Location: ' . DIRPAGE . 'login?error=' . urlencode($errorCode));
            exit;
        }
    }


    public function logout()
    {
        $user = ClassAuth::user();
        if ($user) {
            Log::create([
                'user_id' => (int) ($user['id'] ?? 0),
                'action' => 'auth_logout',
                'entity_type' => 'auth',
                'entity_id' => (int) ($user['id'] ?? 0),
                'details' => json_encode([
                    'result' => 'success',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        }

        ClassAuth::logout();
        header('Location: ' . DIRPAGE);
        exit;
    }

}
