<?php

namespace App\controller;

use App\model\Log;
use App\model\Property;
use App\model\Request;
use App\model\RequestChatMessage;
use App\model\RequestChatRead;
use App\model\RequestChatThread;
use Src\classes\ClassAccess;
use Src\classes\ClassAuth;
use Src\classes\ClassCsrf;
use Src\classes\ClassRender;
use Src\classes\UserDisplay;

class ControllerDashboardRequests
{
    public function requests()
    {
        ClassAuth::requireAuth();

        $user = ClassAuth::user();
        $canManageAllRequests = ClassAccess::can('requests.manage', $user);
        $myProperties = Property::getByAffiliate($user['id']);
        $requestView = (string) ($_GET['view'] ?? '');
        $scope = 'user';
        $pageTitle = 'Minhas Solicitações';
        $pageDescription = 'Acompanhe o status de cada contato feito aos imóveis.';
        $requests = [];
        $requestsTotal = 0;
        $perPage = 20;
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        // If user is admin, show all requests
        // If user has properties (is owner), show requests for their properties
        // Otherwise, show only their own requests
        if ($canManageAllRequests) {
            $requestsTotal = Request::countAllWithUser();
            $requests = Request::getAllWithUser($perPage, $offset);
            $scope = 'admin';
            $pageTitle = 'Solicitações do Sistema';
            $pageDescription = 'Visão completa das solicitações de todos os utilizadores.';
        } else {
            $hasOwnedProperties = !empty($myProperties);

            if ($hasOwnedProperties) {
                $scope = 'owner';

                if ($requestView === 'sent') {
                    $requestsTotal = Request::countByUser((int) $user['id']);
                    $requests = Request::getByUser($user['id'], $perPage, $offset);
                    $pageTitle = 'Solicitações Enviadas';
                    $pageDescription = 'Pedidos que você enviou para imóveis de outros proprietários.';
                } else {
                    $requestsTotal = Request::countByPropertyOwner((int) $user['id']);
                    $requests = Request::getByPropertyOwner($user['id'], $perPage, $offset);
                    $requestView = 'received';
                    $pageTitle = 'Solicitações Recebidas';
                    $pageDescription = 'Acompanhe os pedidos recebidos nos seus imóveis.';
                }
            } else {
                $requestsTotal = Request::countByUser((int) $user['id']);
                $requests = Request::getByUser($user['id'], $perPage, $offset);
                $requestView = 'sent';
            }
        }

        $totalPages = (int) ceil($requestsTotal / max(1, $perPage));

        $availableActionFilters = [
            'all' => 'Todas as ações',
            'update_request_status' => 'Mudança de estado',
            'confirm_request_closing' => 'Confirmação de fecho',
            'contest_request_closing' => 'Contestação de fecho',
            'declare_request_payment' => 'Declaração de pagamento',
            'confirm_request_payment_receipt' => 'Confirmação de recebimento',
            'contest_request_payment' => 'Contestação de pagamento',
            'open_request_dispute' => 'Abertura de disputa',
            'cancel_request' => 'Cancelamento',
        ];

        $actionFilter = (string) ($_GET['action_filter'] ?? 'all');
        if (!isset($availableActionFilters[$actionFilter])) {
            $actionFilter = 'all';
        }

        $requestLogs = [];
        $requestChatSummaries = RequestChatMessage::getSummariesByRequestIds(array_map(static function (array $requestItem): int {
            return (int) ($requestItem['id'] ?? 0);
        }, $requests), (int) ($user['id'] ?? 0));

        $requestIds = array_values(array_filter(array_map(static function (array $requestItem): int {
            return (int) ($requestItem['id'] ?? 0);
        }, $requests), static function (int $id): bool {
            return $id > 0;
        }));

        $logsByRequestId = Log::getByEntities('request', $requestIds, 40);

        foreach ($requests as $requestItem) {
            $requestId = (int) ($requestItem['id'] ?? 0);
            if ($requestId <= 0) {
                continue;
            }

            $logs = $logsByRequestId[$requestId] ?? [];
            if ($actionFilter !== 'all') {
                $logs = array_values(array_filter($logs, static function (array $log) use ($actionFilter): bool {
                    return (string) ($log['action'] ?? '') === $actionFilter;
                }));
            }

            if (!empty($logs)) {
                $requestLogs[$requestId] = array_slice($logs, 0, 5);
            }
        }

        $render = new ClassRender();
        $render->setTitle($pageTitle);
        $render->setDescription($pageDescription);
        $render->setKeywords('solicitações');
        $render->setData([
            'user' => $user,
            'requests' => $requests,
            'requestsTotal' => $requestsTotal,
            'isOwner' => !empty($myProperties),
            'canManageAllRequests' => $canManageAllRequests,
            'scope' => $scope,
            'requestView' => $requestView,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'requestLogs' => $requestLogs,
            'requestChatSummaries' => $requestChatSummaries,
            'actionFilter' => $actionFilter,
            'availableActionFilters' => $availableActionFilters,
            'page'       => $page,
            'totalPages' => $totalPages,
        ]);
        $render->setDir('dashboard/requests');
        $render->renderLayout();
    }

    public function requestChatSummariesFeed()
    {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        header('Content-Type: application/json; charset=utf-8');

        $canManageAllRequests = ClassAccess::can('requests.manage', $user);
        $myProperties = Property::getByAffiliate($user['id']);
        $requestView = (string) ($_GET['view'] ?? '');
        $requests = [];

        if ($canManageAllRequests) {
            $requests = Request::getAllWithUser();
        } else {
            $hasOwnedProperties = !empty($myProperties);

            if ($hasOwnedProperties) {
                if ($requestView === 'sent') {
                    $requests = Request::getByUser($user['id']);
                } else {
                    $requests = Request::getByPropertyOwner($user['id']);
                }
            } else {
                $requests = Request::getByUser($user['id']);
            }
        }

        $summaries = RequestChatMessage::getSummariesByRequestIds(array_map(static function (array $requestItem): int {
            return (int) ($requestItem['id'] ?? 0);
        }, $requests), (int) ($user['id'] ?? 0));

        $payload = [];
        foreach ($summaries as $requestId => $summary) {
            $payload[] = [
                'request_id' => (int) $requestId,
                'thread_id' => (int) ($summary['thread_id'] ?? 0),
                'total_messages' => (int) ($summary['total_messages'] ?? 0),
                'unread_count' => (int) ($summary['unread_count'] ?? 0),
                'last_message_text' => (string) ($summary['last_message_text'] ?? ''),
                'last_message_type' => (string) ($summary['last_message_type'] ?? ''),
                'last_sender_name' => \Src\classes\UserDisplay::publicHandleFromRow(
                    $summary,
                    'last_sender_username',
                    'last_sender_name',
                    ''
                ),
                'last_message_at' => (string) ($summary['last_message_at'] ?? ''),
            ];
        }

        echo json_encode([
            'count' => count($payload),
            'summaries' => $payload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function requestChats()
    {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        $canManageAllRequests = ClassAccess::can('requests.manage', $user);
        $myProperties = Property::getByAffiliate($user['id']);
        $requestView = (string) ($_GET['view'] ?? '');
        $scope = 'user';
        $pageTitle = 'Conversas de Negociação';
        $pageDescription = 'Veja em um único lugar as negociações e mensagens das suas solicitações.';
        $requests = [];

        if ($canManageAllRequests) {
            $scope = 'admin';
            $pageTitle = 'Todas as Conversas';
            $pageDescription = 'Acompanhe as negociações ativas de todo o sistema.';
            $requests = Request::getAllWithUser();
        } else {
            $hasOwnedProperties = !empty($myProperties);

            if ($hasOwnedProperties) {
                $scope = 'owner';

                if ($requestView === 'sent') {
                    $requests = Request::getByUser($user['id']);
                } else {
                    $requestView = 'received';
                    $requests = Request::getByPropertyOwner($user['id']);
                }
            } else {
                $requestView = 'sent';
                $requests = Request::getByUser($user['id']);
            }
        }

        $selectedRequestId = (int) ($_GET['chat'] ?? 0);
        $selectedRequest = null;
        $messages = [];
        $thread = null;
        $chatWritable = false;

        if ($selectedRequestId > 0) {
            $selectedRequest = Request::getByIdWithContext($selectedRequestId);
            if ($selectedRequest) {
                $isRequester = (int) ($selectedRequest['user_id'] ?? 0) === (int) ($user['id'] ?? 0);
                $isOwner = (int) ($selectedRequest['owner_id'] ?? 0) === (int) ($user['id'] ?? 0);
                if ($isRequester || $isOwner || $canManageAllRequests) {
                    $thread = RequestChatThread::getOrCreateByRequestId($selectedRequestId);
                    RequestChatMessage::ensureNegotiationContactPolicyMessage($selectedRequestId);
                    if ($thread && !empty($thread['id'])) {
                        RequestChatRead::markThreadRead((int) $thread['id'], (int) ($user['id'] ?? 0));
                    }
                    $messages = $thread ? RequestChatMessage::getByThreadId((int) ($thread['id'] ?? 0), 200) : [];
                    $chatWritable = Request::isChatWritable($selectedRequest) && ($isRequester || $isOwner);
                } else {
                    $selectedRequest = null;
                    $selectedRequestId = 0;
                }
            } else {
                $selectedRequestId = 0;
            }
        }

        $requestChatSummaries = RequestChatMessage::getSummariesByRequestIds(array_map(static function (array $requestItem): int {
            return (int) ($requestItem['id'] ?? 0);
        }, $requests), (int) ($user['id'] ?? 0));

        $render = new ClassRender();
        $render->setTitle($pageTitle);
        $render->setDescription($pageDescription);
        $render->setKeywords('conversas, chat, solicitações');
        $render->setData([
            'user' => $user,
            'requests' => $requests,
            'requestChatSummaries' => $requestChatSummaries,
            'requestView' => $requestView,
            'scope' => $scope,
            'canManageAllRequests' => $canManageAllRequests,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'selectedRequestId' => $selectedRequestId,
            'selectedRequest' => $selectedRequest,
            'messages' => $messages,
            'thread' => $thread,
            'chatWritable' => $chatWritable,
        ]);
        $render->setDir('dashboard/request_chats');
        $render->renderLayout();
    }

    public function disputes()
    {
        $user = ClassAccess::requirePermission('requests.manage', 'dashboard', 'Acesso disponível apenas para gestão de solicitações');

        $perPage      = 20;
        $page         = max(1, (int) ($_GET['page'] ?? 1));
        $statusFilter = (string) ($_GET['dispute_status'] ?? ($_GET['status'] ?? 'all'));
        $allowedStatusFilters = ['all', 'nenhuma', 'aberta', 'em_analise', 'julgada_procedente', 'julgada_improcedente'];
        if (!in_array($statusFilter, $allowedStatusFilters, true)) {
            $statusFilter = 'all';
        }
        $offset       = ($page - 1) * $perPage;
        $totalDisputes = Request::countDisputes($statusFilter);
        $disputes     = Request::getDisputes($perPage, $offset, $statusFilter);
        $totalPages   = (int) ceil($totalDisputes / $perPage);

        $baseParams = [];
        if ($statusFilter !== 'all') {
            $baseParams['dispute_status'] = $statusFilter;
        }
        $paginationBaseQuery = http_build_query($baseParams);

        $render = new ClassRender();
        $render->setTitle('Painel de Disputas');
        $render->setDescription('Analise e resolva solicitações em disputa');
        $render->setKeywords('disputas, solicitações, moderação');
        $render->setData([
            'user' => $user,
            'disputes' => $disputes,
            'disputesTotal' => $totalDisputes,
            'statusFilter' => $statusFilter,
            'statusFilters' => [
                'all' => 'Todos os estados',
                'aberta' => 'Aberta',
                'em_analise' => 'Em análise',
                'julgada_procedente' => 'Julgada procedente',
                'julgada_improcedente' => 'Julgada improcedente',
                'nenhuma' => 'Nenhuma',
            ],
            'paginationBaseQuery' => $paginationBaseQuery,
            'page'       => $page,
            'totalPages' => $totalPages,
            'csrfField' => ClassCsrf::field(),
        ]);
        $render->setDir('dashboard/disputes');
        $render->renderLayout();
    }

    public function dispute($id)
    {
        $user = ClassAccess::requirePermission('requests.manage', 'dashboard', 'Acesso disponível apenas para gestão de solicitações');

        $requestId = (int) $id;
        if ($requestId <= 0) {
            header('Location: ' . DIRPAGE . 'dashboard/disputes?error=Disputa inválida');
            exit;
        }

        $request = Request::getByIdWithContext($requestId);
        if (!$request) {
            header('Location: ' . DIRPAGE . 'dashboard/disputes?error=Solicitação não encontrada');
            exit;
        }

        $logs = Log::getByEntity('request', $requestId);
        $timeline = array_reverse($logs);
        $decision = $this->extractDisputeDecisionFromLogs($logs);

        $render = new ClassRender();
        $render->setTitle('Detalhe da Disputa #' . $requestId);
        $render->setDescription('Linha do tempo e fundamentação da disputa');
        $render->setKeywords('disputa, timeline, moderação');
        $render->setData([
            'user' => $user,
            'request' => $request,
            'timeline' => $timeline,
            'decision' => $decision,
            'csrfField' => ClassCsrf::field(),
        ]);
        $render->setDir('dashboard/dispute_detail');
        $render->renderLayout();
    }

    public function requestChat($id)
    {
        ClassAuth::requireAuth();

        $requestId = (int) $id;
        if ($requestId <= 0) {
            header('Location: ' . DIRPAGE . 'dashboard/requestChats?error=' . rawurlencode('Solicitação inválida'));
            exit;
        }

        $params = ['chat' => $requestId];
        $requestView = trim((string) ($_GET['view'] ?? ''));
        if ($requestView !== '') {
            $params['view'] = $requestView;
        }

        header('Location: ' . DIRPAGE . 'dashboard/requestChats?' . http_build_query($params));
        exit;
    }

    public function requestChatMarkRead($id)
    {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        header('Content-Type: application/json; charset=utf-8');

        $requestId = (int) $id;
        if ($requestId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Solicitação inválida'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $request = Request::getByIdWithContext($requestId);
        if (!$request) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Solicitação não encontrada'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $isRequester = (int) ($request['user_id'] ?? 0) === (int) ($user['id'] ?? 0);
        $isOwner = (int) ($request['owner_id'] ?? 0) === (int) ($user['id'] ?? 0);
        $canManageAllRequests = ClassAccess::can('requests.manage', $user);
        if (!$isRequester && !$isOwner && !$canManageAllRequests) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sem permissão'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $thread = RequestChatThread::getOrCreateByRequestId($requestId);
        RequestChatMessage::ensureNegotiationContactPolicyMessage($requestId);
        if (!$thread || empty($thread['id'])) {
            echo json_encode(['ok' => true, 'request_id' => $requestId, 'unread_count' => 0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        RequestChatRead::markThreadRead((int) $thread['id'], (int) ($user['id'] ?? 0));

        $summaries = RequestChatMessage::getSummariesByRequestIds([$requestId], (int) ($user['id'] ?? 0));
        $summary = $summaries[$requestId] ?? [];
        $headerUnread = $canManageAllRequests
            ? RequestChatMessage::countUnreadByUser((int) ($user['id'] ?? 0))
            : RequestChatMessage::countUnreadForVisibleRequests((int) ($user['id'] ?? 0));

        echo json_encode([
            'ok' => true,
            'request_id' => $requestId,
            'unread_count' => (int) ($summary['unread_count'] ?? 0),
            'header_unread_chat_messages' => $headerUnread,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function requestChatMarkUnread($id)
    {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        header('Content-Type: application/json; charset=utf-8');

        $requestId = (int) $id;
        if ($requestId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Solicitação inválida'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $request = Request::getByIdWithContext($requestId);
        if (!$request) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Solicitação não encontrada'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $isRequester = (int) ($request['user_id'] ?? 0) === (int) ($user['id'] ?? 0);
        $isOwner = (int) ($request['owner_id'] ?? 0) === (int) ($user['id'] ?? 0);
        $canManageAllRequests = ClassAccess::can('requests.manage', $user);
        if (!$isRequester && !$isOwner && !$canManageAllRequests) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sem permissão'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $thread = RequestChatThread::getOrCreateByRequestId($requestId);
        RequestChatMessage::ensureNegotiationContactPolicyMessage($requestId);
        if (!$thread || empty($thread['id'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Sem mensagens para marcar'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if (!RequestChatRead::markThreadUnread((int) $thread['id'], (int) ($user['id'] ?? 0))) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Não há mensagens de outra parte para marcar'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $summaries = RequestChatMessage::getSummariesByRequestIds([$requestId], (int) ($user['id'] ?? 0));
        $summary = $summaries[$requestId] ?? [];
        $headerUnread = $canManageAllRequests
            ? RequestChatMessage::countUnreadByUser((int) ($user['id'] ?? 0))
            : RequestChatMessage::countUnreadForVisibleRequests((int) ($user['id'] ?? 0));

        echo json_encode([
            'ok' => true,
            'request_id' => $requestId,
            'unread_count' => (int) ($summary['unread_count'] ?? 0),
            'header_unread_chat_messages' => $headerUnread,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function requestChatFeed($id)
    {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        header('Content-Type: application/json; charset=utf-8');

        $requestId = (int) $id;
        if ($requestId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Solicitação inválida'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $request = Request::getByIdWithContext($requestId);
        if (!$request) {
            http_response_code(404);
            echo json_encode(['error' => 'Solicitação não encontrada'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $isRequester = (int) ($request['user_id'] ?? 0) === (int) ($user['id'] ?? 0);
        $isOwner = (int) ($request['owner_id'] ?? 0) === (int) ($user['id'] ?? 0);
        $canManageAllRequests = ClassAccess::can('requests.manage', $user);
        if (!$isRequester && !$isOwner && !$canManageAllRequests) {
            http_response_code(403);
            echo json_encode(['error' => 'Sem permissão para acessar este chat'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $thread = RequestChatThread::getOrCreateByRequestId($requestId);
        RequestChatMessage::ensureNegotiationContactPolicyMessage($requestId);
        if (!$thread || empty($thread['id'])) {
            echo json_encode(['messages' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        RequestChatRead::markThreadRead((int) $thread['id'], (int) ($user['id'] ?? 0));
        $messages = RequestChatMessage::getByThreadId((int) $thread['id'], 200);

        echo json_encode([
            'thread_id' => (int) $thread['id'],
            'messages' => array_map(static function (array $message): array {
                $attachmentPath = (string) ($message['attachment_path'] ?? '');
                $attachmentUrl = $attachmentPath !== '' ? \App\model\Request::paymentProofPublicUrl($attachmentPath) : '';
                return [
                    'id' => (int) ($message['id'] ?? 0),
                    'sender_user_id' => (int) ($message['sender_user_id'] ?? 0),
                    'sender_name' => \Src\classes\UserDisplay::publicHandleFromRow(
                        $message,
                        'sender_username',
                        'sender_name',
                        'Utilizador'
                    ),
                    'message_type' => (string) ($message['message_type'] ?? 'text'),
                    'message_text' => RequestChatMessage::displayText((string) ($message['message_text'] ?? '')),
                    'attachment_path' => $attachmentPath,
                    'attachment_url' => $attachmentUrl,
                    'created_at' => (string) ($message['created_at'] ?? ''),
                    'created_at_label' => !empty($message['created_at'])
                        ? date('d/m/Y H:i', strtotime((string) $message['created_at']))
                        : '',
                ];
            }, $messages),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function extractDisputeDecisionFromLogs(array $logs): ?array
    {
        foreach ($logs as $log) {
            $action = (string) ($log['action'] ?? '');
            if ($action !== 'update_request_status') {
                continue;
            }

            $details = (string) ($log['details'] ?? '');
            if (!preg_match('/Status atualizado para:\s*([a-z_]+)/i', $details, $statusMatch)) {
                continue;
            }

            $targetStatus = strtolower(trim((string) ($statusMatch[1] ?? '')));
            if (!in_array($targetStatus, ['fechado_ganho', 'cancelado'], true)) {
                continue;
            }

            $note = '';
            $evidencePath = '';

            if (preg_match('/\|\s*Observação:\s*(.*?)(?:\s*\|\s*Evidência:|$)/u', $details, $noteMatch)) {
                $note = trim((string) ($noteMatch[1] ?? ''));
            }

            if (preg_match('/\|\s*Evidência:\s*(.+)$/u', $details, $evidenceMatch)) {
                $evidencePath = trim((string) ($evidenceMatch[1] ?? ''));
            }

            return [
                'status' => $targetStatus,
                'status_label' => Request::statusLabel($targetStatus),
                'note' => $note,
                'evidence_path' => $evidencePath,
                'decided_at' => (string) ($log['created_at'] ?? ''),
                'decided_by' => (string) ($log['actor_name'] ?? 'Moderação'),
            ];
        }

        return null;
    }
}
