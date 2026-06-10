<?php

namespace App\controller;

use App\model\Log;
use App\model\Notification;
use App\model\Request;
use App\model\RequestChatMessage;
use App\model\RequestChatThread;
use Src\classes\ClassAuth;
use Src\classes\ClassCsrf;

class ControllerRequestChat
{
    use RequestControllerSupport;

    public function sendMessage($id)
    {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
        RequestChatMessage::ensureNegotiationContactPolicyMessage($requestId);
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

}
