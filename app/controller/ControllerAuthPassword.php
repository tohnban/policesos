<?php

namespace App\controller;

use App\model\Log;
use App\model\ManipularBanco;
use App\model\User;
use Src\classes\ClassCsrf;
use Src\classes\ClassMailer;
use Src\classes\ClassRateLimiter;
use Src\classes\ClassRender;

class ControllerAuthPassword
{

    public function recover()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $render = new ClassRender();
            $render->setTitle('Recuperar Conta');
            $render->setDescription('Recupere o acesso por email');
            $render->setKeywords('recuperar, senha, email');
            $render->setDir('auth/recover');
            $render->renderLayout();
            return;
        }

        ClassRateLimiter::enforceScope('auth_recover', 'rate_limit_auth_recover_max', 'rate_limit_auth_recover_window_seconds', 5, 300);

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::generate();
            header('Location: ' . DIRPAGE . 'recover?error=' . urlencode('Token inválido. Tente novamente.'));
            exit;
        }

        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . DIRPAGE . 'recover?error=' . urlencode('Email inválido.'));
            exit;
        }

        $user = User::findByEmail($email);
        if (!$user) {
            header('Location: ' . DIRPAGE . 'recover?success=1');
            exit;
        }

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $db      = new ManipularBanco();
        $db->Salvar([
            'user_id'    => $user['id'],
            'token'      => $token,
            'expires_at' => $expires,
        ], 'password_resets');

        $resetLink = DIRPAGE . 'reset?token=' . $token;
        $subject   = 'Recuperação de senha - Imobil';
        $body      = "Olá,\n\nRecebemos uma solicitação para redefinir sua senha. Clique no link abaixo para criar uma nova senha:\n$resetLink\n\nSe não foi você, ignore este email.";
        ClassMailer::sendTransactional($email, $user['name'] ?? '', $subject, nl2br($body), $body);

        Log::create([
            'user_id' => (int) ($user['id'] ?? 0),
            'action' => 'auth_password_recovery_requested',
            'entity_type' => 'auth',
            'entity_id' => (int) ($user['id'] ?? 0),
            'details' => json_encode([
                'email' => (string) $email,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        header('Location: ' . DIRPAGE . 'recover?success=1');
        exit;
    }


    public function reset()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $render = new ClassRender();
            $render->setTitle('Redefinir Senha');
            $render->setDescription('Crie uma nova senha');
            $render->setKeywords('reset, senha, redefinir');
            $render->setDir('auth/reset');
            $render->renderLayout();
            return;
        }

        ClassRateLimiter::enforceScope('auth_reset', 'rate_limit_auth_reset_max', 'rate_limit_auth_reset_window_seconds', 10, 600);

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::generate();
            header('Location: ' . DIRPAGE . 'reset?error=' . urlencode('Token inválido. Tente novamente.'));
            exit;
        }

        $token            = $_POST['token'] ?? '';
        $password         = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($token) || empty($password) || empty($password_confirm)) {
            header('Location: ' . DIRPAGE . 'reset?error=' . urlencode('Preencha todos os campos.') . '&token=' . urlencode($token));
            exit;
        }
        if ($password !== $password_confirm) {
            header('Location: ' . DIRPAGE . 'reset?error=' . urlencode('As senhas não coincidem.') . '&token=' . urlencode($token));
            exit;
        }
        if (strlen($password) < 6) {
            header('Location: ' . DIRPAGE . 'reset?error=' . urlencode('A senha deve ter pelo menos 6 caracteres.') . '&token=' . urlencode($token));
            exit;
        }

        $db   = new ManipularBanco();
        $stmt = $db->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()');
        $stmt->execute([$token]);
        $reset = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$reset) {
            header('Location: ' . DIRPAGE . 'reset?error=' . urlencode('Token inválido ou expirado.'));
            exit;
        }

        $user = User::findById($reset['user_id']);
        if (!$user) {
            header('Location: ' . DIRPAGE . 'reset?error=' . urlencode('Usuário não encontrado.'));
            exit;
        }

        $hashed   = password_hash($password, PASSWORD_DEFAULT);
        $upStmt   = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
        $upStmt->execute([$hashed, $user['id']]);

        $delStmt = $db->prepare('DELETE FROM password_resets WHERE user_id = ?');
        $delStmt->execute([$user['id']]);

        Log::create([
            'user_id' => (int) ($user['id'] ?? 0),
            'action' => 'auth_password_reset_success',
            'entity_type' => 'auth',
            'entity_id' => (int) ($user['id'] ?? 0),
            'details' => json_encode([
                'result' => 'success',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        header('Location: ' . DIRPAGE . 'reset?success=1');
        exit;
    }

}
