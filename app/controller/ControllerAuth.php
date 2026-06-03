<?php
namespace App\controller;

use Src\classes\ClassRender;
use Src\classes\ClassAuth;
use Src\classes\ClassAccess;
use Src\classes\ClassCsrf;
use Src\classes\ClassRateLimiter;
use Src\classes\ClassDocumentValidator;
use Src\classes\ClassMailer;
use Src\classes\PhoneHelper;
use Src\classes\AuthRegisterFeedback;
use App\model\User;
use App\model\Document;
use App\model\ManipularBanco;
use App\model\Log;

class ControllerAuth {

    private function redirectRegisterError(string $code): void {
        $safeCode = AuthRegisterFeedback::isKnownCode($code) ? $code : AuthRegisterFeedback::CREATE_FAILED;
        header('Location: ' . DIRPAGE . 'register?error=' . urlencode($safeCode));
        exit;
    }

    private function processProfilePhotoUpload(?array $profilePhoto, int $userId): array {
        if (!$profilePhoto || (int) ($profilePhoto['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => null];
        }

        $errorCode = (int) ($profilePhoto['error'] ?? UPLOAD_ERR_OK);
        if ($errorCode !== UPLOAD_ERR_OK) {
            $errorMap = [
                UPLOAD_ERR_INI_SIZE => 'A foto de perfil excede o limite do servidor.',
                UPLOAD_ERR_FORM_SIZE => 'A foto de perfil excede o limite permitido no formulário.',
                UPLOAD_ERR_PARTIAL => 'A foto de perfil foi enviada parcialmente.',
                UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária indisponível para a foto de perfil.',
                UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar a foto de perfil no disco.',
                UPLOAD_ERR_EXTENSION => 'Upload da foto de perfil bloqueado pelo servidor.',
            ];

            return ['path' => null, 'error' => $errorMap[$errorCode] ?? 'Erro ao enviar a foto de perfil.'];
        }

        $tmpName = (string) ($profilePhoto['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['path' => null, 'error' => 'Arquivo de foto de perfil inválido.'];
        }

        $maxBytes = 512 * 1024;
        if ((int) ($profilePhoto['size'] ?? 0) > $maxBytes) {
            return ['path' => null, 'error' => 'A foto de perfil deve ter no máximo 512 KB.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo ? (string) finfo_file($finfo, $tmpName) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMime = [
            'image/jpeg' => 'jpg',
        ];

        if (!isset($allowedMime[$detectedMime])) {
            return ['path' => null, 'error' => 'Formato de foto inválido. A foto final deve estar em JPG.'];
        }

        $uploadDir = rtrim(DIRREQ, '/\\') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR;
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return ['path' => null, 'error' => 'Não foi possível preparar a pasta da foto de perfil.'];
        }

        try {
            $suffix = bin2hex(random_bytes(6));
        } catch (\Throwable $e) {
            $suffix = substr(md5(uniqid('', true)), 0, 12);
        }
        $filename = 'profile_' . $userId . '_' . time() . '_' . $suffix . '.' . $allowedMime[$detectedMime];
        $targetPath = $uploadDir . $filename;
        if (!move_uploaded_file($tmpName, $targetPath)) {
            return ['path' => null, 'error' => 'Falha ao salvar a foto de perfil.'];
        }

        return ['path' => DIRPAGE . 'storage/uploads/profiles/' . $filename, 'error' => null];
    }

    public function login() {
        if (ClassAuth::check()) {
            $user = ClassAuth::user();
            $redirectTo = ClassAccess::canUseAccountStatusPage($user)
                ? DIRPAGE . 'dashboard/accountStatus'
                : DIRPAGE . 'dashboard';
            header('Location: ' . $redirectTo);
            exit;
        }

        $render = new ClassRender();
        $render->setTitle("Entrar na Imobil Fácil");
        $render->setDescription("Aceda à sua conta Imobil Fácil com email ou telefone.");
        $render->setKeywords("login, entrar, conta, imobil");
        $render->setDir("auth/login");
        $render->renderLayout();
    }

    public function register() {
        if (ClassAuth::check()) {
            $user = ClassAuth::user();
            $redirectTo = ClassAccess::canUseAccountStatusPage($user)
                ? DIRPAGE . 'dashboard/accountStatus'
                : DIRPAGE . 'dashboard';
            header('Location: ' . $redirectTo);
            exit;
        }

        $render = new ClassRender();
        $render->setTitle('Criar conta na Imobil Fácil');
        $render->setDescription('Registe-se na Imobil Fácil para explorar imóveis, solicitar visitas e gerir o seu perfil.');
        $render->setKeywords('registar, criar conta, imobil, imóveis');
        $render->setDir('auth/register');
        $render->renderLayout();
    }

    public function authenticate() {
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
                : DIRPAGE . 'dashboard';
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

    public function logout() {
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

    public function store() {
        ClassRateLimiter::enforceScope('auth_register', 'rate_limit_auth_register_max', 'rate_limit_auth_register_window_seconds', 5, 300);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'register');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::generate();
            $this->redirectRegisterError(AuthRegisterFeedback::CSRF_INVALID);
        }

        $affiliateInterest = isset($_POST['affiliate_interest']);

        $data = [
            'name'            => trim((string) ($_POST['name'] ?? '')),
            'user_type'       => trim((string) ($_POST['user_type'] ?? '')),
            'document_number' => trim((string) ($_POST['document_number'] ?? '')),
            'email'           => strtolower(trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL))),
            'password'        => $_POST['password'] ?? '',
            'password_confirm'=> $_POST['password_confirm'] ?? '',
            'phone'           => PhoneHelper::normalize(trim((string) ($_POST['phone'] ?? ''))),
            'is_affiliate'    => 0,
        ];

        $errors = User::validateData($data);
        if (!empty($errors)) {
            $this->redirectRegisterError(AuthRegisterFeedback::pickPrimaryValidationCode($errors));
        }

        $uploadDir = DIRREQ . 'storage/documents/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $docDocument = '';

        if (!isset($_FILES['document_file']) || (int) ($_FILES['document_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->redirectRegisterError(AuthRegisterFeedback::DOCUMENT_FILE_REQUIRED);
        }

        $validation = ClassDocumentValidator::validateFile(
            $_FILES['document_file'],
            ClassDocumentValidator::TYPE_USER_REGISTRATION
        );

        if (!$validation['valid']) {
            $this->redirectRegisterError(AuthRegisterFeedback::DOCUMENT_FILE_INVALID);
        }

        $tmpPath      = (string) ($_FILES['document_file']['tmp_name'] ?? '');
        $originalName = (string) ($_FILES['document_file']['name'] ?? '');
        $filename     = ClassDocumentValidator::generateFilename($originalName, 'v1');

        if (!move_uploaded_file($tmpPath, $uploadDir . $filename)) {
            $this->redirectRegisterError(AuthRegisterFeedback::DOCUMENT_SAVE_FAILED);
        }

        $docDocument = $filename;
        $data['document_file'] = $docDocument;

        if (User::create($data)) {
            $createdUser = User::findByEmail($data['email']);
            $userId = (int) ($createdUser['id'] ?? 0);

            Log::create([
                'user_id' => $userId > 0 ? $userId : null,
                'action' => 'auth_register_success',
                'entity_type' => 'user',
                'entity_id' => $userId > 0 ? $userId : null,
                'details' => json_encode([
                    'email' => (string) ($data['email'] ?? ''),
                    'user_type' => (string) ($data['user_type'] ?? ''),
                    'affiliate_interest' => $affiliateInterest,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            if ($affiliateInterest && $userId > 0) {
                Log::create([
                    'user_id' => $userId,
                    'action' => 'affiliate_interest_registration',
                    'entity_type' => 'user',
                    'entity_id' => $userId,
                    'details' => json_encode([
                        'message' => 'Interesse em perfil de parceiro/afiliado indicado no registo',
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }

            if ($userId > 0) {
                $photoUpload = $this->processProfilePhotoUpload($_FILES['profile_photo'] ?? null, $userId);
                if (!empty($photoUpload['error'])) {
                    $this->redirectRegisterError(AuthRegisterFeedback::PROFILE_PHOTO_INVALID);
                }

                if (!empty($photoUpload['path'])) {
                    User::updateProfile($userId, ['profile_photo' => $photoUpload['path']]);
                }
            }

            if (!empty($docDocument) && $userId > 0) {
                Document::create(
                    $userId,
                    null,
                    ClassDocumentValidator::TYPE_USER_REGISTRATION,
                    $docDocument
                );
            }

            // Enviar email de verificação
            if ($userId > 0) {
                $vToken   = bin2hex(random_bytes(32));
                $vExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $db = new ManipularBanco();
                $db->Salvar([
                    'user_id'    => $userId,
                    'token'      => $vToken,
                    'expires_at' => $vExpires,
                ], 'email_verifications');

                $verifyLink = DIRPAGE . 'verify?token=' . $vToken;
                $subject    = 'Confirme o seu email – Imobil';
                $body       = "Olá {$data['name']},\n\nClique no link abaixo para confirmar o seu email:\n$verifyLink\n\nO link é válido por 24 horas.";
                ClassMailer::sendQueued($data['email'], $data['name'], $subject, nl2br(htmlspecialchars($body)), $body);
            }

            header('Location: ' . DIRPAGE . 'verify');
            exit;
        } else {
            Log::create([
                'user_id' => null,
                'action' => 'auth_register_failed',
                'entity_type' => 'user',
                'entity_id' => null,
                'details' => json_encode([
                    'email' => (string) ($data['email'] ?? ''),
                    'reason' => 'create_failed',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            $this->redirectRegisterError(AuthRegisterFeedback::CREATE_FAILED);
        }
    }

    public function verify() {
        $token = $_GET['token'] ?? '';

        if ($token === '') {
            // Página informativa: "verifique seu email"
            $render = new ClassRender();
            $render->setTitle("Verifique o seu Email");
            $render->setDir("auth/verify");
            $render->renderLayout();
            return;
        }

        $db   = new ManipularBanco();
        $stmt = $db->prepare('SELECT * FROM email_verifications WHERE token = ? AND expires_at > NOW()');
        $stmt->execute([$token]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            header('Location: ' . DIRPAGE . 'verify?error=' . urlencode('Link inválido ou expirado.'));
            exit;
        }

        $pendingEmail = trim((string) ($row['pending_email'] ?? ''));
        if ($pendingEmail !== '') {
            $confirmed = \Src\classes\EmailVerificationService::confirmPendingEmailChange($row);
            if ($confirmed === null) {
                header('Location: ' . DIRPAGE . 'verify?error=' . urlencode(
                    'Não foi possível confirmar o novo email. O endereço pode já estar em uso ou o pedido expirou.'
                ));
                exit;
            }

            if (Src\classes\ClassAuth::check()) {
                header('Location: ' . DIRPAGE . 'profile?success=' . rawurlencode(
                    'Email actualizado para ' . $confirmed . '.'
                ));
                exit;
            }

            header('Location: ' . DIRPAGE . 'verify?success=email_change');
            exit;
        }

        \Src\classes\EmailVerificationService::confirmRegistrationEmail((int) $row['user_id']);

        header('Location: ' . DIRPAGE . 'verify?success=1');
        exit;
    }

    public function recover() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $render = new ClassRender();
            $render->setTitle("Recuperar Conta");
            $render->setDescription("Recupere o acesso por email");
            $render->setKeywords("recuperar, senha, email");
            $render->setDir("auth/recover");
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
            'expires_at' => $expires
        ], 'password_resets');

        $resetLink = DIRPAGE . 'reset?token=' . $token;
        $subject   = 'Recuperação de senha - Imobil';
        $body      = "Olá,\n\nRecebemos uma solicitação para redefinir sua senha. Clique no link abaixo para criar uma nova senha:\n$resetLink\n\nSe não foi você, ignore este email.";
        ClassMailer::sendQueued($email, $user['name'] ?? '', $subject, nl2br($body), $body);

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

    public function reset() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $render = new ClassRender();
            $render->setTitle("Redefinir Senha");
            $render->setDescription("Crie uma nova senha");
            $render->setKeywords("reset, senha, redefinir");
            $render->setDir("auth/reset");
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
