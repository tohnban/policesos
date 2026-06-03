<?php
    $requests = is_array($requests ?? []) ? $requests : [];
    $requestChatSummaries = is_array($requestChatSummaries ?? []) ? $requestChatSummaries : [];
    $requestView = (string) ($requestView ?? '');
    $scope = (string) ($scope ?? 'user');
    $canManageAllRequests = !empty($canManageAllRequests);
    $requestCount = count($requests);
    $selectedRequestId = (int) ($selectedRequestId ?? 0);
    $selectedRequest = is_array($selectedRequest ?? null) ? $selectedRequest : null;
    $hasChatSelected = $selectedRequestId > 0 && $selectedRequest !== null;

    if (!function_exists('requestChatsPageUrl')) {
        function requestChatsPageUrl(int $chatId = 0, string $view = '', array $extra = []): string {
            $params = $extra;
            if ($view !== '') {
                $params['view'] = $view;
            }
            if ($chatId > 0) {
                $params['chat'] = $chatId;
            }
            $query = http_build_query($params);

            return DIRPAGE . 'dashboard/requestChats' . ($query !== '' ? ('?' . $query) : '');
        }
    }

    $listBackUrl = requestChatsPageUrl(0, $requestView);
?>

<div class="container dashboard-view request-chats-dashboard-view<?php echo $hasChatSelected ? ' has-chat-selected' : ''; ?>">

    <section class="dashboard-view-hero compact request-chats-hero <?php echo $hasChatSelected ? 'is-chat-open' : ''; ?>">
        <div>
            <span class="dashboard-hero-kicker">Conversas</span>
            <h1><?php echo htmlspecialchars($pageTitle ?? 'Conversas de Negociação'); ?></h1>
            <p><?php echo htmlspecialchars($pageDescription ?? 'Veja em um único lugar as negociações e mensagens das suas solicitações.'); ?></p>
        </div>
    </section>

    <?php if (!empty($_GET['error'])): ?>
        <div class="sub-feedback error"><?php echo htmlspecialchars((string) $_GET['error']); ?></div>
    <?php endif; ?>

    <?php if ($scope === 'owner'): ?>
        <?php $requestView = $requestView !== '' ? $requestView : 'received'; ?>
        <div class="requests-scope-navigation">
            <div class="scope-toggle">
                <a href="<?php echo requestChatsPageUrl(0, 'received'); ?>"
                   class="scope-button <?php echo $requestView === 'received' ? 'active' : ''; ?>"
                   aria-current="<?php echo $requestView === 'received' ? 'page' : 'false'; ?>">
                    <i class="fa fa-inbox"></i>
                    <span>Recebidas</span>
                    <small>Negociações abertas nos seus imóveis</small>
                </a>
                <a href="<?php echo requestChatsPageUrl(0, 'sent'); ?>"
                   class="scope-button <?php echo $requestView === 'sent' ? 'active' : ''; ?>"
                   aria-current="<?php echo $requestView === 'sent' ? 'page' : 'false'; ?>">
                    <i class="fa fa-paper-plane"></i>
                    <span>Enviadas</span>
                    <small>Negociações que você iniciou</small>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="dashboard-module-card request-chats-shell <?php echo $hasChatSelected ? 'has-chat-selected' : ''; ?>">
        <div class="request-chats-layout">
            <aside class="request-chats-panel" aria-label="Lista de negociações">
                <div class="request-chats-panel-head">
                    <span class="dashboard-module-kicker">Painel</span>
                    <h3><?php echo $requestCount > 0 ? $requestCount : 'Nenhuma'; ?> negociação<?php echo $requestCount === 1 ? '' : 'ões'; ?></h3>
                </div>

                <?php if ($requestCount > 0): ?>
                    <div class="request-chats-panel-list" role="list">
                        <?php foreach ($requests as $request): ?>
                            <?php
                                $requestId = (int) ($request['id'] ?? 0);
                                $summary = $requestChatSummaries[$requestId] ?? [];
                                $unreadCount = (int) ($summary['unread_count'] ?? 0);
                                $commercialStatus = (string) ($request['commercial_status'] ?? ($request['status'] ?? ''));
                                $statusLabel = App\model\Request::statusLabel($commercialStatus, (string) ($request['closing_confirmation_status'] ?? ''));
                                $propertyTitle = htmlspecialchars((string) ($request['title'] ?? 'Solicitação #' . $requestId));
                                $chatUrl = requestChatsPageUrl($requestId, $requestView);
                                $isActive = $selectedRequestId === $requestId;
                                $preview = trim((string) ($summary['last_message_text'] ?? ''));
                                if ($preview !== '' && function_exists('mb_strimwidth')) {
                                    $preview = mb_strimwidth($preview, 0, 80, '...');
                                } elseif ($preview !== '') {
                                    $preview = substr($preview, 0, 80) . (strlen($preview) > 80 ? '...' : '');
                                }
                            ?>
                            <a href="<?php echo htmlspecialchars($chatUrl); ?>"
                               class="request-chats-panel-item <?php echo $isActive ? 'is-active' : ''; ?><?php echo $unreadCount > 0 ? ' has-unread' : ''; ?>"
                               role="listitem"
                               aria-current="<?php echo $isActive ? 'true' : 'false'; ?>">
                                <div class="request-chats-panel-item-top">
                                    <strong class="request-chats-panel-item-title"><?php echo $propertyTitle; ?></strong>
                                    <span class="request-chats-panel-item-id">#<?php echo $requestId; ?></span>
                                </div>
                                <div class="request-chats-panel-item-meta">
                                    <span class="request-status-badge request-status-<?php echo htmlspecialchars($commercialStatus); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="request-chat-unread-badge"><?php echo $unreadCount; ?> nova(s)</span>
                                    <?php elseif ((int) ($summary['total_messages'] ?? 0) > 0): ?>
                                        <span class="request-status-badge request-status-sem-novas">Sem novas</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($preview !== ''): ?>
                                    <p class="request-chats-panel-item-preview"><?php echo htmlspecialchars($preview); ?></p>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-content request-chats-panel-empty">
                        <i class="fa fa-comments"></i>
                        <p>Não há negociações com chat disponível neste momento.</p>
                        <a href="<?php echo DIRPAGE; ?>requests" class="btn-primary">Ver minhas solicitações</a>
                    </div>
                <?php endif; ?>
            </aside>

            <section class="request-chats-conversation" aria-label="Conversa selecionada">
                <?php if ($hasChatSelected): ?>
                    <?php
                        $request = $selectedRequest;
                        include DIRREQ . 'app/view/dashboard/request_chats/chat_panel.php';
                    ?>
                <?php else: ?>
                    <div class="request-chats-conversation-placeholder">
                        <i class="fa fa-comments"></i>
                        <h3>Selecione uma negociação</h3>
                        <p>Escolha uma conversa no painel para ver mensagens e responder.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
