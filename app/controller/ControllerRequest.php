<?php
namespace App\controller;

use Src\classes\ClassRender;
use Src\classes\ClassAuth;
use Src\classes\ClassAccess;
use Src\classes\ClassCsrf;
use Src\classes\ClassSession;
use Src\classes\ClassCookieConsent;
use App\model\Request;
use App\model\Commission;
use App\model\Log;
use App\model\Property;
use App\model\PropertyAffiliate;
use App\model\Notification;
use App\model\User;
use App\model\RequestChatThread;
use App\model\RequestChatMessage;
use App\model\PropertyBehaviorEvent;
use Src\classes\ClassCommissionGuard;

class ControllerRequest {
    private function resolvePropertyFinalStatus(array $property): ?string {
        $purpose = (string) ($property['purpose'] ?? '');
        if ($purpose === 'venda') {
            return 'vendido';
        }
        if (strpos($purpose, 'aluguer') === 0) {
            return 'alugado';
        }

        return null;
    }

    private function isPropertyCommerciallyClosed(array $property): bool {
        return in_array((string) ($property['status'] ?? ''), ['vendido', 'alugado'], true);
    }

    private function noteLength(string $text): int {
        if (function_exists('mb_strlen')) {
            return (int) mb_strlen($text);
        }

        return strlen($text);
    }

    private function appendChatSystemMessage(int $requestId, int $actorId, string $message, ?string $attachmentPath = null): void {
        if (trim($message) === '') {
            return;
        }

        try {
            RequestChatMessage::createSystemForRequest($requestId, $actorId, $message, $attachmentPath);
        } catch (\Throwable $e) {
            // Chat system messages must not break the request flow.
        }
    }

    private function systemMessageForStatusChange(string $status, array $request, array $property, array $actor, ?string $note = null): string {
        $actorName = trim((string) ($actor['name'] ?? 'Utilizador'));
        $note = trim((string) $note);

        if ($status === 'fechado_ganho') {
            $message = $actorName . ' marcou a solicitação como fecho ganho.';
        } elseif ($status === 'cancelado') {
            $message = $actorName . ' encerrou a solicitação como cancelada.';
        } elseif ($status === 'em_disputa') {
            $message = $actorName . ' enviou a solicitação para disputa.';
        } else {
            $message = $actorName . ' atualizou o estado da solicitação para ' . Request::statusLabel($status) . '.';
        }

        if ($note !== '') {
            $message .= ' Observação: ' . $note;
        }

        return $message;
    }

    private function processRequestImageUpload(array $file, int $userId): array {
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => null];
        }

        $errorMap = [
            UPLOAD_ERR_INI_SIZE => 'O arquivo excede o limite do servidor.',
            UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o limite permitido no formulário.',
            UPLOAD_ERR_PARTIAL => 'O arquivo foi enviado parcialmente.',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária de upload indisponível.',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar arquivo no disco.',
            UPLOAD_ERR_EXTENSION => 'Upload de arquivo bloqueado pelo servidor.',
        ];

        if ($errorCode !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => $errorMap[$errorCode] ?? 'Erro ao enviar arquivo.'];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['path' => null, 'error' => 'Arquivo inválido.'];
        }

        if ($size <= 0 || $size > (512 * 1024)) {
            return ['path' => null, 'error' => 'O arquivo deve ter até 512 KB.'];
        }

        $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string) finfo_file($finfo, $tmpName) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!in_array($mime, $allowedMime, true)) {
            return ['path' => null, 'error' => 'Formato inválido. Use JPG, PNG, WebP ou GIF.'];
        }

        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        $ext = $extMap[$mime] ?? 'jpg';

        $uploadDirRelative = 'public/storage/uploads/request_chat_attachments/';
        $uploadDir = DIRREQ . $uploadDirRelative;
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return ['path' => null, 'error' => 'Não foi possível preparar a pasta para anexos.'];
        }

        try {
            $suffix = bin2hex(random_bytes(4));
        } catch (\Exception $e) {
            $suffix = substr(md5(uniqid('', true)), 0, 8);
        }

        $filename = 'chat_' . max(0, $userId) . '_' . time() . '_' . $suffix . '.' . $ext;
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            return ['path' => null, 'error' => 'Falha ao salvar o arquivo.'];
        }

        return ['path' => $uploadDirRelative . $filename, 'error' => null];
    }

    private function processMessageAttachmentUpload(array $file, int $userId): array {
        return $this->processRequestImageUpload($file, $userId);
    }

    private function processActionImageUpload(array $file, int $userId): array {
        return $this->processRequestImageUpload($file, $userId);
    }

    private function collectActionContext(bool $noteRequired = false, bool $imageRequired = false): array {
        $note = trim((string) ($_POST['action_note'] ?? ''));
        $noteLength = $this->noteLength($note);

        if ($noteRequired && $noteLength < 8) {
            return [
                'note' => $note,
                'image_path' => null,
                'error' => 'Descreva o motivo da ação com pelo menos 8 caracteres.',
            ];
        }

        if ($noteLength > 2000) {
            return [
                'note' => $note,
                'image_path' => null,
                'error' => 'A descrição da ação deve ter no máximo 2000 caracteres.',
            ];
        }

        $actor = ClassAuth::user();
        $upload = $this->processActionImageUpload($_FILES['action_image'] ?? [], (int) ($actor['id'] ?? 0));
        if (!empty($upload['error'])) {
            return [
                'note' => $note,
                'image_path' => null,
                'error' => (string) $upload['error'],
            ];
        }

        $imagePath = $upload['path'] ?? null;
        if ($imageRequired && (!is_string($imagePath) || $imagePath === '')) {
            return [
                'note' => $note,
                'image_path' => null,
                'error' => 'Anexe o comprovativo de pagamento para declarar o pagamento.',
            ];
        }

        return [
            'note' => $note,
            'image_path' => $imagePath,
            'error' => null,
        ];
    }

    private function composeActionLogDetails(string $baseDetails, string $note, ?string $imagePath): string {
        $details = $baseDetails;

        if ($note !== '') {
            $details .= ' | Observação: ' . $note;
        }

        if (is_string($imagePath) && $imagePath !== '') {
            $details .= ' | Evidência: ' . $imagePath;
        }

        return $details;
    }

    private function userCanManageAllRequests(array $user): bool {
        return ClassAccess::can('requests.manage', $user);
    }

    private function createCommissionFromRequest(int $requestId, array $request, array $property, int $actorId): void {
        if ($requestId <= 0 || empty($property) || Commission::existsByRequest($requestId)) {
            return;
        }

        $commissionBase = (float) ($request['modality_total_amount'] ?? 0);
        if ($commissionBase <= 0) {
            $commissionBase = (float) ($property['price'] ?? 0);
        }

        $requestAffiliateId = (int) ($request['affiliate_id'] ?? 0);
        $ownerId = (int) ($property['affiliate_id'] ?? 0);
        $propertyId = (int) ($request['property_id'] ?? 0);
        $hasValidAffiliate = $requestAffiliateId > 0
            && $requestAffiliateId !== $ownerId
            && PropertyAffiliate::isActiveAffiliate($requestAffiliateId, $propertyId);

        if ($hasValidAffiliate && Commission::hasActiveAffiliateCommissionForProperty($propertyId, $requestAffiliateId)) {
            $hasValidAffiliate = false;
        }

        $commissionId = Commission::createFromRequest(
            $requestId,
            $hasValidAffiliate ? $requestAffiliateId : 0,
            (int) ($request['property_id'] ?? 0),
            $commissionBase,
            $ownerId
        );

        $commissionRecordId = is_numeric($commissionId) ? (int) $commissionId : 0;
        if ($ownerId > 0 && $commissionRecordId > 0) {
            $commissionRecord = Commission::findById($commissionRecordId);
            $dueAt = (string) ($commissionRecord['due_at'] ?? '');
            $dueLabel = $dueAt !== '' ? date('d/m/Y', strtotime($dueAt)) : date('d/m/Y', strtotime('+' . max(1, (int) \Src\classes\ClassSettings::int('commission_due_days', 7)) . ' days'));
            $amountFormatted = number_format(max(0, (float) ($commissionRecord['amount'] ?? 0)), 0, ',', '.');
            Notification::notifyUser(
                $ownerId,
                'commission_payment_due',
                'Comissão a pagar',
                'Foi registada uma comissão de ' . $amountFormatted . ' Kz pelo fecho do imóvel "' . ((string) ($property['title'] ?? '')) . '". Pague até ' . $dueLabel . '.',
                [
                    'request_id' => $requestId,
                    'property_id' => (int) ($request['property_id'] ?? 0),
                    'commission_id' => $commissionRecordId,
                ],
                $actorId
            );
        }

        if ($hasValidAffiliate) {
            Notification::notifyUser(
                $requestAffiliateId,
                'commission_created',
                'Nova comissão registada',
                'Uma comissão foi registada para o imóvel "' . ((string) ($property['title'] ?? '')) . '". Verifique os detalhes na área de Afiliados.',
                ['request_id' => $requestId, 'property_id' => (int) ($request['property_id'] ?? 0)],
                $actorId
            );
        }
    }

    private function redirectBackOr(string $fallbackPath, string $param, string $message): void {
        $url = ClassCsrf::resolveReturnUrl($fallbackPath);
        $separator = strpos($url, '?') === false ? '?' : '&';
        header('Location: ' . $url . $separator . $param . '=' . urlencode($message));
        exit;
    }

    public function request() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        ClassAuth::requireAuth();
        header('Location: ' . DIRPAGE . 'requests');
        exit;
    }

    public function store() {
        if (!ClassAuth::check()) {
            header('Location: ' . DIRPAGE . 'login');
            exit;
        }

        ClassCommissionGuard::requireCanSubmitPropertyRequest();

        $currentUser = ClassAuth::user();
        if (!ClassAccess::canSubmitPropertyRequest($currentUser)) {
            $propertyId = (int) ($_POST['property_id'] ?? 0);
            $target = $propertyId > 0 ? ('property/' . $propertyId) : 'properties';
            header('Location: ' . DIRPAGE . $target . '?error=' . rawurlencode('Contas da equipa não podem enviar pedidos de compra ou aluguer.'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'properties');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::failRedirect('properties', 'Token inválido');
            exit;
        }

        $propertyId = (int) ($_POST['property_id'] ?? 0);
        $property = Property::find($propertyId);
        if (!$property || ($property['status'] ?? '') !== 'disponivel') {
            header('Location: ' . DIRPAGE . 'properties?error=Imóvel indisponível para solicitação');
            exit;
        }

        if ((int) ($property['affiliate_id'] ?? 0) === (int) ($currentUser['id'] ?? 0)) {
            header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?error=Não pode solicitar o seu próprio imóvel');
            exit;
        }

        if (Request::hasActiveRequest((int) $currentUser['id'], $propertyId)) {
            header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?error=Já tem uma solicitação ativa para este imóvel');
            exit;
        }

        $requestedType = (string) ($_POST['type'] ?? '');
        $purposeToRequestType = [
            'venda' => 'compra',
            'aluguer_curto' => 'aluguer_curto',
            'aluguer_longo' => 'aluguer_longo',
        ];
        $expectedType = $purposeToRequestType[(string) ($property['purpose'] ?? '')] ?? null;
        if ($expectedType === null || $requestedType !== $expectedType) {
            header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?error=Tipo de solicitação incompatível com a finalidade do imóvel');
            exit;
        }

        $allowedTermMonths = [
            'mensal' => 1,
            'trimestral' => 3,
            'semestral' => 6,
            'anual' => 12,
        ];

        $selectedTerm = null;
        $monthsCount = 1;
        $monthlyReferenceAmount = (float) ($property['price'] ?? 0);
        $modalityTotalAmount = $monthlyReferenceAmount;

        if (($property['purpose'] ?? '') === 'aluguer_longo') {
            $selectedTerm = (string) ($_POST['payment_term'] ?? '');
            $termsRaw = json_decode((string) ($property['rent_payment_terms'] ?? '[]'), true);
            $allowedTermsForProperty = is_array($termsRaw) ? array_values(array_filter($termsRaw, static function ($term) use ($allowedTermMonths) {
                return is_string($term) && isset($allowedTermMonths[$term]);
            })) : [];

            if ($selectedTerm === '' || !in_array($selectedTerm, $allowedTermsForProperty, true)) {
                header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?error=Modalidade de pagamento inválida para este imóvel');
                exit;
            }

            $monthsCount = $allowedTermMonths[$selectedTerm];
            $modalityTotalAmount = $monthlyReferenceAmount * $monthsCount;
        }

        $referredBy = (int) ClassSession::get('referred_by');
        if (
            $referredBy <= 0
            || $referredBy === (int) ($currentUser['id'] ?? 0)
            || !PropertyAffiliate::isActiveAffiliate($referredBy, $propertyId)
        ) {
            $referredBy = null;
        }

        $data = [
            'user_id' => $currentUser['id'],
            'property_id' => $propertyId,
            'affiliate_id' => $referredBy,
            'type' => $requestedType,
            'payment_term' => $selectedTerm,
            'months_count' => $monthsCount,
            'monthly_reference_amount' => $monthlyReferenceAmount,
            'modality_total_amount' => $modalityTotalAmount,
            'message' => $_POST['message'] ?? ''
        ];

        $requestId = Request::create($data);
        if ($requestId) {
            if (ClassCookieConsent::hasBehavioralConsent()) {
                PropertyBehaviorEvent::track(
                    (int) ($currentUser['id'] ?? 0),
                    (int) $propertyId,
                    'request',
                    ClassSession::getOrCreateVisitorKey()
                );
            }
            $initialMessage = trim((string) ($data['message'] ?? ''));
            if ($initialMessage !== '') {
                $thread = RequestChatThread::getOrCreateByRequestId((int) $requestId);
                if ($thread && !empty($thread['id'])) {
                    RequestChatMessage::createForThread(
                        (int) ($thread['id'] ?? 0),
                        (int) ($currentUser['id'] ?? 0),
                        $initialMessage,
                        'text'
                    );
                }
            }

            $this->appendChatSystemMessage(
                (int) $requestId,
                (int) ($currentUser['id'] ?? 0),
                'Solicitação criada e negociação iniciada.'
            );

            // Notify property owner about a new request
            if (!empty($property['affiliate_id'])) {
                Notification::notifyUser(
                    (int) $property['affiliate_id'],
                    'new_request',
                    'Nova solicitação recebida',
                    'Você recebeu uma nova solicitação para o imóvel "' . ($property['title'] ?? '') . '".',
                    ['request_id' => (int) $requestId, 'property_id' => (int) $data['property_id']],
                    (int) $data['user_id']
                );
            }

            // Clear referral after use
            ClassSession::remove('referred_by');
            header('Location: ' . DIRPAGE . 'property/' . $propertyId . '?success=' . urlencode('Solicitação enviada com sucesso'));
            exit;
        } else {
            header('Location: ' . DIRPAGE . 'property/' . $data['property_id'] . '?error=1');
            exit;
        }
    }

    public function updateStatus($id, $status = null) {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::failRedirect('dashboard', 'Token inválido');
            exit;
        }

        // Accept status from POST forms and keep URL-based status as fallback.
        $status = $_POST['status'] ?? $status;

        $allowed = Request::allowedStatuses();
        if (!in_array($status, $allowed, true)) {
            header('Location: ' . DIRPAGE . 'dashboard?error=Status inválido');
            exit;
        }

        $request = Request::findById((int) $id);
        if (!$request) {
            header('Location: ' . DIRPAGE . 'dashboard?error=Solicitação não encontrada');
            exit;
        }

        $currentStatus = (string) ($request['status'] ?? '');
        $disputeWindowOpen = Request::isDisputeWindowOpen($request);

        if (!Request::canTransition($currentStatus, (string) $status)) {
            $this->redirectBackOr('requests', 'error', 'Transição de status não permitida para esta solicitação');
        }

        // Check if user is the property owner or can manage all requests by permission.
        $property = Property::find((int) $request['property_id']);
        if (!$property || !ClassAccess::canManagePropertyRequests($user, $property)) {
            header('Location: ' . DIRPAGE . 'dashboard?error=Você não tem permissão para aprovar esta solicitação');
            exit;
        }

        if ($this->isPropertyCommerciallyClosed($property)) {
            header('Location: ' . DIRPAGE . 'dashboard?error=Esta solicitação não pode ser negociada porque o imóvel já está vendido ou alugado');
            exit;
        }

        $isRequestManager = $this->userCanManageAllRequests($user);

        if (!$isRequestManager && $currentStatus === 'em_disputa') {
            header('Location: ' . DIRPAGE . 'dashboard?error=Solicitação em disputa só pode ser decidida por perfis de moderação');
            exit;
        }

        $allowedByProfile = Request::nextStatusesForNegotiationActor($currentStatus, $isRequestManager, $disputeWindowOpen);
        if (!in_array((string) $status, $allowedByProfile, true)) {
            header('Location: ' . DIRPAGE . 'dashboard?error=Transição não permitida para o seu perfil');
            exit;
        }

        if ($status === 'fechado_ganho' && $isRequestManager && $currentStatus !== 'em_disputa') {
            $this->redirectBackOr('requests', 'error', 'Perfis de moderação não podem declarar ganho fora do fluxo de disputa');
        }

        $requiresActionNote = (
            (string) $status === 'em_disputa'
            || $currentStatus === 'em_disputa'
            || (string) $status === 'cancelado'
        );
        $actionContext = $this->collectActionContext($requiresActionNote);
        if (!empty($actionContext['error'])) {
            $this->redirectBackOr('requests', 'error', (string) $actionContext['error']);
        }

        $allowedWhenPropertyNotAvailable = $isRequestManager ? ['cancelado', 'em_disputa'] : ['cancelado'];
        if (($property['status'] ?? '') !== 'disponivel' && !in_array((string) $status, $allowedWhenPropertyNotAvailable, true)) {
            header('Location: ' . DIRPAGE . 'dashboard?error=Solicitacao bloqueada: o anuncio precisa estar moderado e disponivel');
            exit;
        }

        if ($status === 'fechado_ganho' && $isRequestManager && $currentStatus === 'em_disputa') {
            // Admin resolving a dispute: use resolveDispute() to update commercial_status + dispute_status.
            $updated = Request::resolveDispute((int) $id, 'fechado_ganho', (int) ($user['id'] ?? 0));
        } elseif ($status === 'cancelado' && $isRequestManager && $currentStatus === 'em_disputa') {
            $updated = Request::resolveDispute((int) $id, 'cancelado', (int) ($user['id'] ?? 0));
        } elseif ($status === 'fechado_ganho') {
            $updated = Request::declareClosingWon((int) $id, (int) ($user['id'] ?? 0));
        } else {
            $updated = Request::updateStatus((int) $id, $status);
        }

        if ($updated) {
            if ($status === 'fechado_ganho' && $isRequestManager && $currentStatus === 'em_disputa' && $property) {
                $finalStatus = $this->resolvePropertyFinalStatus($property);
                if ($finalStatus !== null && ($property['status'] ?? '') !== $finalStatus) {
                    Property::setStatus((int) ($property['id'] ?? 0), $finalStatus);
                    Request::closeActiveByPropertyClosure((int) ($property['id'] ?? 0), (int) $id);
                }
            }

            Log::create([
                'user_id' => $user['id'],
                'action' => 'update_request_status',
                'entity_type' => 'request',
                'entity_id' => (int) $id,
                'details' => $this->composeActionLogDetails(
                    'Status atualizado para: ' . $status,
                    (string) ($actionContext['note'] ?? ''),
                    is_string($actionContext['image_path'] ?? null) ? $actionContext['image_path'] : null
                )
            ]);

            $this->appendChatSystemMessage(
                (int) $id,
                (int) ($user['id'] ?? 0),
                $this->systemMessageForStatusChange(
                    (string) $status,
                    $request,
                    $property ?: [],
                    $user,
                    (string) ($actionContext['note'] ?? '')
                ),
                is_string($actionContext['image_path'] ?? null) ? $actionContext['image_path'] : null
            );

            $notifyUsers = [(int) $request['user_id']];
            if ($isRequestManager && $currentStatus === 'em_disputa' && !empty($property['affiliate_id'])) {
                $notifyUsers[] = (int) $property['affiliate_id'];
            }

            foreach (array_values(array_unique(array_filter($notifyUsers))) as $targetUserId) {
                Notification::notifyRequestStatusChanged(
                    (int) $targetUserId,
                    (int) $id,
                    (string) $status,
                    (string) ($property['title'] ?? ''),
                    (int) $user['id'],
                    $status === 'fechado_ganho'
                        ? ($currentStatus === 'em_disputa'
                            ? Request::CLOSING_CONFIRMATION_CONFIRMED
                            : Request::CLOSING_CONFIRMATION_PENDING)
                        : ($request['closing_confirmation_status'] ?? null)
                );
            }

            $this->redirectBackOr('dashboard', 'success', 'Status atualizado');
        }

        $this->redirectBackOr('dashboard', 'error', 'Não foi possível atualizar o status');
    }

    public function cancel($id) {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            $this->redirectBackOr('requests?view=sent', 'error', 'Token inválido');
        }

        $request = Request::findById((int) $id);
        if (!$request) {
            $this->redirectBackOr('requests?view=sent', 'error', 'Solicitação não encontrada');
        }

        if ((int) ($request['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            $this->redirectBackOr('requests?view=sent', 'error', 'Sem permissão para cancelar esta solicitação');
        }

        if (!in_array((string) ($request['status'] ?? ''), Request::CANCELLABLE_STATUSES, true)) {
            $this->redirectBackOr('requests?view=sent', 'error', 'So e possivel cancelar solicitacoes com negociacao ativa');
        }

        $actionContext = $this->collectActionContext(true);
        if (!empty($actionContext['error'])) {
            $this->redirectBackOr('requests?view=sent', 'error', (string) $actionContext['error']);
        }

        $cancelled = Request::updateStatus((int) $id, 'cancelado');

        if ($cancelled) {
            Log::create([
                'user_id' => $user['id'],
                'action' => 'cancel_request',
                'entity_type' => 'request',
                'entity_id' => (int) $id,
                'details' => $this->composeActionLogDetails(
                    'Solicitação cancelada pelo utilizador',
                    (string) ($actionContext['note'] ?? ''),
                    is_string($actionContext['image_path'] ?? null) ? $actionContext['image_path'] : null
                ),
            ]);

            $this->appendChatSystemMessage(
                (int) $id,
                (int) ($user['id'] ?? 0),
                'O solicitante cancelou a negociação. Observação: ' . trim((string) ($actionContext['note'] ?? ''))
            );

            $property = Property::find((int) ($request['property_id'] ?? 0));
            if (!empty($property['affiliate_id'])) {
                Notification::notifyUser(
                    (int) $property['affiliate_id'],
                    'request_cancelled',
                    'Solicitação cancelada',
                    'Uma solicitação para o imóvel "' . ($property['title'] ?? '') . '" foi cancelada pelo cliente.',
                    ['request_id' => (int) $id, 'property_id' => (int) ($request['property_id'] ?? 0)],
                    (int) $user['id']
                );
            }

            $this->redirectBackOr('requests?view=sent', 'success', 'Solicitação cancelada com sucesso');
        }

        $this->redirectBackOr('requests?view=sent', 'error', 'Não foi possível cancelar a solicitação');
    }

    public function confirmClosing($id) {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            $this->redirectBackOr('requests?view=sent', 'error', 'Token inválido');
        }

        $request = Request::findById((int) $id);
        if (!$request) {
            $this->redirectBackOr('requests?view=sent', 'error', 'Solicitação não encontrada');
        }

        if ((int) ($request['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            $this->redirectBackOr('requests?view=sent', 'error', 'Sem permissão para confirmar este fecho');
        }

        if (($request['status'] ?? '') !== 'fechado_ganho' || ($request['closing_confirmation_status'] ?? '') !== Request::CLOSING_CONFIRMATION_PENDING) {
            $this->redirectBackOr('requests?view=sent', 'error', 'Esta solicitação não está em fase de confirmação de pagamento');
        }

        $paymentConfirmationStatus = (string) ($request['payment_confirmation_status'] ?? Request::PAYMENT_CONFIRMATION_PENDING);
        if (!in_array($paymentConfirmationStatus, [
            Request::PAYMENT_CONFIRMATION_PENDING,
            Request::PAYMENT_CONFIRMATION_DECLARED_BY_REQUESTER,
        ], true)) {
            $this->redirectBackOr('requests?view=sent', 'error', 'O pagamento desta solicitação não pode ser declarado neste momento');
        }

        $actionContext = $this->collectActionContext(false, true);
        if (!empty($actionContext['error'])) {
            $this->redirectBackOr('requests?view=sent', 'error', (string) ($actionContext['error'] ?? 'Erro ao registrar pagamento'));
        }

        $property = Property::find((int) ($request['property_id'] ?? 0));

        if ($property && $this->isPropertyCommerciallyClosed($property)) {
            $this->redirectBackOr('requests?view=sent', 'error', 'Esta solicitação não pode ser negociada porque o imóvel já está vendido ou alugado');
        }

        $confirmed = Request::declarePaymentByRequester(
            (int) $id,
            (int) ($user['id'] ?? 0),
            is_string($actionContext['image_path'] ?? null) ? $actionContext['image_path'] : null
        );

        if ($confirmed) {
            Log::create([
                'user_id' => (int) ($user['id'] ?? 0),
                'action' => 'declare_request_payment',
                'entity_type' => 'request',
                'entity_id' => (int) $id,
                'details' => $this->composeActionLogDetails(
                    'Pagamento declarado pelo interessado',
                    (string) ($actionContext['note'] ?? ''),
                    is_string($actionContext['image_path'] ?? null) ? $actionContext['image_path'] : null
                ),
            ]);

            $proofPath = is_string($actionContext['image_path'] ?? null) ? $actionContext['image_path'] : null;
            $this->appendChatSystemMessage(
                (int) $id,
                (int) ($user['id'] ?? 0),
                'O solicitante declarou o pagamento da negociação.' . ($proofPath ? ' Comprovativo em anexo.' : ''),
                $proofPath
            );

            if (!empty($property['affiliate_id'])) {
                Notification::notifyUser(
                    (int) $property['affiliate_id'],
                    'request_payment_declared',
                    'Pagamento declarado',
                    'O interessado declarou pagamento no imóvel "' . ((string) ($property['title'] ?? '')) . '". Confirme o recebimento para consolidar o fecho.',
                    ['request_id' => (int) $id, 'property_id' => (int) ($request['property_id'] ?? 0)],
                    (int) ($user['id'] ?? 0)
                );
            }

            $this->redirectBackOr('requests?view=sent', 'success', 'Pagamento declarado com sucesso');
        }

        $this->redirectBackOr('requests?view=sent', 'error', 'Não foi possível declarar o pagamento');
    }

    public function contestClosing($id) {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            $this->redirectBackOr('requests?view=sent', 'error', 'Token inválido');
        }

        $request = Request::findById((int) $id);
        if (!$request) {
            $this->redirectBackOr('requests?view=sent', 'error', 'Solicitação não encontrada');
        }

        if ((int) ($request['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            $this->redirectBackOr('requests?view=sent', 'error', 'Sem permissão para contestar este fecho');
        }

        if (($request['status'] ?? '') !== 'fechado_ganho' || ($request['closing_confirmation_status'] ?? '') !== Request::CLOSING_CONFIRMATION_PENDING) {
            $this->redirectBackOr('requests?view=sent', 'error', 'Este fecho não está pendente de confirmação');
        }

        $actionContext = $this->collectActionContext(true);
        if (!empty($actionContext['error'])) {
            $this->redirectBackOr('requests?view=sent', 'error', (string) $actionContext['error']);
        }

        $contested = Request::contestPayment((int) $id, (int) ($user['id'] ?? 0));
        $property = Property::find((int) ($request['property_id'] ?? 0));

        if ($contested) {
            Log::create([
                'user_id' => (int) ($user['id'] ?? 0),
                'action' => 'contest_request_closing',
                'entity_type' => 'request',
                'entity_id' => (int) $id,
                'details' => $this->composeActionLogDetails(
                    'Fecho ganho contestado pelo interessado',
                    (string) ($actionContext['note'] ?? ''),
                    is_string($actionContext['image_path'] ?? null) ? $actionContext['image_path'] : null
                ),
            ]);

            $this->appendChatSystemMessage(
                (int) $id,
                (int) ($user['id'] ?? 0),
                'O solicitante contestou o fecho ganho e levou a negociação para disputa. Observação: ' . trim((string) ($actionContext['note'] ?? ''))
            );

            if (!empty($property['affiliate_id'])) {
                Notification::notifyUser(
                    (int) $property['affiliate_id'],
                    'request_payment_contested',
                    'Pagamento contestado',
                    'O interessado contestou o pagamento do imóvel "' . ((string) ($property['title'] ?? '')) . '". O caso foi movido para disputa.',
                    ['request_id' => (int) $id, 'property_id' => (int) ($request['property_id'] ?? 0)],
                    (int) ($user['id'] ?? 0)
                );
            }

            $this->redirectBackOr('requests?view=sent', 'success', 'Contestação enviada. A solicitação está agora em disputa');
        }

        $this->redirectBackOr('requests?view=sent', 'error', 'Não foi possível contestar o fecho');
    }

    public function confirmPaymentReceipt($id) {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            $this->redirectBackOr('requests', 'error', 'Token inválido');
        }

        $request = Request::findById((int) $id);
        if (!$request) {
            $this->redirectBackOr('requests', 'error', 'Solicitação não encontrada');
        }

        $property = Property::find((int) ($request['property_id'] ?? 0));
        if (!$property || (int) ($property['affiliate_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            $this->redirectBackOr('requests', 'error', 'Sem permissão para confirmar recebimento');
        }

        if (($request['status'] ?? '') !== 'fechado_ganho' || ($request['closing_confirmation_status'] ?? '') !== Request::CLOSING_CONFIRMATION_PENDING) {
            $this->redirectBackOr('requests', 'error', 'Esta solicitação não está em fase de confirmação de recebimento');
        }

        if (($request['payment_confirmation_status'] ?? '') !== Request::PAYMENT_CONFIRMATION_DECLARED_BY_REQUESTER) {
            $this->redirectBackOr('requests', 'error', 'Ainda não há declaração de pagamento do interessado');
        }

        if ($this->isPropertyCommerciallyClosed($property)) {
            $this->redirectBackOr('requests', 'error', 'Esta solicitação não pode ser consolidada porque o imóvel já está vendido ou alugado');
        }

        $confirmed = Request::confirmPaymentReceiptByOwner((int) $id, (int) ($user['id'] ?? 0));

        if ($confirmed) {
            $this->createCommissionFromRequest((int) $id, $request, $property, (int) ($user['id'] ?? 0));

            $finalStatus = $this->resolvePropertyFinalStatus($property);
            if ($finalStatus !== null && ($property['status'] ?? '') !== $finalStatus) {
                Property::setStatus((int) ($property['id'] ?? 0), $finalStatus);
                Request::closeActiveByPropertyClosure((int) ($property['id'] ?? 0), (int) $id);
            }

            Log::create([
                'user_id' => (int) ($user['id'] ?? 0),
                'action' => 'confirm_request_payment_receipt',
                'entity_type' => 'request',
                'entity_id' => (int) $id,
                'details' => 'Recebimento de pagamento confirmado pelo proprietário',
            ]);

            $this->appendChatSystemMessage(
                (int) $id,
                (int) ($user['id'] ?? 0),
                'O proprietário confirmou o recebimento do pagamento e consolidou o fecho da negociação.'
            );

            Notification::notifyUser(
                (int) ($request['user_id'] ?? 0),
                'request_payment_receipt_confirmed',
                'Recebimento confirmado',
                'O proprietário confirmou o recebimento do pagamento do imóvel "' . ((string) ($property['title'] ?? '')) . '".',
                ['request_id' => (int) $id, 'property_id' => (int) ($request['property_id'] ?? 0)],
                (int) ($user['id'] ?? 0)
            );

            $this->redirectBackOr('requests', 'success', 'Recebimento confirmado com sucesso');
        }

        $this->redirectBackOr('requests', 'error', 'Não foi possível confirmar o recebimento');
    }

    public function contestPayment($id) {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            $this->redirectBackOr('requests', 'error', 'Token inválido');
        }

        $request = Request::findById((int) $id);
        if (!$request) {
            $this->redirectBackOr('requests', 'error', 'Solicitação não encontrada');
        }

        $property = Property::find((int) ($request['property_id'] ?? 0));
        if (!$property || (int) ($property['affiliate_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            $this->redirectBackOr('requests', 'error', 'Sem permissão para contestar pagamento');
        }

        if (($request['status'] ?? '') !== 'fechado_ganho' || ($request['closing_confirmation_status'] ?? '') !== Request::CLOSING_CONFIRMATION_PENDING) {
            $this->redirectBackOr('requests', 'error', 'Esta solicitação não está apta para contestação de pagamento');
        }

        $actionContext = $this->collectActionContext(true);
        if (!empty($actionContext['error'])) {
            $this->redirectBackOr('requests', 'error', (string) ($actionContext['error'] ?? 'Erro ao contestar pagamento'));
        }

        $contested = Request::contestPayment((int) $id, (int) ($user['id'] ?? 0));
        if ($contested) {
            Log::create([
                'user_id' => (int) ($user['id'] ?? 0),
                'action' => 'contest_request_payment',
                'entity_type' => 'request',
                'entity_id' => (int) $id,
                'details' => $this->composeActionLogDetails(
                    'Pagamento contestado pelo proprietário',
                    (string) ($actionContext['note'] ?? ''),
                    is_string($actionContext['image_path'] ?? null) ? $actionContext['image_path'] : null
                ),
            ]);

            $this->appendChatSystemMessage(
                (int) $id,
                (int) ($user['id'] ?? 0),
                'O proprietário contestou o pagamento e a solicitação foi enviada para disputa. Observação: ' . trim((string) ($actionContext['note'] ?? ''))
            );

            Notification::notifyUser(
                (int) ($request['user_id'] ?? 0),
                'request_payment_contested',
                'Pagamento contestado',
                'O proprietário contestou o pagamento do imóvel "' . ((string) ($property['title'] ?? '')) . '". O caso foi movido para disputa.',
                ['request_id' => (int) $id, 'property_id' => (int) ($request['property_id'] ?? 0)],
                (int) ($user['id'] ?? 0)
            );

            $this->redirectBackOr('requests', 'success', 'Contestação de pagamento enviada com sucesso');
        }

        $this->redirectBackOr('requests', 'error', 'Não foi possível contestar o pagamento');
    }

    public function openDispute($id) {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            $this->redirectBackOr('requests?view=sent', 'error', 'Token inválido');
        }

        $request = Request::findById((int) $id);
        if (!$request) {
            $this->redirectBackOr('requests?view=sent', 'error', 'Solicitação não encontrada');
        }

        $isRequester = (int) ($request['user_id'] ?? 0) === (int) ($user['id'] ?? 0);
        $property = Property::find((int) ($request['property_id'] ?? 0));
        if (!$isRequester) {
            $this->redirectBackOr('requests', 'error', 'Apenas o solicitante pode abrir disputa nesta solicitação');
        }

        if (!Request::isDisputeWindowOpen($request)) {
            $this->redirectBackOr('requests', 'error', 'A janela de disputa desta solicitação já terminou');
        }

        $actionContext = $this->collectActionContext(true);
        if (!empty($actionContext['error'])) {
            $this->redirectBackOr('requests', 'error', (string) $actionContext['error']);
        }

        $opened = Request::openDisputeStatus((int) $id);
        if ($opened) {
            Log::create([
                'user_id' => (int) ($user['id'] ?? 0),
                'action' => 'open_request_dispute',
                'entity_type' => 'request',
                'entity_id' => (int) $id,
                'details' => $this->composeActionLogDetails(
                    'Solicitação enviada para disputa',
                    (string) ($actionContext['note'] ?? ''),
                    is_string($actionContext['image_path'] ?? null) ? $actionContext['image_path'] : null
                ),
            ]);

            $this->appendChatSystemMessage(
                (int) $id,
                (int) ($user['id'] ?? 0),
                'O solicitante abriu disputa para esta negociação. Observação: ' . trim((string) ($actionContext['note'] ?? ''))
            );

            $notifyUsers = array_unique(array_filter([
                (int) ($request['user_id'] ?? 0),
                (int) ($property['affiliate_id'] ?? 0),
            ]));
            foreach ($notifyUsers as $targetUserId) {
                if ($targetUserId === (int) ($user['id'] ?? 0)) {
                    continue;
                }

                Notification::notifyRequestStatusChanged(
                    (int) $targetUserId,
                    (int) $id,
                    'em_disputa',
                    (string) ($property['title'] ?? ''),
                    (int) ($user['id'] ?? 0)
                );
            }

            $this->redirectBackOr('requests', 'success', 'Disputa aberta com sucesso');
        }

        $this->redirectBackOr('requests', 'error', 'Não foi possível abrir a disputa');
    }

    public function sendMessage($id) {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::failRedirect('requests', 'Token inválido');
            exit;
        }

        $requestId = (int) $id;
        $request = Request::getByIdWithContext($requestId);
        if (!$request) {
            header('Location: ' . DIRPAGE . 'requests?error=Solicitação não encontrada');
            exit;
        }

        if (in_array((string) ($request['property_status'] ?? ''), ['vendido', 'alugado'], true)) {
            header('Location: ' . DIRPAGE . 'dashboard/requestChat/' . $requestId . '?error=' . urlencode('Negociação encerrada: o imóvel já está vendido ou alugado'));
            exit;
        }

        $isRequester = (int) ($request['user_id'] ?? 0) === (int) ($user['id'] ?? 0);
        $isOwner = (int) ($request['owner_id'] ?? 0) === (int) ($user['id'] ?? 0);
        if (!$isRequester && !$isOwner) {
            header('Location: ' . DIRPAGE . 'requests?error=Sem permissão para enviar mensagens neste chat');
            exit;
        }

        if (!Request::isChatWritable($request)) {
            header('Location: ' . DIRPAGE . 'dashboard/requestChat/' . $requestId . '?error=' . urlencode('O chat desta solicitação está bloqueado para novas mensagens'));
            exit;
        }

        $messageText = trim((string) ($_POST['message_text'] ?? ''));
        if ($messageText === '') {
            header('Location: ' . DIRPAGE . 'dashboard/requestChat/' . $requestId . '?error=' . urlencode('Escreva uma mensagem antes de enviar'));
            exit;
        }

        if ($this->noteLength($messageText) > 3000) {
            header('Location: ' . DIRPAGE . 'dashboard/requestChat/' . $requestId . '?error=' . urlencode('A mensagem deve ter no máximo 3000 caracteres'));
            exit;
        }

        // Process optional file upload
        $attachmentPath = null;
        if (!empty($_FILES['message_attachment']['tmp_name'])) {
            $upload = $this->processMessageAttachmentUpload($_FILES['message_attachment'] ?? [], (int) ($user['id'] ?? 0));
            if (!empty($upload['error'])) {
                header('Location: ' . DIRPAGE . 'dashboard/requestChat/' . $requestId . '?error=' . urlencode($upload['error']));
                exit;
            }
            $attachmentPath = $upload['path'] ?? null;
        }

        $thread = RequestChatThread::getOrCreateByRequestId($requestId);
        if (!$thread) {
            header('Location: ' . DIRPAGE . 'dashboard/requestChat/' . $requestId . '?error=' . urlencode('Não foi possível iniciar o chat desta solicitação'));
            exit;
        }

        $messageId = RequestChatMessage::createForThread((int) ($thread['id'] ?? 0), (int) ($user['id'] ?? 0), $messageText, 'text', $attachmentPath);
        if (!$messageId) {
            header('Location: ' . DIRPAGE . 'dashboard/requestChat/' . $requestId . '?error=' . urlencode('Não foi possível enviar a mensagem'));
            exit;
        }

        Log::create([
            'user_id' => (int) ($user['id'] ?? 0),
            'action' => 'send_request_chat_message',
            'entity_type' => 'request',
            'entity_id' => $requestId,
            'details' => 'Mensagem enviada no chat da solicitação' . ($attachmentPath ? ' com anexo' : ''),
        ]);

        $counterpartyId = $isRequester
            ? (int) ($request['owner_id'] ?? 0)
            : (int) ($request['user_id'] ?? 0);
        if ($counterpartyId > 0 && $counterpartyId !== (int) ($user['id'] ?? 0)) {
            Notification::notifyUser(
                $counterpartyId,
                'request_chat_message',
                'Nova mensagem na negociação',
                'Você recebeu uma nova mensagem na solicitação do imóvel "' . ((string) ($request['title'] ?? '')) . '".',
                ['request_id' => $requestId],
                (int) ($user['id'] ?? 0)
            );
        }

        header('Location: ' . DIRPAGE . 'dashboard/requestChat/' . $requestId . '?success=' . urlencode('Mensagem enviada'));
        exit;
    }

    public function approveAffiliate($id) {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::failRedirect('dashboard/afiliados?tab=affiliate_requests', 'Token inválido');
            exit;
        }

        $affiliate = PropertyAffiliate::find((int) $id);
        if (!$affiliate) {
            header('Location: ' . DIRPAGE . 'dashboard/afiliados?tab=affiliate_requests&error=Solicitação de afiliação não encontrada');
            exit;
        }

        // Check if user is the property owner
        $property = Property::find($affiliate['property_id']);
        if (!$property || $property['affiliate_id'] != $user['id']) {
            header('Location: ' . DIRPAGE . 'dashboard/afiliados?tab=affiliate_requests&error=Você não tem permissão para aprovar esta solicitação');
            exit;
        }

        $approved = PropertyAffiliate::approve((int) $id);

        if ($approved) {
            Log::create([
                'user_id' => $user['id'],
                'action' => 'approve_affiliate_request',
                'entity_type' => 'property_affiliate',
                'entity_id' => (int) $id,
                'details' => 'Afiliado aprovado para propriedade ID: ' . $property['id']
            ]);
            Notification::notifyUser(
                (int) $affiliate['user_id'],
                'affiliate_approved',
                'Afiliação aprovada',
                'A sua solicitação de afiliação ao imóvel "' . ($property['title'] ?? '') . '" foi aprovada. Pode começar a indicar.',
                ['property_id' => (int) $property['id']],
                (int) $user['id']
            );
            $this->redirectBackOr('dashboard/afiliados?tab=affiliate_requests', 'success', 'Afiliado aprovado com sucesso');
        }

        $this->redirectBackOr('dashboard/afiliados?tab=affiliate_requests', 'error', 'Não foi possível aprovar a solicitação');
    }

    public function rejectAffiliate($id) {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            ClassCsrf::failRedirect('dashboard/afiliados?tab=affiliate_requests', 'Token inválido');
            exit;
        }

        $affiliate = PropertyAffiliate::find((int) $id);
        if (!$affiliate) {
            header('Location: ' . DIRPAGE . 'dashboard/afiliados?tab=affiliate_requests&error=Solicitação de afiliação não encontrada');
            exit;
        }

        // Check if user is the property owner
        $property = Property::find($affiliate['property_id']);
        if (!$property || $property['affiliate_id'] != $user['id']) {
            header('Location: ' . DIRPAGE . 'dashboard/afiliados?tab=affiliate_requests&error=Você não tem permissão para rejeitar esta solicitação');
            exit;
        }

        $rejected = PropertyAffiliate::reject((int) $id);

        if ($rejected) {
            Log::create([
                'user_id' => $user['id'],
                'action' => 'reject_affiliate_request',
                'entity_type' => 'property_affiliate',
                'entity_id' => (int) $id,
                'details' => 'Solicitação de afiliação rejeitada para propriedade ID: ' . $property['id']
            ]);
            Notification::notifyUser(
                (int) $affiliate['user_id'],
                'affiliate_rejected',
                'Afiliação rejeitada',
                'A sua solicitação de afiliação ao imóvel "' . ($property['title'] ?? '') . '" foi rejeitada pelo proprietário.',
                ['property_id' => (int) $property['id']],
                (int) $user['id']
            );
            $this->redirectBackOr('dashboard/afiliados?tab=affiliate_requests', 'success', 'Solicitação rejeitada');
        }

        $this->redirectBackOr('dashboard/afiliados?tab=affiliate_requests', 'error', 'Não foi possível rejeitar a solicitação');
    }
}