<?php
namespace App\controller;

use Src\classes\ClassRender;
use Src\classes\ClassAuth;
use Src\classes\ClassAccess;
use Src\classes\ClassCsrf;
use Src\classes\ClassDocumentValidator;
use Src\classes\ClassSettings;
use Src\classes\ClassTrustBadgeEligibility;
use App\model\Request;
use App\model\Commission;
use App\model\User;
use App\model\Property;
use App\model\Log;
use App\model\PropertyAffiliate;
use App\model\PropertyBoostRequest;
use App\model\Notification;
use App\model\Document;
use App\model\RequestChatThread;
use App\model\RequestChatMessage;
use App\model\RequestChatRead;
use App\model\Favorite;
use App\model\SubscriptionPlan;
use App\model\UserSubscription;
use App\model\PaymentMethod;
use App\model\SystemPaymentChannel;
use App\model\PaymentTransaction;
use App\services\CommissionSettlementService;
use Src\classes\ClassPlan;
use Dompdf\Dompdf;
use Dompdf\Options;

class ControllerDashboard {
    public function index() {
        ClassAuth::requireAuth();

        $user = ClassAuth::user();
        if (ClassAccess::canUseAccountStatusPage($user)) {
            header('Location: ' . DIRPAGE . 'dashboard/accountStatus');
            exit;
        }
        $requests = Request::getByUser($user['id']);
        $stats = User::getAffiliateStats($user['id']);
        $trust = User::getTrustMetrics($user['id']);
        $notifications = Notification::getLatestByUser((int) $user['id'], 8);
        $unreadNotifications = Notification::countUnreadByUser((int) $user['id']);
        $rejectedDocuments = Document::getRejectedByUser((int) $user['id']);

        $render = new ClassRender();
        $render->setTitle("Meu Painel");
        $render->setDescription("Gerencie suas solicitações e comissões");
        $render->setKeywords("painel, solicitações");
        $render->setData([
            'user' => $user,
            'requests' => $requests,
            'stats' => $stats,
            'trust' => $trust,
            'notifications' => $notifications,
            'unreadNotifications' => $unreadNotifications,
            'rejectedDocuments' => $rejectedDocuments,
            'csrfField' => ClassCsrf::field()
        ]);
        $render->setDir("dashboard/index");
        $render->renderLayout();
    }

    public function markNotificationsRead() {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard?error=Token inválido');
            exit;
        }

        Notification::markAllAsReadByUser((int) $user['id']);
        header('Location: ' . DIRPAGE . 'dashboard?success=Notificações marcadas como lidas');
        exit;
    }

    public function markNotificationRead($id) {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        $notificationId = (int) $id;
        $isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            if ($isAjax) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'Token inválido',
                    'csrf_token' => ClassCsrf::get(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            header('Location: ' . DIRPAGE . 'dashboard?error=Token inválido');
            exit;
        }

        $marked = false;
        if ($notificationId > 0) {
            $marked = Notification::markAsReadByUser($notificationId, (int) $user['id']);
        }

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => $marked,
                'unread_count' => Notification::countUnreadByUser((int) $user['id']),
                'csrf_token' => ClassCsrf::get(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        header('Location: ' . DIRPAGE . 'dashboard#notifications');
        exit;
    }

    public function markNotificationUnread($id) {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        $notificationId = (int) $id;
        $isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            if ($isAjax) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'Token inválido',
                    'csrf_token' => ClassCsrf::get(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            header('Location: ' . DIRPAGE . 'dashboard?error=Token inválido');
            exit;
        }

        $marked = false;
        if ($notificationId > 0) {
            $marked = Notification::markAsUnreadByUser($notificationId, (int) $user['id']);
        }

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => $marked,
                'unread_count' => Notification::countUnreadByUser((int) $user['id']),
                'csrf_token' => ClassCsrf::get(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        header('Location: ' . DIRPAGE . 'dashboard#notifications');
        exit;
    }

    public function notificationsFeed() {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        header('Content-Type: application/json; charset=utf-8');

        $notifications = Notification::getLatestByUser((int) $user['id'], 5);
        $payload = array_map(static function (array $notification): array {
            return [
                'id' => (int) ($notification['id'] ?? 0),
                'title' => (string) ($notification['title'] ?? ''),
                'message' => (string) ($notification['message'] ?? ''),
                'type' => (string) ($notification['type'] ?? ''),
                'type_label' => (string) ($notification['type_label'] ?? ''),
                'target_url' => (string) ($notification['target_url'] ?? (DIRPAGE . 'notification/inbox')),
                'mark_read_url' => DIRPAGE . 'dashboard/markNotificationRead/' . (int) ($notification['id'] ?? 0),
                'mark_unread_url' => DIRPAGE . 'dashboard/markNotificationUnread/' . (int) ($notification['id'] ?? 0),
                'action_label' => (string) ($notification['action_label'] ?? 'Abrir'),
                'is_read' => !empty($notification['is_read']),
                'created_at' => (string) ($notification['created_at'] ?? ''),
                'created_at_label' => !empty($notification['created_at'])
                    ? date('d/m/Y H:i', strtotime((string) $notification['created_at']))
                    : '',
            ];
        }, $notifications);

        echo json_encode([
            'unread_count' => Notification::countUnreadByUser((int) $user['id']),
            'notifications' => $payload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function dashboard() {
        // Alias para index()
        $this->index();
    }

    public function requests() {
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
        $render->setKeywords("solicitações");
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
        $render->setDir("dashboard/requests");
        $render->renderLayout();
    }

    public function requestChatSummariesFeed() {
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

    public function requestChats() {
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

        $requestChatSummaries = RequestChatMessage::getSummariesByRequestIds(array_map(static function (array $requestItem): int {
            return (int) ($requestItem['id'] ?? 0);
        }, $requests), (int) ($user['id'] ?? 0));

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

    public function disputes() {
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

    public function dispute($id) {
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

    public function requestChat($id) {
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

    public function requestChatFeed($id) {
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
                    'message_text' => (string) ($message['message_text'] ?? ''),
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

    private function extractDisputeDecisionFromLogs(array $logs): ?array {
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

    public function commissions() {
        header('Location: ' . DIRPAGE . 'dashboard/afiliados?tab=commissions');
        exit;
    }

    public function promotor() {
        header('Location: ' . DIRPAGE . 'dashboard/afiliados');
        exit;
    }

    public function afiliados() {
        ClassAuth::requireAuth();
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores não acedem a esta área');

        $hasProperties = Property::countByOwner((int) $user['id']) > 0;
        $validTabs = ['referrals', 'commissions', 'my_affiliates', 'affiliate_requests'];
        $defaultTab = $hasProperties ? 'affiliate_requests' : 'referrals';
        $activeTab = in_array($_GET['tab'] ?? '', $validTabs, true) ? $_GET['tab'] : $defaultTab;

        // If user chose an affiliate-only tab but is not an affiliate, fall back
        if (in_array($activeTab, ['referrals', 'commissions'], true) && empty($user['is_affiliate'])) {
            $activeTab = $hasProperties ? 'affiliate_requests' : 'referrals';
        }

        // Affiliate data (only loaded when needed)
        $commissions            = [];
        $summary                = [];
        $myAffiliatedProperties = [];

        // Owner-only tabs are hidden when the user has no properties.
        if (in_array($activeTab, ['my_affiliates', 'affiliate_requests'], true) && !$hasProperties) {
            $activeTab = 'referrals';
        }

        $perPage    = 20;
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $offset     = ($page - 1) * $perPage;
        $totalPages = 1;
        $commissionsTotal  = 0;
        $myAffiliatesTotal = 0;
        $myAffiliates      = [];

        if (!empty($user['is_affiliate'])) {
            $summary = Commission::getAffiliateSummary((int) $user['id']);
            if ($activeTab === 'commissions') {
                $commissionsTotal = Commission::countByAffiliate((int) $user['id']);
                $commissions      = Commission::getByAffiliate($user['id'], $perPage, $offset);
                $totalPages       = (int) ceil($commissionsTotal / max(1, $perPage));
            } else {
                // referrals tab: affiliated_properties is usually small, load all
                $myAffiliatedProperties = Property::getActiveAffiliationsForUser((int) $user['id']);
            }
        }

        if (in_array($activeTab, ['my_affiliates', 'affiliate_requests'], true)) {
            $statusFilter = $activeTab === 'affiliate_requests' ? 'pendente' : null;
            $myAffiliatesTotal = PropertyAffiliate::countByOwner((int) $user['id'], $statusFilter);
            $myAffiliates      = PropertyAffiliate::getByOwner((int) $user['id'], $perPage, $offset, $statusFilter);
            $totalPages        = (int) ceil($myAffiliatesTotal / max(1, $perPage));
        }

        $render = new ClassRender();
        $render->setTitle('Afiliados');
        $render->setDescription('Indicações, comissões e promotores dos seus imóveis');
        $render->setKeywords('afiliados, indicações, comissões, promotores');
        $render->setData([
            'user'                   => $user,
            'commissions'            => $commissions,
            'commissionsTotal'       => $commissionsTotal,
            'summary'                => $summary,
            'affiliated_properties'  => $myAffiliatedProperties,
            'affiliate_code'         => $user['affiliate_code'] ?? '',
            'my_affiliates'          => $myAffiliates,
            'myAffiliatesTotal'      => $myAffiliatesTotal,
            'has_properties'         => $hasProperties,
            'active_tab'             => $activeTab,
            'csrfField'              => ClassCsrf::field(),
            'page'                   => $page,
            'totalPages'             => $totalPages,
        ]);
        $render->setDir('dashboard/afiliados');
        $render->renderLayout();
    }

    public function payments() {
        $admin = ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponível apenas para a equipa financeira');

        // Resolve active tab (mirrors view logic so controller can load only needed data)
        $requestedTab  = isset($_GET['tab']) ? (string) $_GET['tab'] : '';
        $allowedTabs   = ['trust', 'boosts', 'commissions', 'subscriptions', 'history'];
        if (!in_array($requestedTab, $allowedTabs, true)) {
            if (!empty($_GET['boost_id']))    { $requestedTab = 'boosts'; }
            elseif (!empty($_GET['highlight'])) { $requestedTab = 'commissions'; }
            elseif (!empty($_GET['user']))      { $requestedTab = 'trust'; }
            else                                { $requestedTab = 'trust'; }
        }
        $activeTab = $requestedTab;

        $perPage = 20;
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        // Always load counts for KPIs and tab labels
        $pendingCommissionsCount = Commission::countAllPending();
        $affiliatePayoutCount = Commission::countAwaitingAffiliatePayout();
        $commissionsTabPendingCount = $pendingCommissionsCount + $affiliatePayoutCount;
        $pendingBoostsCount      = PropertyBoostRequest::countPending();
        $pendingTrustCount       = \App\model\User::countTrustBadgePendingUsers();
        $pendingSubscriptionFeesCount =
            \App\model\PaymentTransaction::countList('pendente', 'subscription_fee') +
            \App\model\PaymentTransaction::countList('processando', 'subscription_fee');
        $pendingTotal            = Commission::sumAllPendingAmount();
        $commissionsAffiliatePendingAmount = Commission::sumAwaitingAffiliatePayoutAmount();
        $commissionsPendingTotal = $pendingTotal + $commissionsAffiliatePendingAmount;

        // Load only the active tab's paginated data
        $pendingCommissions = [];
        $affiliatePayoutQueue = [];
        $pendingBoosts      = [];
        $pendingTrust       = [];
        $subscriptionTransactions = [];
        $subscriptionTransactionsCount = 0;
        $allCommissions     = [];
        $allCommissionsCount = 0;
        $allPaymentTransactions = [];
        $allPaymentTransactionsCount = 0;
        $totalPages         = 1;

        if ($activeTab === 'trust') {
            $pendingTrust = \App\model\User::getTrustBadgePendingUsers($perPage, $offset);
            $totalPages   = (int) ceil($pendingTrustCount / max(1, $perPage));
        } elseif ($activeTab === 'boosts') {
            $pendingBoosts = PropertyBoostRequest::getPending($perPage, $offset);
            $totalPages    = (int) ceil($pendingBoostsCount / max(1, $perPage));
        } elseif ($activeTab === 'commissions') {
            $pendingCommissions = Commission::getAllPending($perPage, $offset);
            $affiliatePayoutQueue = array_values(array_filter(
                Commission::getAwaitingAffiliatePayout(50),
                static fn(array $row): bool => Commission::needsAffiliatePayout($row)
            ));
            $totalPages         = (int) ceil($pendingCommissionsCount / max(1, $perPage));
        } elseif ($activeTab === 'subscriptions') {
            $subscriptionTransactionsCount = \App\model\PaymentTransaction::countList(null, 'subscription_fee', ['rejeitado']);
            $subscriptionTransactions = \App\model\PaymentTransaction::getList(null, 'subscription_fee', $perPage, $offset, ['rejeitado']);
            $totalPages = (int) ceil($subscriptionTransactionsCount / max(1, $perPage));
        } elseif ($activeTab === 'history') {
            $allPaymentTransactionsCount = \App\model\PaymentTransaction::countList(null, null);
            $allPaymentTransactions = \App\model\PaymentTransaction::getList(null, null, $perPage, $offset);
            $totalPages = (int) ceil($allPaymentTransactionsCount / max(1, $perPage));
        }

        $render = new ClassRender();
        $render->setTitle('Central de Pagamentos');
        $render->setDescription('Gestão financeira de comissões, destaques e selo de confiança');
        $render->setKeywords('pagamentos, comissões, destaques, selo de confiança');
        $commissionSection = 'owner';
        $highlightCommissionId = max(0, (int) ($_GET['highlight'] ?? 0));
        if ($activeTab === 'commissions') {
            $requestedSection = trim((string) ($_GET['section'] ?? ''));
            if (in_array($requestedSection, ['owner', 'affiliate'], true)) {
                $commissionSection = $requestedSection;
            } elseif ($highlightCommissionId > 0) {
                $highlightInOwnerQueue = false;
                foreach ($pendingCommissions as $row) {
                    if ((int) ($row['id'] ?? 0) === $highlightCommissionId) {
                        $highlightInOwnerQueue = true;
                        break;
                    }
                }
                $highlightInAffiliateQueue = false;
                if (!$highlightInOwnerQueue) {
                    foreach ($affiliatePayoutQueue as $row) {
                        if ((int) ($row['id'] ?? 0) === $highlightCommissionId) {
                            $highlightInAffiliateQueue = true;
                            break;
                        }
                    }
                }
                if ($highlightInOwnerQueue) {
                    $commissionSection = 'owner';
                } elseif ($highlightInAffiliateQueue) {
                    $commissionSection = 'affiliate';
                } else {
                    $commissionSection = $pendingCommissionsCount > 0 ? 'owner' : 'affiliate';
                }
            } elseif ($pendingCommissionsCount > 0) {
                $commissionSection = 'owner';
            } elseif ($affiliatePayoutCount > 0) {
                $commissionSection = 'affiliate';
            }
        }

        $render->setData([
            'user'               => $admin,
            'pendingCommissions' => $pendingCommissions,
            'affiliatePayoutQueue' => $affiliatePayoutQueue,
            'affiliatePayoutCount' => $affiliatePayoutCount,
            'commissionSection' => $commissionSection,
            'highlightCommissionId' => $highlightCommissionId,
            'pendingBoosts'      => $pendingBoosts,
            'pendingTrust'       => $pendingTrust,
            'subscriptionTransactions' => $subscriptionTransactions,
            'subscriptionTransactionsCount' => $subscriptionTransactionsCount,
            'allCommissions'     => $allCommissions,
            'allCommissionsCount' => $allCommissionsCount,
            'allPaymentTransactions' => $allPaymentTransactions,
            'allPaymentTransactionsCount' => $allPaymentTransactionsCount,
            'pendingTotal'       => $pendingTotal,
            'commissionsPendingTotal' => $commissionsPendingTotal,
            'commissionsAffiliatePendingAmount' => $commissionsAffiliatePendingAmount,
            'pendingCommissionsCount' => $pendingCommissionsCount,
            'commissionsTabPendingCount' => $commissionsTabPendingCount,
            'pendingBoostsCount'      => $pendingBoostsCount,
            'pendingTrustCount'       => $pendingTrustCount,
            'pendingSubscriptionFeesCount' => $pendingSubscriptionFeesCount,
            'activeTab'               => $activeTab,
            'page'                    => $page,
            'totalPages'              => $totalPages,
        ]);
        $render->setDir('dashboard/payments');
        $render->renderLayout();
    }

    public function confirmPayment($id) {
        $admin = ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponível apenas para a equipa financeira');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?error=Token inválido');
            exit;
        }

        $commission = Commission::findById((int) $id);
        if (!$commission) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&error=Comissão não encontrada');
            exit;
        }

        if (!Commission::canValidateOwnerPayment($commission)) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&error=' . rawurlencode('Comprovativo em falta ou comissão não está aguardando validação.'));
            exit;
        }

        $reference = trim($_POST['payment_reference'] ?? '');
        if ($reference === '') {
            $reference = trim((string) ($commission['owner_payment_reference'] ?? ''));
        }

        if (!CommissionSettlementService::approveOwnerPayment((int) $id, (int) $admin['id'], $reference)) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&error=' . rawurlencode('Não foi possível aprovar o pagamento.'));
            exit;
        }

        Log::create([
            'user_id'     => $admin['id'],
            'action'      => 'confirm_payment',
            'entity_type' => 'commission',
            'entity_id'   => (int) $id,
            'details'     => 'Pagamento do proprietário aprovado. Ref: ' . ($reference ?: 'N/A'),
        ]);

        $ownerId = (int) ($commission['owner_id'] ?? 0);
        if ($ownerId > 0) {
            Notification::notifyUser(
                $ownerId,
                'commission_owner_payment_confirmed',
                'Comissão confirmada',
                'O pagamento da comissão de ' . number_format((float) ($commission['amount'] ?? 0), 0, ',', '.') . ' Kz foi confirmado pela equipa.'
                . ($reference ? ' Ref: ' . $reference : ''),
                ['commission_id' => (int) $id, 'amount' => (float) ($commission['amount'] ?? 0)],
                (int) $admin['id']
            );
        }

        if (Commission::hasValidAffiliate($commission)) {
            $affiliateUserId = (int) ($commission['affiliate_id'] ?? 0);
            Notification::notifyUser(
                $affiliateUserId,
                'commission_payout_pending',
                'Comissão a receber',
                'O pagamento do proprietário foi validado. A sua comissão de '
                . number_format((float) ($commission['affiliate_amount'] ?? 0), 0, ',', '.')
                . ' Kz será transferida para a conta registada na plataforma.',
                ['commission_id' => (int) $id, 'amount' => (float) ($commission['affiliate_amount'] ?? 0)],
                (int) $admin['id']
            );
        }

        header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&success=' . rawurlencode('Pagamento aprovado e liquidado no sistema.'));
        exit;
    }

    public function rejectCommissionOwnerPayment($id) {
        $admin = ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponível apenas para a equipa financeira');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&error=Token inválido');
            exit;
        }

        $commission = Commission::findById((int) $id);
        if (!$commission || !Commission::canValidateOwnerPayment($commission)) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&error=' . rawurlencode('Não foi possível rejeitar este pagamento.'));
            exit;
        }

        $reason = trim((string) ($_POST['rejection_reason'] ?? ''));
        if (!CommissionSettlementService::rejectOwnerPayment((int) $id, (int) $admin['id'], $reason)) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&error=' . rawurlencode('Não foi possível rejeitar o pagamento.'));
            exit;
        }

        Log::create([
            'user_id' => (int) $admin['id'],
            'action' => 'reject_commission_owner_payment',
            'entity_type' => 'commission',
            'entity_id' => (int) $id,
            'details' => 'Pagamento rejeitado' . ($reason !== '' ? ': ' . $reason : ''),
        ]);

        $ownerId = (int) ($commission['owner_id'] ?? 0);
        if ($ownerId > 0) {
            Notification::notifyUser(
                $ownerId,
                'commission_owner_payment_rejected',
                'Comprovativo rejeitado',
                'O comprovativo da comissão de ' . number_format((float) ($commission['amount'] ?? 0), 0, ',', '.') . ' Kz foi rejeitado.'
                . ($reason !== '' ? ' Motivo: ' . $reason : ' Envie um novo comprovativo.'),
                ['commission_id' => (int) $id],
                (int) $admin['id']
            );
        }

        header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&success=' . rawurlencode('Pagamento rejeitado. O proprietário pode reenviar o comprovativo.'));
        exit;
    }

    public function confirmAffiliatePayout($id) {
        $admin = ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponível apenas para a equipa financeira');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&error=Token inválido');
            exit;
        }

        $commissionId = (int) $id;
        $commission = Commission::findById($commissionId);
        if (!$commission || !Commission::needsAffiliatePayout($commission)) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&error=' . rawurlencode('Pagamento ao afiliado indisponível para esta comissão.'));
            exit;
        }

        $affiliateId = (int) ($commission['affiliate_id'] ?? 0);
        if ($affiliateId <= 0 || !\App\model\UserPaymentAccount::getDefaultActiveForUser($affiliateId)) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&error=' . rawurlencode('O afiliado deve registar uma conta de recebimento antes de confirmar o pagamento.'));
            exit;
        }

        $proofFile = $_FILES['payout_proof'] ?? [];
        $proofError = (int) ($proofFile['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($proofError === UPLOAD_ERR_NO_FILE || trim((string) ($proofFile['tmp_name'] ?? '')) === '') {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&error=' . rawurlencode('Comprovativo obrigatório. Anexe a imagem do pagamento ao afiliado.'));
            exit;
        }

        $upload = $this->uploadCommissionPayoutProof($proofFile, $commissionId);
        if (!empty($upload['error']) || empty($upload['path'])) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&error=' . rawurlencode((string) ($upload['error'] ?? 'Comprovativo obrigatório.')));
            exit;
        }

        $reference = trim((string) ($_POST['payout_reference'] ?? ''));
        if (!CommissionSettlementService::confirmAffiliatePayout($commissionId, (int) $admin['id'], (string) $upload['path'], $reference)) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&error=' . rawurlencode('Não foi possível confirmar o pagamento ao afiliado.'));
            exit;
        }

        Log::create([
            'user_id' => (int) $admin['id'],
            'action' => 'confirm_affiliate_payout',
            'entity_type' => 'commission',
            'entity_id' => $commissionId,
            'details' => 'Pagamento ao afiliado confirmado. Ref: ' . ($reference !== '' ? $reference : 'N/A'),
        ]);

        $affiliateUserId = (int) ($commission['affiliate_id'] ?? 0);
        if ($affiliateUserId > 0) {
            Notification::notifyUser(
                $affiliateUserId,
                'commission_paid',
                'Comissão paga',
                'O pagamento da sua comissão de ' . number_format((float) ($commission['affiliate_amount'] ?? 0), 0, ',', '.') . ' Kz foi confirmado.'
                . ($reference !== '' ? ' Ref: ' . $reference : ''),
                ['commission_id' => $commissionId, 'amount' => (float) ($commission['affiliate_amount'] ?? 0)],
                (int) $admin['id']
            );
        }

        header('Location: ' . DIRPAGE . 'dashboard/payments?tab=commissions&success=' . rawurlencode('Pagamento ao afiliado registado. Comissão marcada como paga.'));
        exit;
    }

    public function cancelPayment($id) {
        $admin = ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponível apenas para a equipa financeira');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?error=Token inválido');
            exit;
        }

        $commission = Commission::findById((int) $id);
        if (!$commission) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?error=Comissão não encontrada');
            exit;
        }

        if (!Commission::markAsCancelled((int) $id)) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?error=Não+foi+possível+cancelar+a+comissão+(estado+inválido+ou+já+processado)');
            exit;
        }

        Log::create([
            'user_id'     => $admin['id'],
            'action'      => 'cancel_payment',
            'entity_type' => 'commission',
            'entity_id'   => (int) $id,
            'details'     => 'Comissão cancelada',
        ]);

        header('Location: ' . DIRPAGE . 'dashboard/payments?success=Comissão cancelada');
        exit;
    }

    private function uploadCommissionOwnerProof(array $file, int $userId): array {
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => 'Comprovativo de pagamento obrigatório.'];
        }

        $errorMap = [
            UPLOAD_ERR_INI_SIZE => 'O comprovativo excede o limite do servidor.',
            UPLOAD_ERR_FORM_SIZE => 'O comprovativo excede o limite permitido no formulário.',
            UPLOAD_ERR_PARTIAL => 'O comprovativo foi enviado parcialmente.',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária de upload indisponível.',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar o comprovativo no disco.',
            UPLOAD_ERR_EXTENSION => 'Upload bloqueado pelo servidor.',
        ];

        if ($errorCode !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => $errorMap[$errorCode] ?? 'Erro ao enviar comprovativo.'];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['path' => null, 'error' => 'Comprovativo inválido.'];
        }
        if ($size <= 0 || $size > (512 * 1024)) {
            return ['path' => null, 'error' => 'O comprovativo deve ter até 512 KB.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string) finfo_file($finfo, $tmpName) : '';
        if ($finfo) {
            finfo_close($finfo);
        }
        $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
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

        $uploadDirRelative = 'public/storage/uploads/commission_proofs/';
        $uploadDir = DIRREQ . $uploadDirRelative;
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return ['path' => null, 'error' => 'Não foi possível preparar a pasta para comprovativos.'];
        }

        try {
            $suffix = bin2hex(random_bytes(4));
        } catch (\Exception $e) {
            $suffix = substr(md5(uniqid('', true)), 0, 8);
        }

        $filename = 'commission_' . max(0, $userId) . '_' . time() . '_' . $suffix . '.' . $ext;
        $destination = $uploadDir . $filename;
        if (!move_uploaded_file($tmpName, $destination)) {
            return ['path' => null, 'error' => 'Falha ao guardar o comprovativo.'];
        }

        return ['path' => $uploadDirRelative . $filename, 'error' => null];
    }

    private function uploadCommissionPayoutProof(array $file, int $commissionId): array {
        $upload = $this->uploadCommissionOwnerProof($file, 0);
        if (!empty($upload['error']) || empty($upload['path'])) {
            return $upload;
        }

        $uploadDirRelative = 'public/storage/uploads/commission_payout_proofs/';
        $uploadDir = DIRREQ . $uploadDirRelative;
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return ['path' => null, 'error' => 'Não foi possível preparar a pasta para comprovativos de pagamento.'];
        }

        $basename = basename((string) $upload['path']);
        $destination = $uploadDir . 'payout_' . max(0, $commissionId) . '_' . $basename;
        $source = DIRREQ . ltrim((string) $upload['path'], '/');

        if (!@rename($source, $destination)) {
            if (!@copy($source, $destination)) {
                return ['path' => null, 'error' => 'Falha ao guardar o comprovativo de pagamento.'];
            }
            @unlink($source);
        }

        return ['path' => $uploadDirRelative . basename($destination), 'error' => null];
    }

    private function resolveIncomingPaymentMethods(): array {
        $methodsRaw = PaymentMethod::getActive('user');
        $paymentMethods = [];
        $channelsByMethod = [];
        foreach ($methodsRaw as $method) {
            $direction = (string) ($method['direction'] ?? 'both');
            if (!in_array($direction, ['incoming', 'both'], true)) {
                continue;
            }
            $methodId = (int) ($method['id'] ?? 0);
            if ($methodId <= 0) {
                continue;
            }
            $paymentMethods[] = $method;
            $channelsByMethod[$methodId] = SystemPaymentChannel::getActiveByMethodId($methodId);
        }

        return [$paymentMethods, $channelsByMethod];
    }

    public function commissionPayments() {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Área disponível para proprietários.');
        $ownerId = (int) ($user['id'] ?? 0);

        $activeTab = trim((string) ($_GET['tab'] ?? 'pendentes'));
        if (!in_array($activeTab, ['pendentes', 'pago', 'cancelado'], true)) {
            $activeTab = 'pendentes';
        }

        $pending = [];
        $historyCommissions = [];
        $historyCounts = [
            'pago' => Commission::countHistoryByOwner($ownerId, 'pago'),
            'cancelado' => Commission::countHistoryByOwner($ownerId, 'cancelado'),
        ];

        if ($activeTab === 'pendentes') {
            $pending = Commission::getPayableByOwner($ownerId);
        } else {
            $historyCommissions = Commission::getHistoryByOwner($ownerId, $activeTab);
        }

        $render = new ClassRender();
        $render->setTitle('Pagar comissões');
        $render->setDescription('Regularize as comissões pendentes dos seus fechos comerciais');
        $render->setKeywords('comissão, pagamento, proprietário');
        $render->setData([
            'user' => $user,
            'activeTab' => $activeTab,
            'pendingCommissions' => $pending,
            'historyCommissions' => $historyCommissions,
            'historyCounts' => $historyCounts,
            'pendingCount' => Commission::countPendingByOwner($ownerId),
            'csrfField' => ClassCsrf::field(),
        ]);
        $render->setDir('dashboard/commission_payments');
        $render->renderLayout();
    }

    public function commissionPayment($id) {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Área disponível para proprietários.');
        $commissionId = (int) $id;
        $commission = Commission::findPayableForOwner($commissionId, (int) ($user['id'] ?? 0));
        if (!$commission) {
            header('Location: ' . DIRPAGE . 'dashboard/commissionPayments?error=' . rawurlencode('Comissão não encontrada ou já regularizada.'));
            exit;
        }

        if (!Commission::canOwnerSubmitPayment($commission)) {
            if (Commission::hasOwnerPaymentSubmitted($commission)) {
                header('Location: ' . DIRPAGE . 'dashboard/commissionPayments?success=' . rawurlencode('Comprovativo já enviado. Aguarde validação da equipa financeira.'));
            } else {
                header('Location: ' . DIRPAGE . 'dashboard/commissionPayments?error=' . rawurlencode('Esta comissão não está disponível para pagamento.'));
            }
            exit;
        }

        [$paymentMethods, $channelsByMethod] = $this->resolveIncomingPaymentMethods();
        if (empty($paymentMethods)) {
            header('Location: ' . DIRPAGE . 'dashboard/commissionPayments?error=' . rawurlencode('Nenhum método de pagamento está disponível no momento. Contacte o suporte.'));
            exit;
        }

        $dueAt = (string) ($commission['due_at'] ?? '');
        $isOverdue = $dueAt !== '' && strtotime($dueAt) < time();

        $render = new ClassRender();
        $render->setTitle('Pagar comissão');
        $render->setDescription('Envie o comprovativo de pagamento da comissão');
        $render->setKeywords('comissão, pagamento, comprovativo');
        $render->setData([
            'user' => $user,
            'commission' => $commission,
            'dueAtFormatted' => $dueAt !== '' ? date('d/m/Y H:i', strtotime($dueAt)) : '—',
            'isOverdue' => $isOverdue,
            'paymentMethods' => $paymentMethods,
            'channelsByMethod' => $channelsByMethod,
            'csrfField' => ClassCsrf::field(),
        ]);
        $render->setDir('dashboard/commission_payment');
        $render->renderLayout();
    }

    public function submitCommissionPayment($id) {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Área disponível para proprietários.');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/commissionPayments?error=Token inválido');
            exit;
        }

        $commissionId = (int) $id;
        $commission = Commission::findPayableForOwner($commissionId, (int) ($user['id'] ?? 0));
        if (!$commission) {
            header('Location: ' . DIRPAGE . 'dashboard/commissionPayments?error=' . rawurlencode('Comissão não encontrada ou já regularizada.'));
            exit;
        }

        if (!Commission::canOwnerSubmitPayment($commission)) {
            header('Location: ' . DIRPAGE . 'dashboard/commissionPayments?success=' . rawurlencode('Comprovativo já enviado. Aguarde validação.'));
            exit;
        }

        $upload = $this->uploadCommissionOwnerProof($_FILES['payment_proof'] ?? [], (int) ($user['id'] ?? 0));
        if (!empty($upload['error']) || empty($upload['path'])) {
            header('Location: ' . DIRPAGE . 'dashboard/commissionPayment/' . $commissionId . '?error=' . rawurlencode((string) ($upload['error'] ?? 'Comprovativo obrigatório.')));
            exit;
        }

        $reference = trim((string) ($_POST['payment_reference'] ?? ''));
        $methodId = (int) ($_POST['payment_method_id'] ?? 0);
        $channelId = (int) ($_POST['system_channel_id'] ?? 0);
        if (!Commission::submitOwnerPayment($commissionId, (int) ($user['id'] ?? 0), (string) $upload['path'], $reference, $methodId, $channelId)) {
            header('Location: ' . DIRPAGE . 'dashboard/commissionPayment/' . $commissionId . '?error=' . rawurlencode('Não foi possível registar o comprovativo.'));
            exit;
        }

        Log::create([
            'user_id' => (int) ($user['id'] ?? 0),
            'action' => 'submit_commission_owner_payment',
            'entity_type' => 'commission',
            'entity_id' => $commissionId,
            'details' => 'Comprovativo enviado pelo proprietário. Ref: ' . ($reference !== '' ? $reference : 'N/A'),
        ]);

        Notification::notifyUsers(
            User::getActiveAdminIds(),
            'commission_owner_payment_submitted',
            'Comprovativo de comissão',
            'O proprietário enviou comprovativo de ' . number_format((float) ($commission['amount'] ?? 0), 0, ',', '.') . ' Kz para o imóvel "' . ((string) ($commission['property_title'] ?? '')) . '".',
            ['commission_id' => $commissionId, 'property_id' => (int) ($commission['property_id'] ?? 0)],
            (int) ($user['id'] ?? 0)
        );

        header('Location: ' . DIRPAGE . 'dashboard/commissionPayments?success=' . rawurlencode('Comprovativo enviado. As funcionalidades da plataforma voltam a ficar disponíveis após validação e aprovação pela equipa financeira.'));
        exit;
    }

    public function moderateUsers() {
        $user = ClassAccess::requirePermission('users.review', 'dashboard', 'Acesso disponível apenas para moderação');

        $allowedTabs = ['fila', 'pendentes', 'confianca', 'acessos', 'equipa'];
        $activeTab = (string) ($_GET['tab'] ?? 'pendentes');
        if (!in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'pendentes';
        }
        $canManageSuperAdminTabs = ClassAccess::isSuperAdmin($user);
        if ($activeTab === 'acessos' && !$canManageSuperAdminTabs) {
            $activeTab = 'pendentes';
        }
        if ($activeTab === 'equipa' && !$canManageSuperAdminTabs) {
            $activeTab = 'pendentes';
        }

        $perPage          = 20;
        $page             = max(1, (int) ($_GET['page'] ?? 1));
        $offset           = ($page - 1) * $perPage;
        $pendingUsersTotal = \App\model\User::countPendingUsers();
        $allPendingUsersForQueue = \App\model\User::getPendingUsers();
        $pendingUsers     = \App\model\User::getPendingUsers($perPage, $offset);
        $totalPages       = (int) ceil($pendingUsersTotal / max(1, $perPage));

        $allPendingTrust    = \App\model\User::getTrustBadgePendingUsers();
        $pendingTrustTotal  = count($allPendingTrust);
        $pendingTrust       = array_slice($allPendingTrust, 0, 5);

        $accessStatusFilter = (string) ($_GET['access_status'] ?? 'all');
        $allowedAccessStatusFilters = ['all', 'ativo', 'rejeitado', 'pendente', 'suspenso'];
        if (!in_array($accessStatusFilter, $allowedAccessStatusFilters, true)) {
            $accessStatusFilter = 'all';
        }
        $accessSearch = trim((string) ($_GET['access_search'] ?? ''));
        $accessPage = max(1, (int) ($_GET['access_page'] ?? 1));
        $accessOffset = ($accessPage - 1) * $perPage;
        if ($canManageSuperAdminTabs) {
            $manageableUsersTotal = \App\model\User::countManageableUsers($accessStatusFilter, $accessSearch);
            $manageableUsers = \App\model\User::getManageableUsers($perPage, $accessOffset, $accessStatusFilter, $accessSearch);
            $manageableUsersTotalPages = (int) ceil($manageableUsersTotal / max(1, $perPage));
            $administrativeUsers = User::getAdministrativeUsers();
        } else {
            $manageableUsersTotal = 0;
            $manageableUsers = [];
            $manageableUsersTotalPages = 1;
            $administrativeUsers = [];
        }

        $pendingProperties = Property::getPending();  // used only for queue, not displayed as table
        $openRequests = Request::getOpenForAdmin();
        $pendingDocuments = Document::getPending(200, 0);
        $queueData = $this->buildAdminQueue($allPendingUsersForQueue, $pendingTrust, $pendingProperties, $openRequests, $pendingDocuments);

        $userCompliance = [];
        foreach ($pendingUsers as $pendingUser) {
            $uid = (int) ($pendingUser['id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }

            $latestDoc = Document::getLatestByUser($uid);
            $compliance = Document::getComplianceStatus($uid);

            $stage = 'documental_validacao';
            if ($compliance === 'compliant') {
                $stage = 'aprovacao_final';
            }

            $userCompliance[$uid] = [
                'status' => $compliance,
                'stage' => $stage,
                'latest_document' => $latestDoc,
            ];
        }

        $render = new ClassRender();
        $render->setTitle("Moderação de Usuários");
        $render->setDescription("Aprovar ou rejeitar usuários pendentes");
        $render->setKeywords("moderação, usuários");
        $render->setData([
            'pendingUsers' => $pendingUsers,
            'pendingTrust'      => $pendingTrust,
            'pendingTrustTotal' => $pendingTrustTotal,
            'activeTab' => $activeTab,
            'canManageSuperAdminTabs' => $canManageSuperAdminTabs,
            'pendingProperties' => $pendingProperties,
            'openRequests' => $openRequests,
            'adminQueue' => $queueData['items'],
            'queueSummary' => $queueData['summary'],
            'userCompliance' => $userCompliance,
            'pendingUsersTotal' => $pendingUsersTotal,
            'page'              => $page,
            'totalPages'        => $totalPages,
            'manageableUsers' => $manageableUsers,
            'manageableUsersTotal' => $manageableUsersTotal,
            'manageableUsersPage' => $accessPage,
            'manageableUsersTotalPages' => $manageableUsersTotalPages,
            'accessStatusFilter' => $accessStatusFilter,
            'accessSearch' => $accessSearch,
            'administrativeUsers' => $administrativeUsers,
            'adminRoleOptions' => [
                'super_admin' => 'Admin Total',
                'moderador' => 'Admin Moderação',
                'suporte' => 'Admin Suporte',
                'financeiro' => 'Admin Financeiro',
            ],
            'csrfField' => ClassCsrf::field()
        ]);
        $render->setDir("dashboard/moderate_users");
        $render->renderLayout();

        // Audit: record that an admin viewed the moderation users list (sensitive)
        \App\model\Log::sensitiveRead((int) ($user['id'] ?? null), 'user_list', null, 'Viewed moderation users list, tab=' . $activeTab);
    }

    public function blockUserAccess($id) {
        $admin = ClassAccess::requireSuperAdmin('dashboard/moderateUsers', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Token inválido');
            exit;
        }

        $targetId = (int) $id;
        if ($targetId <= 0) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Utilizador inválido');
            exit;
        }

        if ($targetId === (int) ($admin['id'] ?? 0)) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Não é permitido bloquear o próprio acesso');
            exit;
        }

        $targetUser = User::findById($targetId);
        if (!$targetUser || !empty($targetUser['is_admin']) || (string) ($targetUser['role'] ?? 'utilizador') !== 'utilizador') {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Ação não permitida para este perfil');
            exit;
        }

        if (!empty($targetUser['suspended_until']) && strtotime((string) $targetUser['suspended_until']) > time()) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Utilizador já está com acesso suspenso');
            exit;
        }

        if (User::blockAccessByAdmin($targetId)) {
            Log::create([
                'user_id' => (int) ($admin['id'] ?? 0),
                'action' => 'block_user_access',
                'entity_type' => 'user',
                'entity_id' => $targetId,
                'details' => 'Acesso suspenso por moderação (users.status mantido: ' . (string) ($targetUser['status'] ?? '') . ')'
            ]);

            Notification::notifyUser(
                $targetId,
                'user_blocked',
                'Acesso suspenso',
                'O seu acesso à plataforma foi temporariamente suspenso pela equipa de moderação.',
                ['user_id' => $targetId],
                (int) ($admin['id'] ?? 0)
            );

            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&success=Acesso suspenso com sucesso');
            exit;
        }

        header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Não foi possível suspender o acesso');
        exit;
    }

    public function unblockUserAccess($id) {
        $admin = ClassAccess::requireSuperAdmin('dashboard/moderateUsers', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Token inválido');
            exit;
        }

        $targetId = (int) $id;
        if ($targetId <= 0) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Utilizador inválido');
            exit;
        }

        $targetUser = User::findById($targetId);
        if (!$targetUser || !empty($targetUser['is_admin']) || (string) ($targetUser['role'] ?? 'utilizador') !== 'utilizador') {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Ação não permitida para este perfil');
            exit;
        }

        if (empty($targetUser['suspended_until']) || strtotime((string) $targetUser['suspended_until']) <= time()) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Utilizador não está com acesso suspenso');
            exit;
        }

        if (User::unsuspendByAdmin($targetId)) {
            Log::create([
                'user_id' => (int) ($admin['id'] ?? 0),
                'action' => 'unblock_user_access',
                'entity_type' => 'user',
                'entity_id' => $targetId,
                'details' => 'Suspensão de acesso levantada (users.status: ' . (string) ($targetUser['status'] ?? '') . ')'
            ]);

            Notification::notifyUser(
                $targetId,
                'user_unblocked',
                'Suspensão levantada',
                'Já pode voltar a aceder à plataforma.',
                ['user_id' => $targetId],
                (int) ($admin['id'] ?? 0)
            );

            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&success=Suspensão levantada com sucesso');
            exit;
        }

        header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Não foi possível levantar a suspensão');
        exit;
    }

    public function suspendUserAccess($id) {
        $admin = ClassAccess::requireSuperAdmin('dashboard/moderateUsers', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Token inválido');
            exit;
        }

        $targetId = (int) $id;
        if ($targetId <= 0) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Utilizador inválido');
            exit;
        }

        if ($targetId === (int) ($admin['id'] ?? 0)) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Não é permitido suspender o próprio acesso');
            exit;
        }

        $suspendDays = max(1, min(365, (int) ($_POST['suspend_days'] ?? 0)));
        if ($suspendDays <= 0) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Número de dias inválido');
            exit;
        }

        $targetUser = User::findById($targetId);
        if (!$targetUser || !empty($targetUser['is_admin']) || (string) ($targetUser['role'] ?? 'utilizador') !== 'utilizador') {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Ação não permitida para este perfil');
            exit;
        }

        if ((string) ($targetUser['status'] ?? '') !== 'ativo') {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Somente utilizadores ativos podem ser suspensos');
            exit;
        }

        if (User::suspendByAdmin($targetId, $suspendDays)) {
            $until = date('d/m/Y', strtotime('+' . $suspendDays . ' days'));
            Log::create([
                'user_id' => (int) ($admin['id'] ?? 0),
                'action' => 'suspend_user_access',
                'entity_type' => 'user',
                'entity_id' => $targetId,
                'details' => 'Acesso suspenso por ' . $suspendDays . ' dias até ' . $until
            ]);

            Notification::notifyUser(
                $targetId,
                'user_suspended',
                'Acesso suspenso',
                'O seu acesso foi suspenso por ' . $suspendDays . ' dias, até ' . $until . '.',
                ['user_id' => $targetId, 'suspended_until' => $until],
                (int) ($admin['id'] ?? 0)
            );

            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&success=Utilizador suspenso com sucesso');
            exit;
        }

        header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Não foi possível suspender o utilizador');
        exit;
    }

    public function unsuspendUserAccess($id) {
        $admin = ClassAccess::requireSuperAdmin('dashboard/moderateUsers', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Token inválido');
            exit;
        }

        $targetId = (int) $id;
        if ($targetId <= 0) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Utilizador inválido');
            exit;
        }

        $targetUser = User::findById($targetId);
        if (!$targetUser || !empty($targetUser['is_admin']) || (string) ($targetUser['role'] ?? 'utilizador') !== 'utilizador') {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Ação não permitida para este perfil');
            exit;
        }

        if (empty($targetUser['suspended_until']) || strtotime((string) $targetUser['suspended_until']) <= time()) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Utilizador não está suspenso');
            exit;
        }

        if (User::unsuspendByAdmin($targetId)) {
            Log::create([
                'user_id' => (int) ($admin['id'] ?? 0),
                'action' => 'unsuspend_user_access',
                'entity_type' => 'user',
                'entity_id' => $targetId,
                'details' => 'Suspensão de acesso levantada manualmente'
            ]);

            Notification::notifyUser(
                $targetId,
                'user_unsuspended',
                'Suspensão levantada',
                'A sua suspensão foi levantada e o acesso voltou a ficar normal.',
                ['user_id' => $targetId],
                (int) ($admin['id'] ?? 0)
            );

            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&success=Suspensão levantada com sucesso');
            exit;
        }

        header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=acessos&error=Não foi possível levantar a suspensão');
        exit;
    }

    public function setAdminRole($id) {
        $admin = ClassAccess::requireSuperAdmin('dashboard/moderateUsers', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=equipa&error=Token inválido');
            exit;
        }

        $targetId = (int) $id;
        if ($targetId <= 0) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=equipa&error=Utilizador inválido');
            exit;
        }

        $newRole = strtolower(trim((string) ($_POST['role'] ?? '')));
        $allowedRoles = ['super_admin', 'moderador', 'suporte', 'financeiro'];
        if (!in_array($newRole, $allowedRoles, true)) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=equipa&error=Papel inválido');
            exit;
        }

        if ($targetId === (int) ($admin['id'] ?? 0) && $newRole !== 'super_admin') {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=equipa&error=Não pode reduzir o seu próprio nível de acesso por este ecrã');
            exit;
        }

        $targetUser = User::findById($targetId);
        if (!$targetUser) {
            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=equipa&error=Utilizador não encontrado');
            exit;
        }

        $currentRole = (string) ($targetUser['role'] ?? 'utilizador');
        $targetIsActive = (string) ($targetUser['status'] ?? '') === 'ativo';
        if ($currentRole === 'super_admin' && $newRole !== 'super_admin' && $targetIsActive) {
            $activeSuperAdmins = User::countActiveSuperAdmins();
            if ($activeSuperAdmins <= 1) {
                header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=equipa&error=Não é permitido rebaixar o último Admin Total ativo');
                exit;
            }
        }

        if (User::setAdministrativeRole($targetId, $newRole)) {
            Log::create([
                'user_id' => (int) ($admin['id'] ?? 0),
                'action' => 'set_admin_role',
                'entity_type' => 'user',
                'entity_id' => $targetId,
                'details' => 'Papel administrativo alterado para: ' . $newRole,
            ]);

            Notification::notifyUser(
                $targetId,
                'admin_role_updated',
                'Perfil administrativo atualizado',
                'O seu papel administrativo foi atualizado para ' . ClassAccess::roleLabel(['role' => $newRole, 'is_admin' => 1]) . '.',
                ['user_id' => $targetId, 'role' => $newRole],
                (int) ($admin['id'] ?? 0)
            );

            header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=equipa&success=Papel administrativo atualizado com sucesso');
            exit;
        }

        header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=equipa&error=Não foi possível atualizar o papel');
        exit;
    }

    private function redirectModerateUsersPendentes(string $type, string $message): void {
        $param = $type === 'success' ? 'success' : 'error';
        header('Location: ' . DIRPAGE . 'dashboard/moderateUsers?tab=pendentes&' . $param . '=' . rawurlencode($message));
        exit;
    }

    public function approveUser($id) {
        $user = ClassAccess::requirePermission('users.review', 'dashboard', 'Acesso disponível apenas para moderação');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            $this->redirectModerateUsersPendentes('error', 'Token inválido');
        }

        $targetId = (int) $id;
        if ($targetId <= 0) {
            $this->redirectModerateUsersPendentes('error', 'Utilizador inválido');
        }

        $targetUser = User::findById($targetId);
        if (!$targetUser || (string) ($targetUser['status'] ?? '') !== 'pendente') {
            $this->redirectModerateUsersPendentes('error', 'Utilizador não encontrado ou já não está pendente');
        }

        $compliance = Document::getComplianceStatus($targetId);
        if ($compliance !== 'compliant') {
            $this->redirectModerateUsersPendentes('error', 'Aprovação final bloqueada: validação documental pendente');
        }

        if (!\App\model\User::approveUser($targetId)) {
            $this->redirectModerateUsersPendentes('error', 'Não foi possível aprovar o utilizador');
        }

        \App\model\Log::create([
            'user_id' => $user['id'],
            'action' => 'approve_user',
            'entity_type' => 'user',
            'entity_id' => $targetId,
            'details' => 'Aprovação final concluída após validação documental'
        ]);

        Notification::notifyUser(
            $targetId,
            'user_approved',
            'Conta aprovada',
            'Sua conta foi aprovada e já está ativa na plataforma.',
            ['user_id' => $targetId],
            (int) $user['id']
        );

        $this->redirectModerateUsersPendentes('success', 'Utilizador aprovado com sucesso');
    }

    public function requestTrustedBadge() {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'profile');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'profile?error=Token inválido');
            exit;
        }

        $userId = (int) ($user['id'] ?? 0);
        $trustGate = ClassTrustBadgeEligibility::assertCanRequest($userId);
        if (($trustGate['allowed'] ?? false) !== true) {
            $blockers = $trustGate['blockers'] ?? [];
            $errorMsg = !empty($blockers)
                ? implode('. ', $blockers)
                : 'Não é possível solicitar o selo neste momento';
            header('Location: ' . DIRPAGE . 'profile?error=' . urlencode($errorMsg) . '#trust-badge-section');
            exit;
        }

        $months = (int) ($_POST['trust_badge_months'] ?? 0);
        $feeRequired = User::calculateTrustedBadgeFeeByMonths($months);
        if ($feeRequired <= 0) {
            header('Location: ' . DIRPAGE . 'profile#trust-badge-section?error=Duração do selo inválida');
            exit;
        }

        // Handle payment proof upload (required)
        $proofPath = '';
        $proofFile = $_FILES['payment_proof'] ?? null;
        if (empty($proofFile['tmp_name']) || ($proofFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            header('Location: ' . DIRPAGE . 'profile#trust-badge-section?error=Comprovativo de pagamento obrigatório');
            exit;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $proofMime = (string) $finfo->file((string) $proofFile['tmp_name']);
        $allowedProofMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($proofMime, $allowedProofMimes, true)) {
            header('Location: ' . DIRPAGE . 'profile#trust-badge-section?error=Formato inválido (use JPG, PNG ou WebP)');
            exit;
        }
        if ((int) ($proofFile['size'] ?? 0) > 512 * 1024) {
            header('Location: ' . DIRPAGE . 'profile#trust-badge-section?error=Comprovativo demasiado grande (máx. 512 KB)');
            exit;
        }

        $proofUploadDirRelative = 'public/storage/uploads/trust_badge_proofs/';
        $proofUploadDir = DIRREQ . $proofUploadDirRelative;
        if (!is_dir($proofUploadDir)) {
            mkdir($proofUploadDir, 0755, true);
        }
        $proofExtMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $proofExt = $proofExtMap[$proofMime] ?? 'jpg';
        $proofFilename = 'proof_' . $user['id'] . '_' . time() . '.' . $proofExt;
        $proofAbsolutePath = $proofUploadDir . $proofFilename;
        if (!move_uploaded_file((string) $proofFile['tmp_name'], $proofAbsolutePath)) {
            header('Location: ' . DIRPAGE . 'profile#trust-badge-section?error=Erro ao guardar comprovativo');
            exit;
        }
        $proofPath = DIRPAGE . $proofUploadDirRelative . $proofFilename;

        if (!User::requestTrustedBadge($userId, $months, $feeRequired, $proofPath)) {
            @unlink($proofAbsolutePath);
            $blockers = ClassTrustBadgeEligibility::assertCanRequest($userId)['blockers'] ?? [];
            $errorMsg = !empty($blockers)
                ? implode('. ', $blockers)
                : 'Não foi possível registar o pedido. Verifique os requisitos do selo.';
            header('Location: ' . DIRPAGE . 'profile?error=' . urlencode($errorMsg) . '#trust-badge-section');
            exit;
        }

        Notification::notifyUsers(
            User::getActiveAdminIds(),
            'trusted_badge_requested',
            'Nova solicitação de selo',
            'Um utilizador solicitou análise para o selo de confiança (' . $months . ' mes(es), ' . number_format($feeRequired, 0, ',', '.') . ' Kz).',
            ['user_id' => (int) $user['id'], 'months' => $months, 'fee' => $feeRequired],
            (int) $user['id']
        );

        header('Location: ' . DIRPAGE . 'profile?success=Solicitação do selo de confiança enviada para análise (' . $months . ' mes(es))');
        exit;
    }

    public function approveTrustedBadge($id) {
        ClassAuth::requireAuth();
        $admin = ClassAuth::user();
        if (!ClassAccess::can('payments.manage', $admin) && !ClassAccess::can('users.review', $admin)) {
            ClassAccess::requireSuperAdmin('dashboard', 'Sem permissão para aprovar selo de confiança');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?error=Token inválido');
            exit;
        }

        $targetUser = User::findById((int) $id);
        if (!$targetUser || empty($targetUser['trust_badge_fee_paid'])) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?error=Confirme o pagamento antes de aprovar o selo');
            exit;
        }

        $fee = (float) ($targetUser['trust_badge_fee_required'] ?? 0);

        User::approveTrustedBadge((int) $id);
        \App\model\Log::create([
            'user_id' => $admin['id'],
            'action' => 'approve_trusted_badge',
            'entity_type' => 'user',
            'entity_id' => $id,
            'details' => 'Selo de confiança aprovado (pagamento já confirmado, taxa: ' . $fee . ')'
        ]);

        Notification::notifyUser(
            (int) $id,
            'trusted_badge_approved',
            'Selo de confiança aprovado',
            'Seu selo de confiança foi aprovado. Taxa paga: ' . number_format($fee, 0, ',', '.') . ' Kz.',
            ['user_id' => (int) $id, 'fee' => $fee],
            (int) $admin['id']
        );

        header('Location: ' . DIRPAGE . 'dashboard/payments?success=Selo de confiança aprovado com sucesso');
        exit;
    }

    public function rejectTrustedBadge($id) {
        ClassAuth::requireAuth();
        $admin = ClassAuth::user();
        if (!ClassAccess::can('payments.manage', $admin) && !ClassAccess::can('users.review', $admin)) {
            ClassAccess::requireSuperAdmin('dashboard', 'Sem permissão para rejeitar selo de confiança');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?error=Token inválido');
            exit;
        }

        User::rejectTrustedBadge($id);
        \App\model\Log::create([
            'user_id' => $admin['id'],
            'action' => 'reject_trusted_badge',
            'entity_type' => 'user',
            'entity_id' => $id,
            'details' => 'Selo de confiança rejeitado'
        ]);

        Notification::notifyUser(
            (int) $id,
            'trusted_badge_rejected',
            'Selo de confiança rejeitado',
            'Sua solicitação de selo de confiança foi rejeitada. Você pode solicitar novamente depois.',
            ['user_id' => (int) $id],
            (int) $admin['id']
        );

        header('Location: ' . DIRPAGE . 'dashboard/payments?success=Solicitação de selo rejeitada');
        exit;
    }

    public function confirmTrustedBadgePayment($id) {
        $admin = ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponível apenas para a equipa financeira');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/payments?error=Token inválido');
            exit;
        }

        if (isset($_POST['fee'])) {
            $fee = max(0.0, (float) $_POST['fee']);
            User::setTrustBadgeFeeRequired((int) $id, $fee);
        }

        User::markTrustedBadgeFeePaid($id);
        \App\model\Log::create([
            'user_id' => $admin['id'],
            'action' => 'confirm_trusted_badge_payment',
            'entity_type' => 'user',
            'entity_id' => $id,
            'details' => 'Pagamento do selo de confiança confirmado'
        ]);

        Notification::notifyUser(
            (int) $id,
            'trusted_badge_payment_confirmed',
            'Pagamento confirmado',
            'O pagamento da taxa do selo de confiança foi confirmado.',
            ['user_id' => (int) $id],
            (int) $admin['id']
        );

        header('Location: ' . DIRPAGE . 'dashboard/payments?success=Pagamento do selo confirmado');
        exit;
    }

    public function rejectUser($id) {
        $user = ClassAccess::requirePermission('users.review', 'dashboard', 'Acesso disponível apenas para moderação');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            $this->redirectModerateUsersPendentes('error', 'Token inválido');
        }

        $targetId = (int) $id;
        if ($targetId <= 0) {
            $this->redirectModerateUsersPendentes('error', 'Utilizador inválido');
        }

        $targetUser = User::findById($targetId);
        if (!$targetUser || (string) ($targetUser['status'] ?? '') !== 'pendente') {
            $this->redirectModerateUsersPendentes('error', 'Utilizador não encontrado ou já não está pendente');
        }

        if (!\App\model\User::rejectUser($targetId)) {
            $this->redirectModerateUsersPendentes('error', 'Não foi possível rejeitar o utilizador');
        }

        \App\model\Log::create([
            'user_id' => $user['id'],
            'action' => 'reject_user',
            'entity_type' => 'user',
            'entity_id' => $targetId,
            'details' => 'Usuário rejeitado'
        ]);

        Notification::notifyUser(
            $targetId,
            'user_rejected',
            'Conta rejeitada',
            'Sua conta foi rejeitada após análise documental.',
            ['user_id' => $targetId],
            (int) $user['id']
        );

        $this->redirectModerateUsersPendentes('success', 'Utilizador rejeitado');
    }

    public function myProperties() {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores não têm portfólio de imóveis');
        $properties = Property::getByAffiliate($user['id']);

        $propertyIds = array_map(
            static fn(array $property): int => (int) ($property['id'] ?? 0),
            $properties
        );
        $affiliateRequests = PropertyAffiliate::getByProperties($propertyIds, 'pendente');

        foreach ($propertyIds as $propertyId) {
            if (!isset($affiliateRequests[$propertyId])) {
                $affiliateRequests[$propertyId] = [];
            }
        }

        $pendingBoostIds = [];
        foreach (PropertyBoostRequest::getPending() as $boostRequest) {
            $pid = (int) ($boostRequest['property_id'] ?? 0);
            if ($pid > 0) {
                $pendingBoostIds[$pid] = true;
            }
        }

        $render = new ClassRender();
        $render->setTitle("Minhas Propriedades");
        $render->setDescription("Gerencie suas propriedades");
        $render->setKeywords("propriedades, gerencie");
        $render->setData([
            'user' => $user,
            'properties' => $properties,
            'affiliateRequests' => $affiliateRequests,
            'pendingBoostIds' => $pendingBoostIds,
            'boostPricing' => PropertyBoostRequest::getBoostPricingConfig(),
        ]);
        $render->setDir("dashboard/my_properties");
        $render->renderLayout();
    }

    public function referrals() {
        header('Location: ' . DIRPAGE . 'dashboard/afiliados?tab=referrals');
        exit;
    }

    public function myAffiliates() {
        header('Location: ' . DIRPAGE . 'dashboard/afiliados?tab=my_affiliates');
        exit;
    }

    public function accountStatus() {
        $sessionUser = ClassAccess::requireAuthenticatedAccount();
        $user = User::findById((int) ($sessionUser['id'] ?? 0)) ?: $sessionUser;

        if (ClassAccess::hasFullPlatformAccess($user)) {
            header('Location: ' . DIRPAGE . 'profile');
            exit;
        }

        if (!ClassAccess::canUseAccountStatusPage($user)) {
            header('Location: ' . DIRPAGE . 'login?error=' . rawurlencode('Não foi possível abrir a página da conta.'));
            exit;
        }

        $userId = (int) ($user['id'] ?? 0);
        $compliance = Document::getComplianceStatus($userId);
        $latestDocument = Document::getLatestByUser($userId);
        $rejectedDocuments = Document::getRejectedByUser($userId);
        $accountState = \Src\classes\UserAccountState::resolveWithDocument(
            $user,
            $compliance,
            count($rejectedDocuments)
        );

        $render = new ClassRender();
        $render->setTitle('A sua conta');
        $render->setDescription('Veja como está o seu registo e o que pode fazer na Imobil Fácil');
        $render->setKeywords('conta, documentos, registo');
        $render->setData([
            'user' => $user,
            'accountState' => $accountState,
            'canEditIdentificationFields' => \Src\classes\UserAccountState::canEditIdentificationOnAccountPage($user),
            'canManageDocuments' => $accountState['can_submit_documents_on_account_page'],
            'latestDocument' => $latestDocument,
            'rejectedDocuments' => $rejectedDocuments,
            'csrfField' => ClassCsrf::field(),
        ]);
        $render->setDir('dashboard/account_status');
        $render->renderLayout();
    }

    public function profile() {
        ClassAuth::requireAuth();

        $user = ClassAuth::user();
        if (ClassAccess::canUseAccountStatusPage($user)) {
            header('Location: ' . DIRPAGE . 'dashboard/accountStatus');
            exit;
        }
        $userId = (int) ($user['id'] ?? 0);
        $trustGate = ClassTrustBadgeEligibility::assertCanRequest($userId);
        $trust = User::getTrustMetrics($userId);
        $trustCanSubmit = ($trustGate['allowed'] ?? false) === true;
        $trustPricing = User::getTrustedBadgePricingConfig();
        $officialPlan = empty($user['is_admin'])
            ? ClassPlan::getOfficialPlanByUser($userId)
            : null;

        $render = new ClassRender();
        $render->setTitle("Meu Perfil");
        $render->setDescription("Gerencie os dados da sua conta");
        $render->setKeywords("perfil, conta, usuário");
        $render->setData([
            'user' => $user,
            'trust' => $trust,
            'trustGate' => $trustGate,
            'trustCanSubmit' => $trustCanSubmit,
            'trustPricing' => $trustPricing,
            'officialPlan' => $officialPlan,
            'usernameCanChange' => \Src\classes\UsernameHelper::canChangeUsername($user),
            'usernameNextChangeAt' => \Src\classes\UsernameHelper::nextChangeEligibleAt($user),
            'pendingEmailChange' => \Src\classes\EmailVerificationService::getPendingEmailChange($userId),
        ]);
        $render->setDir("dashboard/profile");
        $render->renderLayout();
    }

    public function getPromoterTerms() {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();
        $userType = (string) ($user['user_type'] ?? 'pessoa_fisica');
        if (!in_array($userType, ['pessoa_fisica', 'pessoa_juridica'], true)) {
            $userType = 'pessoa_fisica';
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(\App\model\User::getPromoterProgramTerms($userType), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function becomeAffiliate() {
        ClassAuth::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'profile?error=Token inválido');
            exit;
        }

        $user = ClassAuth::user();

        if (!empty($user['is_admin'])) {
            header('Location: ' . DIRPAGE . 'profile?error=Administradores não podem activar o perfil de promotor');
            exit;
        }

        if (!empty($user['is_affiliate'])) {
            header('Location: ' . DIRPAGE . 'profile?error=Já é promotor de imóveis');
            exit;
        }

        $acceptedTerms = filter_var($_POST['accept_promoter_terms'] ?? '', FILTER_VALIDATE_BOOLEAN)
            || (string) ($_POST['accept_promoter_terms'] ?? '') === '1';
        if (!$acceptedTerms) {
            header('Location: ' . DIRPAGE . 'profile?error=' . rawurlencode('Deve aceitar os Termos e Condições da plataforma para activar o perfil de promotor'));
            exit;
        }

        $enabled = \App\model\User::enableAffiliate((int) $user['id']);

        if ($enabled) {
            \App\model\Log::create([
                'user_id'     => $user['id'],
                'action'      => 'become_affiliate',
                'entity_type' => 'user',
                'entity_id'   => $user['id'],
                'details'     => json_encode([
                    'message' => 'Perfil de promotor activado com aceitação dos termos',
                    'user_type' => (string) ($user['user_type'] ?? 'pessoa_fisica'),
                    'terms_version' => '2026-05-24',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            header('Location: ' . DIRPAGE . 'profile?success=Perfil de promotor de imóveis activado com sucesso');
            exit;
        }

        header('Location: ' . DIRPAGE . 'profile?error=Não foi possível activar o perfil de promotor');
        exit;
    }

    public function update() {
        ClassAccess::requireAuthenticatedAccount();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'dashboard/accountStatus');
            exit;
        }

        if (!ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            $redirect = ClassAccess::canUseAccountStatusPage()
                ? 'dashboard/accountStatus'
                : 'profile';
            header('Location: ' . DIRPAGE . $redirect . '?error=Token inválido');
            exit;
        }

        $sessionUser = ClassAuth::user();
        $currentUser = User::findById((int) ($sessionUser['id'] ?? 0)) ?: $sessionUser;
        $accountStatusFlow = ClassAccess::canUseAccountStatusPage($currentUser);
        $redirectBase = $accountStatusFlow ? 'dashboard/accountStatus' : 'profile';

        if ($accountStatusFlow) {
            $canEditIdentification = \Src\classes\UserAccountState::canEditIdentificationOnAccountPage($currentUser);
            $canSubmitDocuments = ClassAccess::canSubmitDocumentsOnAccountStatusPage($currentUser);

            if (!$canEditIdentification && !$canSubmitDocuments) {
                header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Por agora estes dados estão só para consulta. Se precisarmos que corrija algo, avisamos nesta página.'));
                exit;
            }

            $userId = (int) $currentUser['id'];
            $docCompliance = Document::getComplianceStatus($userId);
            $latestDocument = Document::getLatestByUser($userId);
            $documentPendingReview = $docCompliance === 'pending' && $latestDocument !== null;
            $profileUpdated = false;

            if ($canEditIdentification) {
                if (!\Src\classes\UserAccountState::canEditIdentificationOnAccountPage($currentUser)) {
                    header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Só pode alterar nome e número de BI quando a conta estiver rejeitada para correcção.'));
                    exit;
                }

                $name = trim($_POST['name'] ?? '');
                $documentNumber = trim($_POST['document_number'] ?? '');

                if ($name === '') {
                    header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Indique o seu nome completo.'));
                    exit;
                }
                if ($documentNumber === '') {
                    header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Indique o número do BI ou documento de identificação.'));
                    exit;
                }
                if (User::findByDocumentNumberExceptId($documentNumber, $userId)) {
                    header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Este número de BI já está associado a outra conta.'));
                    exit;
                }

                User::updateProfile($userId, [
                    'name' => $name,
                    'document_number' => $documentNumber,
                ]);
                $profileUpdated = true;
            }

            $uploadResult = ['uploaded' => false, 'error' => null];
            $mustUploadDocument = $canSubmitDocuments && !$documentPendingReview;

            if ($mustUploadDocument) {
                $documentFile = $_FILES['document_file'] ?? null;
                $noFile = !$documentFile
                    || (int) ($documentFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE;
                if ($noFile) {
                    header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Envie o ficheiro do BI ou do documento da empresa.'));
                    exit;
                }

                $uploadResult = $this->processIdentificationDocumentUpload($currentUser);
                if ($uploadResult['error'] !== null) {
                    header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode($uploadResult['error']));
                    exit;
                }
                if (empty($uploadResult['uploaded'])) {
                    header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Não conseguimos receber o ficheiro. Tente outra vez ou escolha outro formato.'));
                    exit;
                }
            }

            if (!$profileUpdated && empty($uploadResult['uploaded'])) {
                header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Não há alterações para guardar neste momento.'));
                exit;
            }

            $successParts = [];
            if ($profileUpdated) {
                $successParts[] = 'Guardámos as suas alterações.';
            }
            if (!empty($uploadResult['uploaded'])) {
                $successParts[] = 'Recebemos o documento — vamos analisá-lo em breve.';
            }

            header('Location: ' . DIRPAGE . $redirectBase . '?success=' . rawurlencode(implode(' ', $successParts)));
            exit;
        }

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        if (!ClassAuth::verifyCurrentPassword($currentUser, $currentPassword)) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Indique a senha actual correcta para guardar alterações.'));
            exit;
        }

        $submittedName = trim((string) ($_POST['name'] ?? ''));
        if ($submittedName !== '' && $submittedName !== trim((string) ($currentUser['name'] ?? ''))) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode(
                'Nome e documento de identificação só podem ser alterados na página Estado da conta, quando a plataforma o solicitar.'
            ));
            exit;
        }

        $phone = \Src\classes\PhoneHelper::normalize(trim((string) ($_POST['phone'] ?? '')));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $profilePhoto = $_FILES['profile_photo'] ?? null;

        $currentUsername = \Src\classes\UsernameHelper::normalize((string) ($currentUser['username'] ?? ''));
        $submittedUsername = array_key_exists('username', $_POST)
            ? \Src\classes\UsernameHelper::normalize(trim((string) $_POST['username']))
            : $currentUsername;
        $usernameWillChange = $submittedUsername !== $currentUsername;

        if ($usernameWillChange) {
            if (!\Src\classes\UsernameHelper::canChangeUsername($currentUser)) {
                $nextChange = \Src\classes\UsernameHelper::nextChangeEligibleAt($currentUser);
                $detail = $nextChange !== null
                    ? ' Pode voltar a alterar a partir de ' . $nextChange->format('d/m/Y') . '.'
                    : '';
                header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode(
                    \Src\classes\UsernameHelper::profileErrorMessage('username_cooldown') . $detail
                ));
                exit;
            }

            $usernameValidation = \Src\classes\UsernameHelper::validate($submittedUsername);
            if ($usernameValidation !== null) {
                header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode(
                    \Src\classes\UsernameHelper::profileErrorMessage($usernameValidation)
                ));
                exit;
            }

            if (\Src\classes\UsernameHelper::isTaken($submittedUsername, (int) $currentUser['id'])) {
                header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode(
                    \Src\classes\UsernameHelper::profileErrorMessage('username_taken')
                ));
                exit;
            }
        }

        $storedPhone = \Src\classes\PhoneHelper::normalize(trim((string) ($currentUser['phone'] ?? '')));
        $storedEmail = \Src\classes\EmailVerificationService::normalizeEmail((string) ($currentUser['email'] ?? ''));
        $phoneWillChange = $phone !== $storedPhone;
        $emailWillChange = $email !== $storedEmail;
        $passwordWillChange = $newPassword !== '';
        $photoWillChange = $profilePhoto && (int) ($profilePhoto['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
        $emailChangeRequested = false;

        if (!$usernameWillChange && !$phoneWillChange && !$emailWillChange && !$passwordWillChange && !$photoWillChange) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Não há alterações para guardar.'));
            exit;
        }

        if ($phone === '') {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=Telefone é obrigatório');
            exit;
        }
        if ($email === '') {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Email é obrigatório.'));
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=Email inválido');
            exit;
        }
        if (User::findByPhoneExceptId($phone, $currentUser['id'])) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=Telefone já está em uso');
            exit;
        }
        if ($emailWillChange) {
            $emailChangeResult = \Src\classes\EmailVerificationService::requestEmailChange(
                (int) $currentUser['id'],
                $email,
                (string) ($currentUser['name'] ?? '')
            );

            if ($emailChangeResult['status'] === 'taken') {
                header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Este email já está registado noutra conta.'));
                exit;
            }
            if ($emailChangeResult['status'] === 'invalid' || $emailChangeResult['status'] === 'failed') {
                header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Não foi possível enviar a confirmação para o novo email.'));
                exit;
            }

            $emailChangeRequested = ($emailChangeResult['status'] === 'sent');
        }
        if ($newPassword !== '' && strlen($newPassword) < 6) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=Nova senha deve ter pelo menos 6 caracteres');
            exit;
        }
        if ($newPassword !== '' && $newPassword !== $confirmPassword) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=Confirmação de senha não coincide');
            exit;
        }

        // Handle profile photo upload
        $photoPath = null;
        if ($profilePhoto && $profilePhoto['error'] === UPLOAD_ERR_OK) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            $tmpName = (string) ($profilePhoto['tmp_name'] ?? '');

            if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                header('Location: ' . DIRPAGE . $redirectBase . '?error=Arquivo de imagem inválido');
                exit;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = $finfo ? (string) finfo_file($finfo, $tmpName) : '';
            if ($finfo) {
                finfo_close($finfo);
            }

            if (!in_array($detectedMime, $allowedMimes, true)) {
                header('Location: ' . DIRPAGE . $redirectBase . '?error=Formato de imagem inválido. Aceitos: JPG, PNG, GIF, WebP');
                exit;
            }
            if ($profilePhoto['size'] > $maxSize) {
                header('Location: ' . DIRPAGE . $redirectBase . '?error=Arquivo muito grande. Máximo 2MB');
                exit;
            }

            $extMap = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ];
            $ext = $extMap[$detectedMime] ?? 'jpg';

            $uploadDirRelative = 'storage/uploads/profiles/';
            $uploadDirAbs = rtrim((string) DIRREQ, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uploadDirRelative);
            if (!is_dir($uploadDirAbs) && !mkdir($uploadDirAbs, 0755, true) && !is_dir($uploadDirAbs)) {
                header('Location: ' . DIRPAGE . $redirectBase . '?error=Não foi possível preparar a pasta da foto');
                exit;
            }

            try {
                $suffix = bin2hex(random_bytes(6));
            } catch (\Throwable $e) {
                $suffix = substr(md5(uniqid('', true)), 0, 12);
            }
            $filename = 'profile_' . (int) $currentUser['id'] . '_' . time() . '_' . $suffix . '.' . $ext;
            $photoPath = $uploadDirRelative . $filename;

            if (!move_uploaded_file($tmpName, $uploadDirAbs . $filename)) {
                header('Location: ' . DIRPAGE . $redirectBase . '?error=Erro ao fazer upload da foto');
                exit;
            }
        }

        if ($usernameWillChange && !User::updateUsername((int) $currentUser['id'], $submittedUsername)) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode(
                \Src\classes\UsernameHelper::profileErrorMessage('username_locked')
            ));
            exit;
        }

        $updateData = [];
        if ($phoneWillChange) {
            $updateData['phone'] = $phone;
        }
        if ($emailWillChange) {
            $updateData['email'] = $email;
        }
        if ($passwordWillChange) {
            $updateData['password'] = $newPassword;
        }
        if ($photoPath !== null) {
            $updateData['profile_photo'] = DIRPAGE . $photoPath;
        }

        if (!empty($updateData)) {
            $updated = User::updateProfile($currentUser['id'], $updateData);
            if (!$updated) {
                header('Location: ' . DIRPAGE . $redirectBase . '?error=Não foi possível atualizar o perfil');
                exit;
            }
        }

        $successParts = [];
        if ($emailChangeRequested) {
            $successParts[] = 'Enviámos um link de confirmação para o novo email. O email actual mantém-se até validar o link.';
        }
        if ($phoneWillChange || $passwordWillChange || $photoWillChange || $usernameWillChange) {
            $successParts[] = 'Os restantes dados foram guardados.';
        }
        if (empty($successParts)) {
            $successParts[] = 'Pedido registado.';
        }

        header('Location: ' . DIRPAGE . $redirectBase . '?success=' . rawurlencode(implode(' ', $successParts)));
        exit;
    }

    public function auditLog($entityType = null, $entityId = null) {
        $admin = ClassAccess::requirePermission('audit.view', 'dashboard', 'Acesso disponível apenas para perfis autorizados');

        $entityType = !empty($entityType) ? preg_replace('/[^a-z_]/', '', strtolower((string) $entityType)) : null;
        $entityId   = !empty($entityId)   ? (int) $entityId : null;

        $perPage = 40;
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        if ($entityType && $entityId) {
            $logs  = Log::getByEntity($entityType, $entityId);
            $total = count($logs);
        } else {
            $logs  = Log::getRecent($perPage, $offset);
            $total = Log::countAll();
        }

        $totalPages = (int) ceil($total / $perPage);

        $render = new ClassRender();
        $render->setTitle('Registo de Auditoria');
        $render->setDescription('Histórico de ações do sistema');
        $render->setKeywords('auditoria, logs, histórico');
        $render->setData([
            'user'       => $admin,
            'logs'       => $logs,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => $totalPages,
            'perPage'    => $perPage,
            'filterType' => $entityType,
            'filterId'   => $entityId,
        ]);
        $render->setDir('dashboard/audit_log');
        $render->renderLayout();
    }

    public function kpi() {
        $user = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        $userStats = User::getRegistrationStats();
        $propertyStats = Property::getStatusStats();
        $requestStats = Request::getStatusStats();
        $commissionStats = Commission::getSummaryStats();
        $topAffiliates = Commission::getTopAffiliates(5);

        $render = new ClassRender();
        $render->setTitle("KPIs do Sistema");
        $render->setDescription("Indicadores de desempenho da plataforma");
        $render->setKeywords("kpi, estatísticas, desempenho");
        $render->setData([
            'user' => $user,
            'userStats' => $userStats,
            'propertyStats' => $propertyStats,
            'requestStats' => $requestStats,
            'commissionStats' => $commissionStats,
            'topAffiliates' => $topAffiliates,
        ]);
        $render->setDir("dashboard/kpi");
        $render->renderLayout();
    }

    public function reviewDocuments() {
        $admin = ClassAccess::requirePermission('documents.review', 'dashboard', 'Acesso disponível apenas para moderação documental');

        $perPage = 20;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        
        $pendingDocs = Document::getPending($perPage, $offset);
        $totalPending = Document::countPending();
        $totalPages = (int) ceil($totalPending / $perPage);
        $stats = Document::getComplianceStats();

        $render = new ClassRender();
        $render->setTitle('Revisão de Documentos');
        $render->setDescription('Revise documentos enviados por utilizadores');
        $render->setKeywords('documentos, conformidade, revisão');
        $render->setData([
            'user' => $admin,
            'pendingDocuments' => $pendingDocs,
            'totalPending' => $totalPending,
            'page' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
            'stats' => $stats,
            'csrfField' => ClassCsrf::field()
        ]);
        $render->setDir('dashboard/review_documents');
        $render->renderLayout();

        // Audit: record that an admin viewed documents pending review
        \App\model\Log::sensitiveRead((int) ($admin['id'] ?? null), 'document_list', null, 'Viewed pending documents list');
    }

    public function approveDocument($id) {
        $admin = ClassAccess::requirePermission('documents.review', 'dashboard', 'Acesso disponível apenas para moderação documental');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/reviewDocuments?error=Token inválido');
            exit;
        }

        $document = Document::findById((int) $id);
        if (!$document) {
            header('Location: ' . DIRPAGE . 'dashboard/reviewDocuments?error=Documento não encontrado');
            exit;
        }

        if (!Document::approve((int) $id, (int) $admin['id'])) {
            header('Location: ' . DIRPAGE . 'dashboard/reviewDocuments?error=Erro ao aprovar documento');
            exit;
        }

        Log::create([
            'user_id' => (int) $admin['id'],
            'action' => 'approve_document',
            'entity_type' => 'document',
            'entity_id' => (int) $id,
            'details' => 'Documento aprovado: ' . $document['type'] . ' (' . $document['version'] . ')'
        ]);

        if ($document['user_id']) {
            Notification::notifyUser(
                (int) $document['user_id'],
                'document_approved',
                'Documento aprovado',
                'Seu documento (' . $document['type'] . ') foi aprovado após análise.',
                ['document_id' => (int) $id, 'doc_type' => $document['type']],
                (int) $admin['id']
            );
        }

        header('Location: ' . DIRPAGE . 'dashboard/reviewDocuments?success=Documento aprovado');
        exit;
    }

    public function rejectDocument($id) {
        $admin = ClassAccess::requirePermission('documents.review', 'dashboard', 'Acesso disponível apenas para moderação documental');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/reviewDocuments?error=Token inválido');
            exit;
        }

        $document = Document::findById((int) $id);
        if (!$document) {
            header('Location: ' . DIRPAGE . 'dashboard/reviewDocuments?error=Documento não encontrado');
            exit;
        }

        $rejectionReason = trim($_POST['rejection_reason'] ?? '');
        if (empty($rejectionReason)) {
            header('Location: ' . DIRPAGE . 'dashboard/reviewDocuments?error=Motivo da rejeição é obrigatório');
            exit;
        }

        try {
            Document::reject((int) $id, $rejectionReason, (int) $admin['id']);
        } catch (\Exception $e) {
            header('Location: ' . DIRPAGE . 'dashboard/reviewDocuments?error=' . urlencode($e->getMessage()));
            exit;
        }

        Log::create([
            'user_id' => (int) $admin['id'],
            'action' => 'reject_document',
            'entity_type' => 'document',
            'entity_id' => (int) $id,
            'details' => 'Documento rejeitado: ' . $document['type'] . ' (' . $document['version'] . '). Motivo: ' . $rejectionReason
        ]);

        if ($document['user_id']) {
            Notification::notifyUser(
                (int) $document['user_id'],
                'document_rejected',
                'Documento rejeitado',
                'Seu documento foi rejeitado. Motivo: ' . $rejectionReason . '. Por favor, resubmeta um novo documento.',
                ['document_id' => (int) $id, 'doc_type' => $document['type'], 'reason' => $rejectionReason],
                (int) $admin['id']
            );
        }

        header('Location: ' . DIRPAGE . 'dashboard/reviewDocuments?success=Documento rejeitado');
        exit;
    }

    public function resubmitDocument($documentId) {
        $user = ClassAccess::requireAuthenticatedAccount();
        $redirectBase = 'dashboard/accountStatus';

        if (!ClassAccess::canSubmitDocumentsOnAccountStatusPage($user)) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Só pode reenviar documentos quando a identificação precisar de correcção.'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=Token inválido');
            exit;
        }

        if (!isset($_FILES['document_file'])) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=Nenhum ficheiro foi enviado');
            exit;
        }

        $rejectedDoc = Document::findById((int) $documentId);
        if (!$rejectedDoc || $rejectedDoc['user_id'] !== (int) $user['id'] || $rejectedDoc['status'] !== 'rejeitado') {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=Documento não encontrado ou não é passível de resubmissão');
            exit;
        }

        // Validate new file
        $validation = ClassDocumentValidator::validateFile(
            $_FILES['document_file'],
            $rejectedDoc['type']
        );

        if (!$validation['valid']) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=' . urlencode($validation['error']));
            exit;
        }

        // Move file
        $uploadDir = DIRREQ . 'storage/documents/';
        $tmpPath = (string) ($_FILES['document_file']['tmp_name'] ?? '');
        $originalName = (string) ($_FILES['document_file']['name'] ?? '');
        
        $nextVersion = ClassDocumentValidator::getNextVersion($rejectedDoc['version']);
        $filename = ClassDocumentValidator::generateFilename($originalName, $nextVersion);

        if (!move_uploaded_file($tmpPath, $uploadDir . $filename)) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=Falha ao guardar o documento');
            exit;
        }

        // Create new document record with next version
        Document::create(
            (int) $user['id'],
            null,
            $rejectedDoc['type'],
            $filename,
            $nextVersion
        );

        Log::create([
            'user_id' => (int) $user['id'],
            'action' => 'resubmit_document',
            'entity_type' => 'document',
            'entity_id' => (int) $documentId,
            'details' => 'Documento resubmetido na versão ' . $nextVersion . '. Anterior: ' . $rejectedDoc['version']
        ]);

        Notification::notifyUsers(
            User::getActiveAdminIds(),
            'document_resubmitted',
            'Documento resubmetido',
            'Um utilizador resubmeteu um documento rejeitado (' . $rejectedDoc['type'] . ' v' . $nextVersion . ').',
            ['user_id' => (int) $user['id'], 'doc_type' => $rejectedDoc['type']],
            (int) $user['id']
        );

        header('Location: ' . DIRPAGE . $redirectBase . '?success=Recebemos o novo documento — vamos analisar em breve');
        exit;
    }

    public function submitAccountDocument() {
        $user = ClassAccess::requireAuthenticatedAccount();
        $redirectBase = 'dashboard/accountStatus';

        if (!ClassAccess::canSubmitDocumentsOnAccountStatusPage($user)) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=' . rawurlencode('Só pode enviar documentos quando a identificação precisar de correcção.'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=Token inválido');
            exit;
        }

        if (!isset($_FILES['document_file']) || (int) ($_FILES['document_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=Selecione um documento para enviar');
            exit;
        }

        $compliance = Document::getComplianceStatus((int) $user['id']);
        if ($compliance === 'compliant') {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=O seu documento já foi aceite');
            exit;
        }

        $latest = Document::getLatestByUser((int) $user['id']);
        if ($latest && (string) ($latest['status'] ?? '') === 'pendente') {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=Já estamos a analisar o seu último envio — aguarde um pouco');
            exit;
        }

        $docType = ClassDocumentValidator::TYPE_USER_REGISTRATION;
        $validation = ClassDocumentValidator::validateFile($_FILES['document_file'], $docType);
        if (!$validation['valid']) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=' . urlencode($validation['error']));
            exit;
        }

        $uploadDir = DIRREQ . 'storage/documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $tmpPath = (string) ($_FILES['document_file']['tmp_name'] ?? '');
        $originalName = (string) ($_FILES['document_file']['name'] ?? '');
        $version = $latest ? ClassDocumentValidator::getNextVersion((string) ($latest['version'] ?? 'v1')) : 'v1';
        $filename = ClassDocumentValidator::generateFilename($originalName, $version);

        if (!move_uploaded_file($tmpPath, $uploadDir . $filename)) {
            header('Location: ' . DIRPAGE . $redirectBase . '?error=Falha ao guardar o documento');
            exit;
        }

        Document::create((int) $user['id'], null, $docType, $filename, $version);

        Log::create([
            'user_id' => (int) $user['id'],
            'action' => 'submit_account_document',
            'entity_type' => 'document',
            'entity_id' => null,
            'details' => 'Documento enviado na área de estado da conta (' . $version . ')',
        ]);

        Notification::notifyUsers(
            User::getActiveAdminIds(),
            'document_resubmitted',
            'Novo documento para análise',
            'Um utilizador enviou documento para validação (' . $version . ').',
            ['user_id' => (int) $user['id']],
            (int) $user['id']
        );

        header('Location: ' . DIRPAGE . $redirectBase . '?success=Documento recebido — avisamos quando houver novidades');
        exit;
    }

    private function buildAdminQueue(array $pendingUsers, array $pendingTrust, array $pendingProperties, array $openRequests, array $pendingDocuments = []): array {
        $items = [];

        foreach ($pendingUsers as $entry) {
            $items[] = $this->queueItemFromDate(
                'user_verification',
                (int) ($entry['id'] ?? 0),
                (string) ($entry['created_at'] ?? ''),
                'Verificação de perfil pendente',
                (string) ($entry['name'] ?? 'Utilizador')
            );
        }

        foreach ($pendingTrust as $entry) {
            $items[] = $this->queueItemFromDate(
                'trusted_badge',
                (int) ($entry['id'] ?? 0),
                (string) ($entry['trust_badge_requested_at'] ?? $entry['created_at'] ?? ''),
                'Solicitação de selo pendente',
                (string) ($entry['name'] ?? 'Utilizador')
            );
        }

        foreach ($pendingProperties as $entry) {
            $items[] = $this->queueItemFromDate(
                'property_moderation',
                (int) ($entry['id'] ?? 0),
                (string) ($entry['created_at'] ?? ''),
                'Imóvel aguardando moderação',
                (string) ($entry['title'] ?? 'Imóvel sem título')
            );
        }

        foreach ($openRequests as $entry) {
            $items[] = $this->queueItemFromDate(
                'request_followup',
                (int) ($entry['id'] ?? 0),
                (string) ($entry['created_at'] ?? ''),
                'Solicitação aberta sem desfecho',
                (string) ($entry['title'] ?? 'Solicitação')
            );
        }

        foreach ($pendingDocuments as $entry) {
            $items[] = $this->queueItemFromDate(
                'document_review',
                (int) ($entry['id'] ?? 0),
                (string) ($entry['created_at'] ?? ''),
                'Validação documental pendente',
                (string) ($entry['type'] ?? 'documento')
            );
        }

        usort($items, function ($a, $b) {
            $priorityOrder = ['atrasado' => 3, 'urgente' => 2, 'pendente' => 1];
            $pa = $priorityOrder[$a['priority']] ?? 0;
            $pb = $priorityOrder[$b['priority']] ?? 0;
            if ($pa !== $pb) {
                return $pb <=> $pa;
            }
            return strcmp($a['created_at'], $b['created_at']);
        });

        $summary = [
            'pendente' => 0,
            'urgente' => 0,
            'atrasado' => 0,
            'total' => count($items)
        ];

        foreach ($items as $item) {
            $summary[$item['priority']]++;
        }

        return ['items' => $items, 'summary' => $summary];
    }

    private function queueItemFromDate(string $type, int $entityId, string $createdAt, string $title, string $subject): array {
        $now = new \DateTimeImmutable('now');
        try {
            $created = new \DateTimeImmutable($createdAt);
        } catch (\Exception $e) {
            $created = $now;
        }

        $ageDays = (int) $created->diff($now)->format('%a');
        $priority = 'pendente';
        if ($ageDays >= 7) {
            $priority = 'atrasado';
        } elseif ($ageDays >= 3) {
            $priority = 'urgente';
        }

        return [
            'type' => $type,
            'entity_id' => $entityId,
            'title' => $title,
            'subject' => $subject,
            'created_at' => $created->format('Y-m-d H:i:s'),
            'age_days' => $ageDays,
            'priority' => $priority
        ];
    }

    public function paymentAccounts() {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        $accounts = \App\model\UserPaymentAccount::getByUser((int) $user['id']);
        $methods = \App\model\PaymentMethod::getActive('user');

        $render = new ClassRender();
        $render->setTitle('Meus Dados de Pagamento');
        $render->setDescription('Adicione e gerencie suas contas para receber pagamentos');
        $render->setKeywords('pagamentos, contas, recebimentos');
        $render->setData([
            'user' => $user,
            'accounts' => $accounts,
            'methods' => $methods,
            'csrfField' => ClassCsrf::field()
        ]);
        $render->setDir('dashboard/payment_accounts');
        $render->renderLayout();
    }

    public function addPaymentAccount() {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/paymentAccounts?error=Token+inválido');
            exit;
        }

        $methodId = (int) ($_POST['method_id'] ?? 0);
        $method = \App\model\PaymentMethod::findById($methodId);

        if (
            !$method
            || (int) $method['is_active'] !== 1
            || !in_array($method['audience'], ['user', 'both'], true)
        ) {
            header('Location: ' . DIRPAGE . 'dashboard/paymentAccounts?error=Método+de+pagamento+inválido');
            exit;
        }

        $fieldsConfig = \App\model\PaymentMethod::parseFieldsConfig($method['fields_config'] ?? null);
        $allowedFieldNames = ['account_name', 'account_number', 'iban', 'bank_name', 'wallet_provider', 'phone_number'];

        $filteredFields = [];
        foreach ($allowedFieldNames as $fieldName) {
            $isAllowed = !empty($fieldsConfig[$fieldName]);
            $filteredFields[$fieldName] = $isAllowed
                ? (($_POST[$fieldName] ?? null) !== '' ? trim((string) ($_POST[$fieldName] ?? '')) : null)
                : null;
        }

        $accountData = [
            'method_id' => $methodId,
            'account_label' => $_POST['account_label'] ?? null,
            'account_name' => $filteredFields['account_name'],
            'account_number' => $filteredFields['account_number'],
            'iban' => $filteredFields['iban'],
            'bank_name' => $filteredFields['bank_name'],
            'wallet_provider' => $filteredFields['wallet_provider'],
            'phone_number' => $filteredFields['phone_number'],
            'is_default' => !empty($_POST['is_default']) ? 1 : 0,
        ];

        $result = \App\model\UserPaymentAccount::createForUser((int) $user['id'], $accountData);

        if ($result === false) {
            header('Location: ' . DIRPAGE . 'dashboard/paymentAccounts?error=Erro+ao+criar+conta');
            exit;
        }

        Log::create([
            'user_id' => (int) $user['id'],
            'action' => 'add_payment_account',
            'entity_type' => 'user_payment_account',
            'entity_id' => is_int($result) ? $result : 0,
            'details' => 'Conta de pagamento adicionada: ' . $method['name']
        ]);

        header('Location: ' . DIRPAGE . 'dashboard/paymentAccounts?success=' . urlencode('Conta adicionada com sucesso'));
        exit;
    }

    public function setDefaultPaymentAccount($id) {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/paymentAccounts?error=Token+inválido');
            exit;
        }

        $account = \App\model\UserPaymentAccount::findByIdForUser((int) $id, (int) $user['id']);
        if (!$account) {
            header('Location: ' . DIRPAGE . 'dashboard/paymentAccounts?error=Conta+não+encontrada');
            exit;
        }

        if (!\App\model\UserPaymentAccount::setDefault((int) $id, (int) $user['id'])) {
            header('Location: ' . DIRPAGE . 'dashboard/paymentAccounts?error=Erro+ao+atualizar');
            exit;
        }

        Log::create([
            'user_id' => (int) $user['id'],
            'action' => 'set_default_payment_account',
            'entity_type' => 'user_payment_account',
            'entity_id' => (int) $id,
            'details' => 'Conta de pagamento marcada como padrão'
        ]);

        header('Location: ' . DIRPAGE . 'dashboard/paymentAccounts?success=' . urlencode('Conta definida como padrão'));
        exit;
    }

    public function deactivatePaymentAccount($id) {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/paymentAccounts?error=Token+inválido');
            exit;
        }

        $account = \App\model\UserPaymentAccount::findByIdForUser((int) $id, (int) $user['id']);
        if (!$account) {
            header('Location: ' . DIRPAGE . 'dashboard/paymentAccounts?error=Conta+não+encontrada');
            exit;
        }

        if (!\App\model\UserPaymentAccount::deactivate((int) $id, (int) $user['id'])) {
            header('Location: ' . DIRPAGE . 'dashboard/paymentAccounts?error=Erro+ao+desativar');
            exit;
        }

        Log::create([
            'user_id' => (int) $user['id'],
            'action' => 'deactivate_payment_account',
            'entity_type' => 'user_payment_account',
            'entity_id' => (int) $id,
            'details' => 'Conta de pagamento desativada'
        ]);

        header('Location: ' . DIRPAGE . 'dashboard/paymentAccounts?success=' . urlencode('Conta desativada'));
        exit;
    }

    public function paymentHistory() {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        $status = trim((string) ($_GET['status'] ?? ''));
        $type = trim((string) ($_GET['type'] ?? ''));

        $allowedStatuses = ['pendente', 'processando', 'confirmado', 'cancelado', 'falhado', 'rejeitado'];
        $allowedTypes = ['commission_payout', 'system_commission', 'boost_fee', 'trust_badge_fee', 'manual_adjustment', 'subscription_fee'];
        $status = in_array($status, $allowedStatuses, true) ? $status : '';
        $type = in_array($type, $allowedTypes, true) ? $type : '';

        $transactions = \App\model\PaymentTransaction::getByCounterpartyUserFiltered(
            (int) $user['id'],
            $status !== '' ? $status : null,
            $type !== '' ? $type : null,
            200
        );

        $render = new ClassRender();
        $render->setTitle('Histórico de Pagamentos');
        $render->setDescription('Consulte seu histórico de transações');
        $render->setKeywords('pagamentos, histórico, transações');
        $render->setData([
            'user' => $user,
            'transactions' => $transactions,
            'filterStatus' => $status,
            'filterType' => $type,
        ]);
        $render->setDir('dashboard/payment_history');
        $render->renderLayout();
    }

        public function exportPaymentHistoryCsv() {
                ClassAuth::requireAuth();
                $user = ClassAuth::user();

            $status = trim((string) ($_GET['status'] ?? ''));
            $type = trim((string) ($_GET['type'] ?? ''));
            $allowedStatuses = ['pendente', 'processando', 'confirmado', 'cancelado', 'falhado', 'rejeitado'];
            $allowedTypes = ['commission_payout', 'system_commission', 'boost_fee', 'trust_badge_fee', 'manual_adjustment', 'subscription_fee'];
            $status = in_array($status, $allowedStatuses, true) ? $status : '';
            $type = in_array($type, $allowedTypes, true) ? $type : '';

            $rows = PaymentTransaction::getByCounterpartyUserFiltered(
                (int) $user['id'],
                $status !== '' ? $status : null,
                $type !== '' ? $type : null,
                5000
            );

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="payment-history-' . (int) $user['id'] . '-' . date('Ymd-His') . '.csv"');

                $out = fopen('php://output', 'w');
                if ($out === false) {
                        exit;
                }

                fputcsv($out, ['id', 'tipo', 'estado', 'direcao', 'montante', 'moeda', 'metodo', 'referencia', 'criada_em', 'confirmada_em']);

                foreach ($rows as $row) {
                        fputcsv($out, [
                                (int) ($row['id'] ?? 0),
                                (string) ($row['transaction_type'] ?? ''),
                                (string) ($row['status'] ?? ''),
                                (string) ($row['direction'] ?? ''),
                                (string) ($row['amount'] ?? ''),
                                (string) ($row['currency'] ?? ''),
                                (string) ($row['method_name'] ?? ''),
                                (string) ($row['reference_code'] ?? ''),
                                (string) ($row['created_at'] ?? ''),
                                (string) ($row['confirmed_at'] ?? ''),
                        ]);
                }

                fclose($out);
                exit;
        }

        public function exportPaymentHistoryPdf() {
                ClassAuth::requireAuth();
                $user = ClassAuth::user();

            $status = trim((string) ($_GET['status'] ?? ''));
            $type = trim((string) ($_GET['type'] ?? ''));
            $allowedStatuses = ['pendente', 'processando', 'confirmado', 'cancelado', 'falhado', 'rejeitado'];
            $allowedTypes = ['commission_payout', 'system_commission', 'boost_fee', 'trust_badge_fee', 'manual_adjustment', 'subscription_fee'];
            $status = in_array($status, $allowedStatuses, true) ? $status : '';
            $type = in_array($type, $allowedTypes, true) ? $type : '';

            $rows = PaymentTransaction::getByCounterpartyUserFiltered(
                (int) $user['id'],
                $status !== '' ? $status : null,
                $type !== '' ? $type : null,
                5000
            );
                $this->streamPaymentHistoryPdf(
                        $rows,
                        'Meu Histórico de Pagamentos',
                'Utilizador: ' . (string) ($user['name'] ?? ('#' . (int) $user['id']))
                    . ' | Estado: ' . ($status !== '' ? $status : 'todos')
                    . ' | Tipo: ' . ($type !== '' ? $type : 'todos'),
                        'payment-history-' . (int) $user['id'] . '-' . date('Ymd-His') . '.pdf'
                );
        }

        public function exportPaymentsHistoryCsv() {
                ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponível apenas para a equipa financeira');

                $rows = PaymentTransaction::getListForExport(null, null, 10000);

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="payments-history-admin-' . date('Ymd-His') . '.csv"');

                $out = fopen('php://output', 'w');
                if ($out === false) {
                        exit;
                }

                fputcsv($out, ['id', 'tipo', 'estado', 'direcao', 'montante', 'moeda', 'metodo', 'utilizador_id', 'utilizador_nome', 'referencia', 'criada_em', 'confirmada_em']);

                foreach ($rows as $row) {
                        fputcsv($out, [
                                (int) ($row['id'] ?? 0),
                                (string) ($row['transaction_type'] ?? ''),
                                (string) ($row['status'] ?? ''),
                                (string) ($row['direction'] ?? ''),
                                (string) ($row['amount'] ?? ''),
                                (string) ($row['currency'] ?? ''),
                                (string) ($row['method_name'] ?? ''),
                                (string) ($row['counterparty_user_id'] ?? ''),
                                (string) ($row['counterparty_name'] ?? ''),
                                (string) ($row['reference_code'] ?? ''),
                                (string) ($row['created_at'] ?? ''),
                                (string) ($row['confirmed_at'] ?? ''),
                        ]);
                }

                fclose($out);
                exit;
        }

        public function exportPaymentsHistoryPdf() {
                ClassAccess::requirePermission('payments.manage', 'dashboard', 'Acesso disponível apenas para a equipa financeira');

                $rows = PaymentTransaction::getListForExport(null, null, 10000);
                $this->streamPaymentHistoryPdf(
                        $rows,
                        'Histórico de Pagamentos (Admin)',
                        'Escopo: Central de Pagamentos',
                        'payments-history-admin-' . date('Ymd-His') . '.pdf'
                );
        }

        private function streamPaymentHistoryPdf(array $rows, string $title, string $scope, string $filename): void {
                $typeLabels = [
                        'commission_payout' => 'Comissão',
                        'system_commission' => 'Taxa do sistema',
                        'boost_fee' => 'Destaque',
                        'trust_badge_fee' => 'Selo',
                        'manual_adjustment' => 'Ajuste manual',
                        'subscription_fee' => 'Subscrição',
                ];

                $statusLabels = [
                        'pendente' => 'Pendente',
                        'processando' => 'Processando',
                        'confirmado' => 'Confirmado',
                        'cancelado' => 'Cancelado',
                        'falhado' => 'Falhado',
                        'rejeitado' => 'Rejeitado',
                ];

                $esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                $generatedAt = date('d/m/Y H:i');

                $statusTotals = [];
                $typeTotals = [];
                $grandTotalAmount = 0.0;
                foreach ($rows as $row) {
                        $rowStatus = (string) ($row['status'] ?? '');
                        $rowType = (string) ($row['transaction_type'] ?? '');
                        $statusTotals[$rowStatus] = ($statusTotals[$rowStatus] ?? 0) + 1;
                        $typeTotals[$rowType] = ($typeTotals[$rowType] ?? 0) + 1;
                        $grandTotalAmount += (float) ($row['amount'] ?? 0);
                }

                $statusItems = '';
                foreach ($statusTotals as $key => $count) {
                        $statusItems .= '<li>' . $esc($statusLabels[$key] ?? ucfirst($key)) . ': ' . (int) $count . '</li>';
                }

                $typeItems = '';
                foreach ($typeTotals as $key => $count) {
                        $typeItems .= '<li>' . $esc($typeLabels[$key] ?? $key) . ': ' . (int) $count . '</li>';
                }

                $tableRows = '';
                foreach ($rows as $row) {
                        $tableRows .= '<tr>'
                                . '<td>#' . (int) ($row['id'] ?? 0) . '</td>'
                                . '<td>' . $esc((string) ($typeLabels[$row['transaction_type'] ?? ''] ?? ($row['transaction_type'] ?? 'Outro'))) . '</td>'
                                . '<td>' . $esc((string) ($statusLabels[$row['status'] ?? ''] ?? ($row['status'] ?? ''))) . '</td>'
                                . '<td>' . $esc((string) ($row['direction'] ?? '')) . '</td>'
                                . '<td>' . $esc(number_format((float) ($row['amount'] ?? 0), 2, ',', '.') . ' ' . (string) ($row['currency'] ?? 'AOA')) . '</td>'
                                . '<td>' . $esc((string) ($row['method_name'] ?? 'N/A')) . '</td>'
                                . '<td>' . $esc((string) ($row['counterparty_name'] ?? 'N/A')) . '</td>'
                                . '<td>' . $esc((string) ($row['reference_code'] ?? '')) . '</td>'
                                . '<td>' . $esc((string) ($row['created_at'] ?? '')) . '</td>'
                                . '<td>' . $esc((string) ($row['confirmed_at'] ?? '')) . '</td>'
                                . '</tr>';
                }

                if ($tableRows === '') {
                        $tableRows = '<tr><td colspan="10" style="text-align:center;">Sem transações para exportar.</td></tr>';
                }

                $html = '<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 24px 20px 36px 20px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .header { margin-bottom: 10px; }
        .brand { display: table; width: 100%; margin-bottom: 8px; }
        .brand-left, .brand-right { display: table-cell; vertical-align: middle; }
        .brand-right { text-align: right; }
        .brand-text { font-size: 24px; font-weight: 700; }
        .brand-imobil { color: #0b2f7a; }
        .brand-facil { color: #f2b705; }
        .title { margin: 0; font-size: 16px; }
        .meta { margin: 4px 0 0; color: #4b5563; }
        .summary { margin: 10px 0 12px; border: 1px solid #d1d5db; background: #f9fafb; }
        .summary-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .summary-table td { border: 1px solid #e5e7eb; padding: 6px; vertical-align: top; }
        .summary-label { width: 150px; font-weight: 700; background: #f3f4f6; }
        .summary-list { margin: 0; padding-left: 16px; }
        .summary-list li { margin: 0 0 2px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #d1d5db; padding: 5px; vertical-align: top; word-wrap: break-word; }
        th { background: #f3f4f6; text-align: left; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            <div class="brand-left"><div class="brand-text"><span class="brand-imobil">Imobil</span><span class="brand-facil">Fácil</span></div></div>
            <div class="brand-right">
                <h1 class="title">' . $esc($title) . '</h1>
                <p class="meta">Gerado em ' . $esc($generatedAt) . '</p>
            </div>
        </div>
        <p class="meta">' . $esc($scope) . ' | Registos: ' . count($rows) . '</p>
    </div>

    <div class="summary">
        <table class="summary-table">
            <tr>
                <td class="summary-label">Total financeiro</td>
                <td>' . $esc(number_format($grandTotalAmount, 2, ',', '.') . ' AOA') . '</td>
            </tr>
            <tr>
                <td class="summary-label">Totais por estado</td>
                <td>' . ($statusItems !== '' ? '<ul class="summary-list">' . $statusItems . '</ul>' : 'Sem dados') . '</td>
            </tr>
            <tr>
                <td class="summary-label">Totais por tipo</td>
                <td>' . ($typeItems !== '' ? '<ul class="summary-list">' . $typeItems . '</ul>' : 'Sem dados') . '</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Direção</th>
                <th>Montante</th>
                <th>Método</th>
                <th>Utilizador</th>
                <th>Referência</th>
                <th>Criada em</th>
                <th>Confirmada em</th>
            </tr>
        </thead>
        <tbody>' . $tableRows . '</tbody>
    </table>
</body>
</html>';

                $options = new Options();
                $options->set('isRemoteEnabled', false);
                $options->set('defaultFont', 'DejaVu Sans');

                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($html, 'UTF-8');
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();

                $canvas = $dompdf->getCanvas();
                $fontMetrics = $dompdf->getFontMetrics();
                $footerFont = $fontMetrics->getFont('DejaVu Sans', 'normal');
                $canvas->page_text(20, 575, 'ImobilFácil - Simples para anunciar, seguro para negociar', $footerFont, 8, [0.45, 0.45, 0.45]);
                $canvas->page_text(760, 575, 'Página {PAGE_NUM} de {PAGE_COUNT}', $footerFont, 8, [0.45, 0.45, 0.45]);

                $dompdf->stream($filename, ['Attachment' => true]);
                exit;
        }

    public function subscription() {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores não possuem subscrição comercial.');

        $currentSubscription = UserSubscription::getCurrentByUser((int) $user['id']);
        $history = UserSubscription::getHistoryByUser((int) $user['id'], 24);
        $plans = SubscriptionPlan::getActiveCatalog();

        $render = new ClassRender();
        $render->setTitle('Meu Plano');
        $render->setDescription('Gerencie seu plano de publicação e renovação');
        $render->setKeywords('plano, subscrição, assinatura');
        $render->setData([
            'user' => $user,
            'currentSubscription' => $currentSubscription,
            'plans' => $plans,
            'history' => $history,
            'csrfField' => ClassCsrf::field(),
        ]);
        $render->setDir('dashboard/subscription');
        $render->renderLayout();
    }

    public function subscriptionCheckout() {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores não possuem subscrição comercial.');

        $planCode = trim(strtolower((string) ($_GET['plan_code'] ?? '')));
        if ($planCode === '') {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=Selecione um plano válido');
            exit;
        }

        $plan = SubscriptionPlan::findByCode($planCode);
        if (!$plan || empty($plan['is_active'])) {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=Plano inválido');
            exit;
        }

        if (!empty($plan['is_custom_pricing'])) {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=Plano empresarial requer proposta comercial. Contacte o suporte.');
            exit;
        }

        $currentSubscription = UserSubscription::getCurrentByUser((int) $user['id']);
        $preferredAutoRenew = !empty($_GET['auto_renew']);

        $methodsRaw = PaymentMethod::getActive('user');
        $paymentMethods = [];
        $channelsByMethod = [];
        foreach ($methodsRaw as $method) {
            $direction = (string) ($method['direction'] ?? 'both');
            if (!in_array($direction, ['incoming', 'both'], true)) {
                continue;
            }

            $methodId = (int) ($method['id'] ?? 0);
            if ($methodId <= 0) {
                continue;
            }

            $paymentMethods[] = $method;
            $channelsByMethod[$methodId] = SystemPaymentChannel::getActiveByMethodId($methodId);
        }

        $isPaidPlan = (float) ($plan['monthly_price_aoa'] ?? 0) > 0;
        if ($isPaidPlan && empty($paymentMethods)) {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=' . rawurlencode('Nenhum método de recebimento está disponível no momento.'));
            exit;
        }

        $render = new ClassRender();
        $render->setTitle('Finalizar subscrição');
        $render->setDescription('Informe os dados de duração e pagamento do plano selecionado');
        $render->setKeywords('subscrição, pagamento, plano, checkout');
        $render->setData([
            'user' => $user,
            'plan' => $plan,
            'planCode' => $planCode,
            'currentSubscription' => $currentSubscription,
            'preferredAutoRenew' => $preferredAutoRenew,
            'paymentMethods' => $paymentMethods,
            'channelsByMethod' => $channelsByMethod,
            'csrfField' => ClassCsrf::field(),
        ]);
        $render->setDir('dashboard/subscription_checkout');
        $render->renderLayout();
    }

    public function confirmSubscriptionCheckout() {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores não possuem subscrição comercial.');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=Token inválido');
            exit;
        }

        $planCode = trim(strtolower((string) ($_POST['plan_code'] ?? '')));
        if ($planCode === '') {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=Selecione um plano válido');
            exit;
        }

        $plan = SubscriptionPlan::findByCode($planCode);
        if (!$plan || empty($plan['is_active'])) {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=Plano inválido');
            exit;
        }

        if (!empty($plan['is_custom_pricing'])) {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=Plano empresarial requer proposta comercial. Contacte o suporte.');
            exit;
        }

        $durationMonths = (int) ($_POST['duration_months'] ?? 1);
        $allowedDurations = [1, 3, 6, 12];
        if (!in_array($durationMonths, $allowedDurations, true)) {
            header('Location: ' . DIRPAGE . 'dashboard/subscriptionCheckout?plan_code=' . rawurlencode($planCode) . '&error=' . rawurlencode('Duração inválida'));
            exit;
        }

        $isPaidPlan = (float) ($plan['monthly_price_aoa'] ?? 0) > 0;
        $autoRenew = !empty($_POST['auto_renew']);
        $paymentMethodId = (int) ($_POST['payment_method_id'] ?? 0);
        $systemChannelId = (int) ($_POST['system_channel_id'] ?? 0);
        $referenceCode = trim((string) ($_POST['reference_code'] ?? ''));

        $amount = (float) ($plan['monthly_price_aoa'] ?? 0) * $durationMonths;
        $dueDate = date('Y-m-d', strtotime('+' . $durationMonths . ' month'));

        $paymentMethod = null;
        $channel = null;
        if ($isPaidPlan) {
            if ($paymentMethodId <= 0) {
                header('Location: ' . DIRPAGE . 'dashboard/subscriptionCheckout?plan_code=' . rawurlencode($planCode) . '&error=' . rawurlencode('Selecione a forma de pagamento'));
                exit;
            }

            $paymentMethod = PaymentMethod::findById($paymentMethodId);
            $methodDirection = (string) ($paymentMethod['direction'] ?? 'both');
            $methodActive = !empty($paymentMethod['is_active']);
            if (!$paymentMethod || !$methodActive || !in_array($methodDirection, ['incoming', 'both'], true)) {
                header('Location: ' . DIRPAGE . 'dashboard/subscriptionCheckout?plan_code=' . rawurlencode($planCode) . '&error=' . rawurlencode('Forma de pagamento inválida'));
                exit;
            }

            if ($systemChannelId > 0) {
                $channel = SystemPaymentChannel::findById($systemChannelId);
                if (!$channel || (int) ($channel['method_id'] ?? 0) !== $paymentMethodId || empty($channel['is_active'])) {
                    header('Location: ' . DIRPAGE . 'dashboard/subscriptionCheckout?plan_code=' . rawurlencode($planCode) . '&error=' . rawurlencode('Canal de pagamento inválido'));
                    exit;
                }
            }
        }

        $proofPath = null;
        if ($isPaidPlan) {
            $proofFile = $_FILES['payment_proof'] ?? null;
            if (empty($proofFile['tmp_name']) || ($proofFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                header('Location: ' . DIRPAGE . 'dashboard/subscriptionCheckout?plan_code=' . rawurlencode($planCode) . '&error=' . rawurlencode('Comprovativo de pagamento é obrigatório'));
                exit;
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $proofMime = (string) $finfo->file((string) $proofFile['tmp_name']);
            $allowedProofMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($proofMime, $allowedProofMimes, true)) {
                header('Location: ' . DIRPAGE . 'dashboard/subscriptionCheckout?plan_code=' . rawurlencode($planCode) . '&error=' . rawurlencode('Formato inválido. Use JPG, PNG, GIF ou WebP'));
                exit;
            }

            if ((int) ($proofFile['size'] ?? 0) > 1024 * 1024) {
                header('Location: ' . DIRPAGE . 'dashboard/subscriptionCheckout?plan_code=' . rawurlencode($planCode) . '&error=' . rawurlencode('Comprovativo demasiado grande. Máximo: 1MB'));
                exit;
            }

            $proofUploadDirRelative = 'public/storage/uploads/subscription_proofs/';
            $proofUploadDir = DIRREQ . $proofUploadDirRelative;
            if (!is_dir($proofUploadDir)) {
                mkdir($proofUploadDir, 0755, true);
            }

            $proofExtMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            $proofExt = $proofExtMap[$proofMime] ?? 'jpg';
            $proofFilename = 'sub_' . (int) $user['id'] . '_' . time() . '.' . $proofExt;
            if (!move_uploaded_file((string) $proofFile['tmp_name'], $proofUploadDir . $proofFilename)) {
                header('Location: ' . DIRPAGE . 'dashboard/subscriptionCheckout?plan_code=' . rawurlencode($planCode) . '&error=' . rawurlencode('Erro ao guardar o comprovativo'));
                exit;
            }

            $proofPath = $proofUploadDirRelative . $proofFilename;
        }

        $notes = 'Checkout do plano pelo utilizador | duração: ' . $durationMonths . ' mês(es) | método: ' . (string) ($paymentMethod['code'] ?? 'n/a');

        $subscriptionId = 0;

        if ($isPaidPlan) {
            $pendingId = UserSubscription::createPendingActivationForUser(
                (int) $user['id'],
                $planCode,
                $durationMonths,
                $autoRenew,
                (int) $user['id'],
                $notes
            );

            if (!$pendingId) {
                header('Location: ' . DIRPAGE . 'dashboard/subscription?error=Não foi possível criar a solicitação de subscrição');
                exit;
            }

            $subscriptionId = (int) $pendingId;

            PaymentTransaction::create([
                'transaction_type' => 'subscription_fee',
                'direction' => 'incoming',
                'status' => 'pendente',
                'amount' => $amount,
                'currency' => 'AOA',
                'method_id' => $paymentMethodId > 0 ? $paymentMethodId : null,
                'system_channel_id' => $systemChannelId > 0 ? $systemChannelId : null,
                'counterparty_user_id' => (int) $user['id'],
                'related_entity_type' => 'user_subscription',
                'related_entity_id' => $subscriptionId,
                'reference_code' => $referenceCode !== '' ? $referenceCode : null,
                'proof_file' => $proofPath,
                'notes' => 'Subscrição pendente de validação financeira: ' . (string) ($plan['name'] ?? $planCode) . ' por ' . $durationMonths . ' mês(es). Vencimento previsto: ' . $dueDate,
                'created_by' => (int) $user['id'],
            ]);

            Log::create([
                'user_id' => (int) $user['id'],
                'action' => 'request_subscription_plan_change',
                'entity_type' => 'user_subscription',
                'entity_id' => $subscriptionId,
                'details' => 'Solicitação enviada para ' . (string) ($plan['name'] ?? $planCode) . ' | duração: ' . $durationMonths . ' mês(es) | método: ' . (string) ($paymentMethod['name'] ?? 'N/A') . ' | vencimento: ' . $dueDate,
            ]);

            header('Location: ' . DIRPAGE . 'dashboard/subscription?success=' . rawurlencode('Solicitação enviada. O plano será ativado após validação financeira do pagamento.'));
            exit;
        }

        $ok = UserSubscription::activatePlanForUser(
            (int) $user['id'],
            $planCode,
            $autoRenew,
            (int) $user['id'],
            $notes,
            $durationMonths
        );

        if (!$ok) {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=Não foi possível atualizar o plano');
            exit;
        }

        $currentSubscription = UserSubscription::getCurrentByUser((int) $user['id']);
        $subscriptionId = (int) ($currentSubscription['id'] ?? 0);

        Log::create([
            'user_id' => (int) $user['id'],
            'action' => 'change_subscription_plan',
            'entity_type' => 'user_subscription',
            'entity_id' => $subscriptionId,
            'details' => 'Plano alterado para ' . (string) ($plan['name'] ?? $planCode) . ' | duração: ' . $durationMonths . ' mês(es) | vencimento: ' . $dueDate,
        ]);

        header('Location: ' . DIRPAGE . 'dashboard/subscription?success=' . rawurlencode('Plano atualizado com sucesso'));
        exit;
    }

    public function changeSubscription() {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores não possuem subscrição comercial.');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=Token inválido');
            exit;
        }

        $planCode = trim(strtolower((string) ($_POST['plan_code'] ?? '')));
        if ($planCode === '') {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=Selecione um plano válido');
            exit;
        }

        $plan = SubscriptionPlan::findByCode($planCode);
        if (!$plan || empty($plan['is_active'])) {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=Plano inválido');
            exit;
        }

        if (!empty($plan['is_custom_pricing'])) {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=Plano empresarial requer proposta comercial. Contacte o suporte.');
            exit;
        }

        $autoRenew = !empty($_POST['auto_renew']);
        $ok = UserSubscription::activatePlanForUser(
            (int) $user['id'],
            $planCode,
            $autoRenew,
            (int) $user['id'],
            'Alteração solicitada pelo utilizador no dashboard'
        );

        if (!$ok) {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=Não foi possível atualizar o plano');
            exit;
        }

        Log::create([
            'user_id' => (int) $user['id'],
            'action' => 'change_subscription_plan',
            'entity_type' => 'user_subscription',
            'entity_id' => 0,
            'details' => 'Plano alterado para ' . (string) ($plan['name'] ?? $planCode),
        ]);

        header('Location: ' . DIRPAGE . 'dashboard/subscription?success=Plano atualizado com sucesso');
        exit;
    }

    // ─── Fase 4: Relatórios de imóveis (Professional+) ───────────────────────

    public function propertyReports() {
        ClassAuth::requireAuth();
        $user = ClassAuth::user();

        if (!ClassPlan::canViewReports((int) $user['id'])) {
            header('Location: ' . DIRPAGE . 'dashboard/subscription?error=' . rawurlencode('Esta funcionalidade requer o Plano Profissional ou superior.'));
            exit;
        }

        $stats = Property::getStatsForOwner((int) $user['id']);
        $plan  = ClassPlan::getOfficialPlanByUser((int) $user['id']);
        $isAdvanced = ClassPlan::canViewAdvancedReports((int) $user['id']);

        $render = new ClassRender();
        $render->setTitle('Relatórios de Imóveis');
        $render->setDescription('Estatísticas e desempenho do portfólio');
        $render->setKeywords('relatórios, estatísticas, imóveis');
        $render->setData([
            'user'       => $user,
            'stats'      => $stats,
            'plan'       => $plan,
            'isAdvanced' => $isAdvanced,
        ]);
        $render->setDir('dashboard/property_reports');
        $render->renderLayout();
    }

    // ─── Admin: gerir subscrições de utilizadores ────────────────────────────

    public function adminSubscriptions() {
        $user = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 30;
        $offset  = ($page - 1) * $perPage;

        $search  = trim((string) ($_GET['search'] ?? ''));
        $status  = trim((string) ($_GET['status'] ?? ''));
        $planFilter = trim((string) ($_GET['plan'] ?? ''));

        $db     = new \App\model\UserSubscription();
        $conn   = $db->ConexaoDB();

        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = '(u.name LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($status !== '') {
            $where[]  = 'us.status = ?';
            $params[] = $status;
        }
        if ($planFilter !== '') {
            $where[]  = 'sp.code = ?';
            $params[] = $planFilter;
        }

        $whereClause = implode(' AND ', $where);
        $pickSub = \App\model\UserSubscription::sqlPrimaryOpenSubscriptionPickSubquery();

        $countSql = "SELECT COUNT(*) FROM user_subscriptions us
                     INNER JOIN subscription_plans sp ON sp.id = us.plan_id
                     INNER JOIN users u ON u.id = us.user_id
                     INNER JOIN {$pickSub} us_primary
                        ON us_primary.user_id = us.user_id AND us_primary.subscription_id = us.id
                     WHERE {$whereClause}";
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT us.*, sp.code AS plan_code, sp.name AS plan_name, sp.ranking_weight,
                       u.name AS user_name, u.email AS user_email
                FROM user_subscriptions us
                INNER JOIN subscription_plans sp ON sp.id = us.plan_id
                INNER JOIN users u ON u.id = us.user_id
                INNER JOIN {$pickSub} us_primary
                    ON us_primary.user_id = us.user_id AND us_primary.subscription_id = us.id
                WHERE {$whereClause}
                ORDER BY us.updated_at DESC, us.id DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $subscriptions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $plans = \App\model\SubscriptionPlan::getActiveCatalog();

        $render = new \Src\classes\ClassRender();
        $render->setData([
            'user'          => $user,
            'subscriptions' => $subscriptions,
            'plans'         => $plans,
            'total'         => $total,
            'page'          => $page,
            'perPage'       => $perPage,
            'search'        => $search,
            'status'        => $status,
            'planFilter'    => $planFilter,
            'csrfField'     => ClassCsrf::field(),
        ]);
        $render->setDir('dashboard/admin_subscriptions');
        $render->renderLayout();
    }

    public function adminSetSubscription() {
        $user = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptions');
            exit;
        }

        ClassCsrf::verify(
            $_POST['csrf_token'] ?? '',
            DIRPAGE . 'dashboard/adminSubscriptions?error=' . urlencode('Token inválido')
        );

        $targetUserId = (int) ($_POST['target_user_id'] ?? 0);
        $planCode     = trim(strtolower((string) ($_POST['plan_code'] ?? '')));
        $autoRenew    = !empty($_POST['auto_renew']);
        $notes        = trim(strip_tags((string) ($_POST['notes'] ?? '')));
        $billingMonths = max(1, min(12, (int) ($_POST['billing_cycle_months'] ?? 1)));

        if ($targetUserId <= 0 || $planCode === '') {
            header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptions?error=Dados inválidos');
            exit;
        }

        $plan = SubscriptionPlan::findByCode($planCode);
        if (!$plan || empty($plan['is_active'])) {
            header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptions?error=Plano inválido');
            exit;
        }

        if (!empty($plan['is_custom_pricing'])) {
            header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptionCheckout?'
                . http_build_query(['target_user_id' => $targetUserId, 'plan_code' => $planCode]));
            exit;
        }

        $ok = UserSubscription::activatePlanForUser(
            $targetUserId,
            $planCode,
            $autoRenew,
            (int) $user['id'],
            $notes !== '' ? $notes : 'Alteração manual pelo administrador',
            $billingMonths
        );

        if (!$ok) {
            header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptions?error=Não foi possível atualizar o plano');
            exit;
        }

        Log::create([
            'user_id'     => (int) $user['id'],
            'action'      => 'admin_set_subscription_plan',
            'entity_type' => 'user_subscription',
            'entity_id'   => $targetUserId,
            'details'     => 'Admin alterou plano do utilizador #' . $targetUserId . ' para ' . $planCode,
        ]);

        header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptions?success=Plano atualizado');
        exit;
    }

    public function adminSubscriptionCheckout() {
        $admin = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        $targetUserId = (int) ($_GET['target_user_id'] ?? 0);
        $planCode = trim(strtolower((string) ($_GET['plan_code'] ?? '')));

        if ($targetUserId <= 0 || $planCode === '') {
            header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptions?error=' . urlencode('Utilizador e plano são obrigatórios'));
            exit;
        }

        $targetUser = User::findById($targetUserId);
        if (!$targetUser) {
            header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptions?error=' . urlencode('Utilizador não encontrado'));
            exit;
        }

        $plan = SubscriptionPlan::findByCode($planCode);
        if (!$plan || empty($plan['is_active'])) {
            header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptions?error=' . urlencode('Plano inválido'));
            exit;
        }

        if (empty($plan['is_custom_pricing'])) {
            header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptions?error=' . urlencode('Este checkout é apenas para planos com preço negociado (Empresarial)'));
            exit;
        }

        $currentSubscription = UserSubscription::getCurrentByUser($targetUserId);

        $render = new ClassRender();
        $render->setTitle('Configurar plano empresarial');
        $render->setDescription('Definir contrato negociado para utilizador');
        $render->setKeywords('admin, subscrição, empresarial');
        $render->setData([
            'user' => $admin,
            'targetUser' => $targetUser,
            'plan' => $plan,
            'planCode' => $planCode,
            'currentSubscription' => $currentSubscription,
            'csrfField' => ClassCsrf::field(),
        ]);
        $render->setDir('dashboard/admin_subscription_checkout');
        $render->renderLayout();
    }

    public function confirmAdminSubscriptionCheckout() {
        $admin = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !ClassCsrf::validate($_POST['csrf_token'] ?? '')) {
            header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptions?error=' . urlencode('Token inválido'));
            exit;
        }

        $targetUserId = (int) ($_POST['target_user_id'] ?? 0);
        $planCode = trim(strtolower((string) ($_POST['plan_code'] ?? '')));
        $billingMonths = max(1, min(12, (int) ($_POST['billing_cycle_months'] ?? 1)));
        $allowedDurations = [1, 3, 6, 12];
        $autoRenew = !empty($_POST['auto_renew']);
        $notes = trim(strip_tags((string) ($_POST['notes'] ?? '')));
        $negotiatedPrice = (float) str_replace([' ', ','], ['', '.'], (string) ($_POST['negotiated_price_aoa'] ?? '0'));

        $checkoutBack = DIRPAGE . 'dashboard/adminSubscriptionCheckout?'
            . http_build_query(['target_user_id' => $targetUserId, 'plan_code' => $planCode]);

        if ($targetUserId <= 0 || $planCode === '') {
            header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptions?error=' . urlencode('Dados inválidos'));
            exit;
        }

        if (!in_array($billingMonths, $allowedDurations, true)) {
            header('Location: ' . $checkoutBack . '&error=' . urlencode('Duração inválida'));
            exit;
        }

        if ($negotiatedPrice <= 0) {
            header('Location: ' . $checkoutBack . '&error=' . urlencode('Indique o valor total negociado (Kz)'));
            exit;
        }

        $plan = SubscriptionPlan::findByCode($planCode);
        if (!$plan || empty($plan['is_active']) || empty($plan['is_custom_pricing'])) {
            header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptions?error=' . urlencode('Plano inválido para este checkout'));
            exit;
        }

        if (!User::findById($targetUserId)) {
            header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptions?error=' . urlencode('Utilizador não encontrado'));
            exit;
        }

        $noteLine = $notes !== ''
            ? $notes
            : 'Plano empresarial atribuído pelo administrador';
        $noteLine .= ' | valor negociado: ' . number_format($negotiatedPrice, 0, ',', '.') . ' Kz / ' . $billingMonths . ' mês(es)';

        $ok = UserSubscription::activatePlanForUser(
            $targetUserId,
            $planCode,
            $autoRenew,
            (int) $admin['id'],
            $noteLine,
            $billingMonths,
            true,
            $negotiatedPrice
        );

        if (!$ok) {
            header('Location: ' . $checkoutBack . '&error=' . urlencode('Não foi possível activar o plano'));
            exit;
        }

        Log::create([
            'user_id' => (int) $admin['id'],
            'action' => 'admin_set_subscription_plan',
            'entity_type' => 'user_subscription',
            'entity_id' => $targetUserId,
            'details' => 'Empresarial activado para #' . $targetUserId . ' | ' . number_format($negotiatedPrice, 0, ',', '.') . ' Kz / ' . $billingMonths . ' mês(es)',
        ]);

        header('Location: ' . DIRPAGE . 'dashboard/adminSubscriptions?success=' . urlencode('Plano empresarial activado com sucesso'));
        exit;
    }

    public function settings() {
        $user = ClassAccess::requireSuperAdmin('dashboard', 'Acesso disponível apenas para Admin Total');

        $errors  = [];
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ClassCsrf::verify(
                $_POST['csrf_token'] ?? '',
                DIRPAGE . 'settings?error=' . urlencode('Token inválido')
            );

            $allowedKeys = [
                'commission_system_pct',
                'commission_affiliate_pct',
                'commission_system_only_pct',
                'commission_due_days',
                'rate_limit_post_max',
                'rate_limit_post_window_seconds',
                'trust_badge_monthly_fee',
                'trust_badge_min_months',
                'trust_badge_max_months',
                'trust_badge_default_months',
                'trust_badge_min_won_deals',
                'trust_badge_min_account_days',
                'trust_badge_require_confirmed_closing',
                'boost_daily_fee',
                'boost_min_days',
                'boost_max_days',
                'boost_default_days',
                'behavior_ranking_enabled',
                'behavior_ranking_lookback_days',
                'behavior_weight_view',
                'behavior_weight_favorite',
                'behavior_weight_request',
                'behavior_max_score_per_property',
                'behavior_decay_lambda',
                'behavior_view_penalty_threshold',
                'behavior_view_penalty_points',
                'behavior_explore_ratio',
                'behavior_impression_cooldown_hours',
                'behavior_home_carousel_size',
                'behavior_continue_exploring_size',
                'behavior_promoted_interval',
            ];

            $integerKeys = [
                'commission_due_days',
                'rate_limit_post_max',
                'rate_limit_post_window_seconds',
                'trust_badge_min_months',
                'trust_badge_max_months',
                'trust_badge_default_months',
                'boost_min_days',
                'boost_max_days',
                'boost_default_days',
                'behavior_ranking_lookback_days',
                'behavior_weight_view',
                'behavior_weight_favorite',
                'behavior_weight_request',
                'behavior_max_score_per_property',
                'behavior_view_penalty_threshold',
                'behavior_view_penalty_points',
                'behavior_explore_ratio',
                'behavior_impression_cooldown_hours',
                'behavior_home_carousel_size',
                'behavior_continue_exploring_size',
                'behavior_promoted_interval',
            ];

            $booleanKeys = [
                'behavior_ranking_enabled',
                'trust_badge_require_confirmed_closing',
            ];

            $pendingSettings = [];

            foreach ($allowedKeys as $key) {
                if (!isset($_POST[$key])) {
                    continue;
                }
                $val = trim($_POST[$key]);

                if (in_array($key, $booleanKeys, true)) {
                    if (!is_numeric($val) || !in_array((int) $val, [0, 1], true)) {
                        $errors[$key] = 'Use 0 (desligado) ou 1 (ligado).';
                        continue;
                    }
                    $pendingSettings[$key] = (string) ((int) $val);
                    continue;
                }

                if (!is_numeric($val) || $val < 0) {
                    $errors[$key] = 'Valor inválido.';
                    continue;
                }

                if ($key === 'behavior_decay_lambda' && ((float) $val <= 0 || (float) $val > 1)) {
                    $errors[$key] = 'Use um valor entre 0.001 e 1.';
                    continue;
                }

                if ($key === 'behavior_explore_ratio' && (int) $val > 30) {
                    $errors[$key] = 'Máximo 30%.';
                    continue;
                }

                if (in_array($key, $integerKeys, true) && (int) $val < 1) {
                    $errors[$key] = 'Use um valor inteiro maior ou igual a 1.';
                    continue;
                }

                $pendingSettings[$key] = $val;
            }

            $minMonths = isset($_POST['trust_badge_min_months']) ? (int) $_POST['trust_badge_min_months'] : 1;
            $maxMonths = isset($_POST['trust_badge_max_months']) ? (int) $_POST['trust_badge_max_months'] : 12;
            $defaultMonths = isset($_POST['trust_badge_default_months']) ? (int) $_POST['trust_badge_default_months'] : 6;

            if ($maxMonths < $minMonths) {
                $errors['trust_badge_max_months'] = 'O máximo não pode ser menor que o mínimo.';
            }
            if ($defaultMonths < $minMonths || $defaultMonths > $maxMonths) {
                $errors['trust_badge_default_months'] = 'O padrão deve estar entre mínimo e máximo.';
            }

            $minDays     = isset($_POST['boost_min_days'])     ? (int) $_POST['boost_min_days']     : 7;
            $maxDays     = isset($_POST['boost_max_days'])     ? (int) $_POST['boost_max_days']     : 90;
            $defaultDays = isset($_POST['boost_default_days']) ? (int) $_POST['boost_default_days'] : 30;

            if ($maxDays < $minDays) {
                $errors['boost_max_days'] = 'O máximo não pode ser menor que o mínimo.';
            }
            if ($defaultDays < $minDays || $defaultDays > $maxDays) {
                $errors['boost_default_days'] = 'O padrão deve estar entre mínimo e máximo.';
            }

            if (empty($errors)) {
                foreach ($pendingSettings as $key => $val) {
                    ClassSettings::set($key, $val);
                }
                $success = true;
            }
        }

        $render = new ClassRender();
        $render->setTitle('Configurações do Sistema');
        $render->setDescription('Gerencie as configurações operacionais');
        $render->setKeywords('configurações, sistema, comissões');
        $render->setData([
            'user'     => $user,
            'settings' => ClassSettings::all(),
            'errors'   => $errors,
            'success'  => $success,
            'csrf'     => ClassCsrf::get(),
        ]);
        $render->setDir('dashboard/settings');
        $render->renderLayout();
    }

    public function myFavorites() {
        $user = ClassAccess::requireNonAdmin('dashboard', 'Administradores não têm favoritos');

        $propertyIds = Favorite::getPropertyIdsByUser((int) $user['id']);
        $properties  = Property::getByIds($propertyIds);

        $render = new ClassRender();
        $render->setTitle('Meus Favoritos');
        $render->setDescription('Imóveis que você marcou como favorito');
        $render->setKeywords('favoritos, imóveis');
        $render->setData([
            'user'       => $user,
            'properties' => $properties,
        ]);
        $render->setDir('dashboard/favorites');
        $render->renderLayout();
    }

    /**
     * @return array{error: ?string, uploaded: bool}
     */
    private function processIdentificationDocumentUpload(array $user): array {
        $file = $_FILES['document_file'] ?? null;
        if (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['error' => null, 'uploaded' => false];
        }

        $userId = (int) ($user['id'] ?? 0);
        $uploadDir = DIRREQ . 'storage/documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $rejectedDocuments = Document::getRejectedByUser($userId);
        if (!empty($rejectedDocuments)) {
            $rejectedDoc = $rejectedDocuments[0];
            $validation = ClassDocumentValidator::validateFile($file, (string) ($rejectedDoc['type'] ?? ClassDocumentValidator::TYPE_USER_REGISTRATION));
            if (!$validation['valid']) {
                return ['error' => (string) $validation['error'], 'uploaded' => false];
            }

            $tmpPath = (string) ($file['tmp_name'] ?? '');
            $originalName = (string) ($file['name'] ?? '');
            $nextVersion = ClassDocumentValidator::getNextVersion((string) ($rejectedDoc['version'] ?? 'v1'));
            $filename = ClassDocumentValidator::generateFilename($originalName, $nextVersion);

            if (!move_uploaded_file($tmpPath, $uploadDir . $filename)) {
                return ['error' => 'Falha ao guardar o documento.', 'uploaded' => false];
            }

            Document::create($userId, null, (string) $rejectedDoc['type'], $filename, $nextVersion);

            Log::create([
                'user_id' => $userId,
                'action' => 'resubmit_document',
                'entity_type' => 'document',
                'entity_id' => (int) ($rejectedDoc['id'] ?? 0),
                'details' => 'Documento resubmetido na versão ' . $nextVersion,
            ]);

            Notification::notifyUsers(
                User::getActiveAdminIds(),
                'document_resubmitted',
                'Documento resubmetido',
                'Um utilizador resubmeteu um documento de identificação (' . $nextVersion . ').',
                ['user_id' => $userId],
                $userId
            );

            return ['error' => null, 'uploaded' => true];
        }

        $compliance = Document::getComplianceStatus($userId);
        if ($compliance === 'compliant') {
            return ['error' => 'O seu documento já foi aceite — não é necessário enviar outro.', 'uploaded' => false];
        }

        $latest = Document::getLatestByUser($userId);
        if ($latest && (string) ($latest['status'] ?? '') === 'pendente') {
            return ['error' => 'Já estamos a analisar o último envio — aguarde antes de enviar outro ficheiro.', 'uploaded' => false];
        }

        $docType = ClassDocumentValidator::TYPE_USER_REGISTRATION;
        $validation = ClassDocumentValidator::validateFile($file, $docType);
        if (!$validation['valid']) {
            return ['error' => (string) $validation['error'], 'uploaded' => false];
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? '');
        $version = $latest
            ? ClassDocumentValidator::getNextVersion((string) ($latest['version'] ?? 'v1'))
            : 'v1';
        $filename = ClassDocumentValidator::generateFilename($originalName, $version);

        if (!move_uploaded_file($tmpPath, $uploadDir . $filename)) {
            return ['error' => 'Falha ao guardar o documento.', 'uploaded' => false];
        }

        Document::create($userId, null, $docType, $filename, $version);

        Log::create([
            'user_id' => $userId,
            'action' => 'submit_account_document',
            'entity_type' => 'document',
            'entity_id' => null,
            'details' => 'Documento enviado na área de identificação (' . $version . ')',
        ]);

        Notification::notifyUsers(
            User::getActiveAdminIds(),
            'document_resubmitted',
            'Novo documento para análise',
            'Um utilizador enviou documento de identificação (' . $version . ').',
            ['user_id' => $userId],
            $userId
        );

        return ['error' => null, 'uploaded' => true];
    }
}
