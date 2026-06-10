<?php

namespace App\controller;

use App\model\Document;
use App\model\Log;
use App\model\ManipularBanco;
use App\model\User;
use Src\classes\AuthRegisterFeedback;
use Src\classes\ClassAuth;
use Src\classes\ClassCsrf;
use Src\classes\ClassDocumentValidator;
use Src\classes\ClassMailer;
use Src\classes\ClassRateLimiter;
use Src\classes\ClassRender;
use Src\classes\PhoneHelper;

class ControllerAuthRegistration
{
    use AuthRegisterSupport;

    public function store()
    {
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
            'password_confirm' => $_POST['password_confirm'] ?? '',
            'phone'           => PhoneHelper::normalize(trim((string) ($_POST['phone'] ?? ''))),
            'is_affiliate'    => 0,
        ];

        $errors = User::validateData($data);
        if (!empty($errors)) {
            $this->redirectRegisterError(AuthRegisterFeedback::pickPrimaryValidationCode($errors));
        }

        if (empty($_POST['accept_terms'])) {
            $this->redirectRegisterError(AuthRegisterFeedback::TERMS_NOT_ACCEPTED);
        }

        $uploadDir = DIRREQ . 'storage/documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

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
                $mailSent = ClassMailer::sendTransactional($data['email'], $data['name'], $subject, nl2br(htmlspecialchars($body)), $body);

                if (!$mailSent) {
                    Log::create([
                        'user_id' => $userId,
                        'action' => 'auth_register_verification_email_failed',
                        'entity_type' => 'user',
                        'entity_id' => $userId,
                        'details' => json_encode([
                            'email' => (string) ($data['email'] ?? ''),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
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


    public function verify()
    {
        $token = $_GET['token'] ?? '';

        if ($token === '') {
            // Página informativa: "verifique seu email"
            $render = new ClassRender();
            $render->setTitle('Verifique o seu Email');
            $render->setDir('auth/verify');
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

            if (ClassAuth::check()) {
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

}
