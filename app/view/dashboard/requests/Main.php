<div class="container dashboard-view requests-dashboard-view"
    id="requestsDashboardRoot"
    data-chat-summaries-feed-url="<?php echo DIRPAGE; ?>dashboard/requestChatSummariesFeed?view=<?php echo urlencode((string) ($requestView ?? '')); ?>">
    <?php
        $currentView = (string) ($requestView ?? '');
        $isAdminScope = ($scope ?? 'user') === 'admin';
        $canManageAllRequests = !empty($canManageAllRequests);
        $actionFilter = (string) ($actionFilter ?? 'all');
        $page = max(1, (int) ($page ?? 1));
        $totalPages = max(1, (int) ($totalPages ?? 1));
        $requestsTotal = (int) ($requestsTotal ?? count($requests ?? []));
        $showManagementColumns = ($scope ?? 'user') === 'admin'
            || (($scope ?? 'user') === 'owner' && $currentView !== 'sent');
        $showSentContextColumns = !$showManagementColumns;

        function requestsPageUrl(int $targetPage, string $requestView, string $actionFilter): string {
            $params = [];
            if ($requestView !== '') {
                $params['view'] = $requestView;
            }
            if ($actionFilter !== 'all') {
                $params['action_filter'] = $actionFilter;
            }
            if ($targetPage > 1) {
                $params['page'] = $targetPage;
            }
            $query = http_build_query($params);
            return DIRPAGE . 'requests' . ($query !== '' ? ('?' . $query) : '');
        }
    ?>

    <section class="dashboard-view-hero compact">
        <div>
            <span class="dashboard-hero-kicker">As suas solicitações</span>
            <h1><?php echo htmlspecialchars($pageTitle ?? 'Minhas Solicitações'); ?></h1>
            <p><?php echo htmlspecialchars($pageDescription ?? 'Acompanhe o estado de cada contacto feito aos imóveis.'); ?></p>
            <p>
                <a href="<?php echo DIRPAGE; ?>dashboard/requestChats" class="btn-secondary">Ver todas as conversas</a>
            </p>
        </div>
    </section>

    <div class="requests-legend">
        <div class="legend-title">
            <div class="legend-title-content">
                <i class="fa fa-info-circle"></i>
                <strong>Guia de estados</strong>
            </div>
            <button class="legend-toggle" id="legendToggle" aria-expanded="true" aria-label="Mostrar/esconder guia de estados">
                <i class="fa fa-chevron-up"></i>
            </button>
        </div>
        <div class="legend-grid" id="legendGrid">
            <div class="legend-item">
                <span class="legend-badge request-status-em_contacto"></span>
                <div>
                    <strong>Em contacto</strong>
                    <p>Solicitação criada e em negociação ativa entre solicitante e proprietário, com chat e ações disponíveis.</p>
                </div>
            </div>
            <div class="legend-item">
                <span class="legend-badge request-status-fechado_ganho"></span>
                <div>
                    <strong>Negócio fechado</strong>
                    <p>O proprietário marcou o negócio como concluído e aguarda declaração de pagamento pelo solicitante.</p>
                </div>
            </div>
            <div class="legend-item">
                <span class="legend-badge request-status-cancelado"></span>
                <div>
                    <strong>Cancelado</strong>
                    <p>A negociação foi encerrada sem fecho comercial, podendo ainda entrar em disputa dentro da janela permitida.</p>
                </div>
            </div>
            <div class="legend-item">
                <span class="legend-badge request-status-expirado"></span>
                <div>
                    <strong>Expirado</strong>
                    <p>O sistema encerrou automaticamente a solicitação após 15 dias sem evolução ou desfecho.</p>
                </div>
            </div>
            <div class="legend-item">
                <span class="legend-badge request-status-em_disputa"></span>
                <div>
                    <strong>Em disputa</strong>
                    <p>Caso enviado para análise da gestão, com base em observações e evidências, dentro de até 30 dias após o fecho ou cancelamento.</p>
                </div>
            </div>
            <div class="legend-item">
                <span class="legend-badge request-status-declarado_comprador"></span>
                <div>
                    <strong>Pagamento: Declarado</strong>
                    <p>Solicitante declarou pagamento realizado. Proprietário pode confirmar recebimento para consolidar o fecho e gerar comissão.</p>
                </div>
            </div>
            <div class="legend-item">
                <span class="legend-badge request-status-confirmado_proprietario"></span>
                <div>
                    <strong>Pagamento: Confirmado</strong>
                    <p>Proprietário confirmou recebimento do pagamento. Fecho consolidado e comissão gerada automaticamente.</p>
                </div>
            </div>
            <div class="legend-item">
                <span class="legend-badge request-status-contestado"></span>
                <div>
                    <strong>Pagamento: Contestado</strong>
                    <p>Proprietário abriu disputa sobre o pagamento, caso movido para análise da gestão.</p>
                </div>
            </div>
        </div>
    </div>

    <?php if (($scope ?? 'user') === 'owner'): ?>
        <?php $currentView = $currentView !== '' ? $currentView : 'received'; ?>
        <div class="requests-scope-navigation">
            <div class="scope-toggle">
                <a href="<?php echo DIRPAGE; ?>requests?view=received"
                   class="scope-button <?php echo $currentView === 'received' ? 'active' : ''; ?>"
                   aria-current="<?php echo $currentView === 'received' ? 'page' : 'false'; ?>">
                    <i class="fa fa-inbox"></i>
                    <span>Solicitações Recebidas</span>
                    <small>Interessados nos seus imóveis</small>
                </a>
                <a href="<?php echo DIRPAGE; ?>requests?view=sent"
                   class="scope-button <?php echo $currentView === 'sent' ? 'active' : ''; ?>"
                   aria-current="<?php echo $currentView === 'sent' ? 'page' : 'false'; ?>">
                    <i class="fa fa-paper-plane"></i>
                    <span>Solicitações Enviadas</span>
                    <small>Solicitações feitas por você</small>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="dashboard-module-card">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Operação</span>
                <h3><?php echo $showManagementColumns ? 'Todas as Solicitações' : ($currentView === 'sent' ? 'Enviadas por Você' : 'Recebidas'); ?></h3>
            </div>
        </div>

        <div class="filter-toolbar filter-toolbar-sticky filter-toolbar-dashboard requests-filter-toolbar" role="search">
            <div class="filter-toolbar-inline-fields">
                <div class="filter-toolbar-field">
                    <label class="filter-toolbar-field-label" for="statusFilter">Estado da solicitação</label>
                    <select id="statusFilter" class="request-history-filter-select filter-toolbar-select">
                        <option value="all">Todos</option>
                        <option value="em_contacto">Em contacto</option>
                        <option value="fechado_ganho">Negócio fechado</option>
                        <option value="cancelado">Cancelado</option>
                        <option value="expirado">Expirado</option>
                        <option value="em_disputa">Em disputa</option>
                    </select>
                </div>
                <div class="filter-toolbar-field">
                    <label class="filter-toolbar-field-label" for="propertyStatusFilter">Estado do imóvel</label>
                    <select id="propertyStatusFilter" class="request-history-filter-select filter-toolbar-select">
                        <option value="all">Todos</option>
                        <option value="disponivel">Disponível</option>
                        <option value="vendido">Vendido</option>
                        <option value="alugado">Alugado</option>
                        <option value="pendente">Pendente</option>
                        <option value="em_analise">Em análise</option>
                        <option value="rejeitado">Rejeitado</option>
                    </select>
                </div>
                <div class="filter-toolbar-field">
                    <label class="filter-toolbar-field-label" for="paymentStatusFilter">Estado do pagamento</label>
                    <select id="paymentStatusFilter" class="request-history-filter-select filter-toolbar-select">
                        <option value="all">Todos</option>
                        <option value="pendente">Pendente</option>
                        <option value="declarado_comprador">Declarado pelo comprador</option>
                        <option value="confirmado_proprietario">Confirmado pelo proprietário</option>
                        <option value="contestado">Contestado</option>
                    </select>
                </div>
            </div>
        </div>
        <small id="requestFilterFeedback" class="request-filter-feedback requests-filter-feedback-below" aria-live="polite"></small>

        <div class="dashboard-table-wrap">
            <table class="requests-table">
                <thead>
                    <tr>
                        <?php if ($showManagementColumns): ?>
                            <th class="col-requester">Solicitante</th>
                        <?php endif; ?>
                        <th class="col-property">Imóvel</th>
                        <?php if ($showSentContextColumns): ?>
                            <th class="col-owner">Proprietário</th>
                        <?php endif; ?>
                        <th class="col-type">Tipo</th>
                        <?php if ($showSentContextColumns): ?>
                            <th class="col-term">Modalidade</th>
                        <?php endif; ?>
                        <th class="col-status">Estado</th>
                        <th class="col-date">Data</th>
                        <th class="col-actions">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $request): ?>
                            <?php
                                $status = (string) ($request['status'] ?? 'pendente');
                                $closingConfirmationStatus = $request['closing_confirmation_status'] ?? null;
                                $paymentConfirmationStatus = isset($request['payment_confirmation_status']) ? (string) $request['payment_confirmation_status'] : null;
                                $commercialStatus = (string) ($request['commercial_status'] ?? $status);
                                $propertyStatus = (string) ($request['property_status'] ?? 'disponivel');
                                $disputeStatus = (string) ($request['dispute_status'] ?? \App\model\Request::DISPUTE_STATUS_NONE);
                                $disputeWindowOpen = App\model\Request::isDisputeWindowOpen($request);
                                $statusLabel = App\model\Request::statusLabel($commercialStatus, is_string($closingConfirmationStatus) ? $closingConfirmationStatus : null);
                                $statusClass = 'request-status-' . strtolower($commercialStatus);
                                $propertyStatusMap = [
                                    'disponivel' => 'Disponível',
                                    'vendido' => 'Vendido',
                                    'alugado' => 'Alugado',
                                    'pendente' => 'Pendente',
                                    'em_analise' => 'Em análise',
                                    'rejeitado' => 'Rejeitado',
                                ];
                                $propertyStatusLabel = $propertyStatusMap[$propertyStatus] ?? ucfirst(str_replace('_', ' ', $propertyStatus));
                                $propertyCommerciallyClosed = in_array($propertyStatus, ['vendido', 'alugado'], true);
                            ?>
                            <tr class="request-row request-row-<?php echo strtolower($status); ?>"
                                data-status="<?php echo strtolower($status); ?>"
                                data-property-status="<?php echo htmlspecialchars(strtolower($propertyStatus)); ?>"
                                data-payment-status="<?php echo htmlspecialchars(strtolower((string) ($paymentConfirmationStatus ?: 'none'))); ?>"
                                data-request-id="<?php echo (int) ($request['id'] ?? 0); ?>">
                                <?php $requestId = (int) ($request['id'] ?? 0); ?>
                                <?php if ($showManagementColumns): ?>
                                    <td class="col-requester" data-label="Solicitante">
                                        <div class="cell-content">
                                            <?php
                                                $requesterHandle = htmlspecialchars(Src\classes\UserDisplay::publicHandleFromRow($request, 'requester_username', 'requester_name'));
                                            ?>
                                            <strong><?php echo !empty($request['requester_id']) ? '<a href="' . DIRPAGE . 'property/owner/' . (int)$request['requester_id'] . '" class="table-name-link">' . $requesterHandle . '</a>' : $requesterHandle; ?></strong>
                                            <?php if ($canManageAllRequests): ?>
                                                <small><?php echo htmlspecialchars($request['requester_email'] ?? '-'); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>

                                <td class="col-property" data-label="Imóvel"><a href="<?php echo DIRPAGE; ?>property/<?php echo (int)($request['property_id'] ?? 0); ?>" class="table-name-link"><?php echo htmlspecialchars((string) ($request['title'] ?? '')); ?></a></td>

                                <?php if ($showSentContextColumns): ?>
                                    <?php
                                        $ownerHandle = htmlspecialchars(Src\classes\UserDisplay::publicHandleFromRow($request, 'owner_username', 'owner_name', 'Não informado'));
                                    ?>
                                    <td class="col-owner" data-label="Proprietário"><?php echo !empty($request['owner_id']) ? '<a href="' . DIRPAGE . 'property/owner/' . (int)$request['owner_id'] . '" class="table-name-link">' . $ownerHandle . '</a>' : $ownerHandle; ?></td>
                                <?php endif; ?>

                                <td class="col-type" data-label="Tipo"><?php echo htmlspecialchars((string) ($request['type'] ?? '')); ?></td>

                                <?php if ($showSentContextColumns): ?>
                                    <td class="col-term" data-label="Modalidade">
                                        <?php
                                            $paymentTerm = (string) ($request['payment_term'] ?? '');
                                            $termLabel = [
                                                'mensal' => 'Mensal',
                                                'trimestral' => 'Trimestral',
                                                'semestral' => 'Semestral',
                                                'anual' => 'Anual',
                                            ][$paymentTerm] ?? '-';
                                            echo htmlspecialchars($termLabel);
                                        ?>
                                    </td>
                                <?php endif; ?>

                                <td class="col-status" data-label="Estado">
                                    <div class="status-cell">
                                        <span class="request-status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                                        <?php if ($status === 'fechado_ganho'): ?>
                                            <span class="request-status-badge request-status-<?php echo htmlspecialchars((string) ($paymentConfirmationStatus ?: 'none')); ?>" style="margin-top:4px;">
                                                Pagamento: <?php echo htmlspecialchars(App\model\Request::paymentConfirmationLabel($paymentConfirmationStatus)); ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="request-status-badge request-status-none" style="margin-top:4px;">
                                            Imóvel: <?php echo htmlspecialchars($propertyStatusLabel); ?>
                                        </span>
                                        <?php if (is_string($closingConfirmationStatus) && $closingConfirmationStatus === 'pendente'): ?>
                                            <span class="status-indicator status-attention">
                                                <i class="fa fa-exclamation-circle"></i> Confirmação pendente
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($disputeStatus !== \App\model\Request::DISPUTE_STATUS_NONE): ?>
                                            <?php
                                                $disputeLabels = [
                                                    'aberta'                => 'Disputa aberta',
                                                    'em_analise'            => 'Disputa em análise',
                                                    'julgada_procedente'    => 'Disputa: procedente',
                                                    'julgada_improcedente'  => 'Disputa: improcedente',
                                                ];
                                                $disputeLabel = $disputeLabels[$disputeStatus] ?? ucfirst(str_replace('_', ' ', $disputeStatus));
                                            ?>
                                            <span class="request-status-badge request-status-em_disputa dispute-status-chip">
                                                <i class="fa fa-gavel"></i> <?php echo htmlspecialchars($disputeLabel); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td class="col-date" data-label="Data"><small><?php echo date('d/m/Y', strtotime((string) ($request['created_at'] ?? 'now'))); ?></small></td>

                                <td class="col-actions" data-label="Ações">
                                    <?php
                                        $chatSummary = is_array($requestChatSummaries ?? null) && isset($requestChatSummaries[$requestId])
                                            ? $requestChatSummaries[$requestId]
                                            : null;
                                        $chatCount = (int) ($chatSummary['total_messages'] ?? 0);
                                        $chatUnread = (int) ($chatSummary['unread_count'] ?? 0);
                                        $chatPreview = trim((string) ($chatSummary['last_message_text'] ?? ''));
                                        if ($chatPreview !== '' && function_exists('mb_strimwidth')) {
                                            $chatPreview = mb_strimwidth($chatPreview, 0, 70, '...');
                                        } elseif ($chatPreview !== '') {
                                            $chatPreview = substr($chatPreview, 0, 70) . (strlen($chatPreview) > 70 ? '...' : '');
                                        }
                                    ?>
                                    <?php if ($propertyCommerciallyClosed): ?>
                                        <span class="request-action-empty">Negociação encerrada: imóvel <?php echo htmlspecialchars(strtolower($propertyStatusLabel)); ?>.</span>
                                    <?php endif; ?>
                                    <div class="request-chat-summary" data-chat-summary-for-request="<?php echo $requestId; ?>" <?php echo $chatCount > 0 ? '' : 'hidden'; ?>>
                                        <strong>
                                            <span class="request-chat-summary-count"><?php echo $chatCount; ?></span> mensagem(ns)
                                            <span class="request-chat-unread-badge" <?php echo $chatUnread > 0 ? '' : 'hidden'; ?>><?php echo $chatUnread; ?> nova(s)</span>
                                        </strong>
                                        <small class="request-chat-summary-preview" <?php echo $chatPreview !== '' ? '' : 'hidden'; ?>><?php echo htmlspecialchars($chatPreview); ?></small>
                                    </div>
                                    <?php if ($showManagementColumns && !$propertyCommerciallyClosed): ?>
                                        <?php
                                            $actionOptions = App\model\Request::managementActionsFor($status, $canManageAllRequests, $disputeWindowOpen);
                                            $ownerPaymentActions = !$canManageAllRequests
                                                ? App\model\Request::ownerPaymentActionsFor(
                                                    $status,
                                                    is_string($closingConfirmationStatus) ? $closingConfirmationStatus : null,
                                                    is_string($paymentConfirmationStatus) ? $paymentConfirmationStatus : null,
                                                    $disputeWindowOpen
                                                )
                                                : [];
                                        ?>

                                        <?php if (!empty($actionOptions)): ?>
                                            <form action="<?php echo DIRPAGE; ?>request/updateStatus/<?php echo (int) ($request['id'] ?? 0); ?>" method="POST"
                                                  class="request-action-select-form request-action-select-form--details"
                                                  enctype="multipart/form-data"
                                                                                                    data-note-required-actions="em_disputa,cancelado">
                                                <?php echo Src\classes\ClassCsrf::field(); ?>
                                                <select name="status" class="request-action-select" required>
                                                    <option value="">Selecionar ação</option>
                                                    <?php foreach ($actionOptions as $optionValue => $optionLabel): ?>
                                                        <option value="<?php echo htmlspecialchars($optionValue); ?>"><?php echo htmlspecialchars($optionLabel); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="request-action-extra-fields" hidden>
                                                    <textarea name="action_note" class="request-action-note" placeholder="Observação (obrigatória para esta ação)..." rows="3" maxlength="2000"></textarea>
                                                    <label class="request-action-upload">
                                                        <span>Evidência (opcional)</span>
                                                        <input type="file" name="action_image" class="js-request-attachment-input" accept="image/*">
                                                        <small class="request-attachment-feedback"></small>
                                                    </label>
                                                </div>
                                                <div class="request-form-buttons">
                                                    <a class="btn-secondary dispute-detail-link request-chat-link" href="<?php echo DIRPAGE; ?>dashboard/requestChat/<?php echo (int) ($request['id'] ?? 0); ?>"><i class="fa fa-comments" aria-hidden="true"></i> Chat</a>
                                                    <button type="submit" class="btn-primary request-action-apply">Aplicar</button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <div class="request-form-buttons">
                                                <a class="btn-secondary dispute-detail-link request-chat-link" href="<?php echo DIRPAGE; ?>dashboard/requestChat/<?php echo (int) ($request['id'] ?? 0); ?>"><i class="fa fa-comments" aria-hidden="true"></i> Chat</a>
                                                <span class="request-action-empty">Sem ações disponíveis</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($ownerPaymentActions)): ?>
                                            <form method="POST"
                                                  class="request-action-select-form request-action-select-form--details user-action-select-form"
                                                  enctype="multipart/form-data"
                                                  data-note-required-actions="contest_payment"
                                                  data-confirm-payment-receipt-url="<?php echo DIRPAGE; ?>request/confirmPaymentReceipt/<?php echo (int) ($request['id'] ?? 0); ?>"
                                                  data-contest-payment-url="<?php echo DIRPAGE; ?>request/contestPayment/<?php echo (int) ($request['id'] ?? 0); ?>">
                                                <?php echo Src\classes\ClassCsrf::field(); ?>
                                                <select name="user_action" class="request-action-select" required>
                                                    <option value="">Selecionar ação de pagamento</option>
                                                    <?php foreach ($ownerPaymentActions as $optionValue => $optionLabel): ?>
                                                        <option value="<?php echo htmlspecialchars($optionValue); ?>"><?php echo htmlspecialchars($optionLabel); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="request-action-extra-fields" hidden>
                                                    <textarea name="action_note" class="request-action-note" placeholder="Observação (obrigatória para esta ação)..." rows="3" maxlength="2000"></textarea>
                                                    <label class="request-action-upload">
                                                        <span>Evidência (opcional)</span>
                                                        <input type="file" name="action_image" class="js-request-attachment-input" accept="image/*">
                                                        <small class="request-attachment-feedback"></small>
                                                    </label>
                                                </div>
                                                <div class="request-form-buttons">
                                                    <a class="btn-secondary dispute-detail-link request-chat-link" href="<?php echo DIRPAGE; ?>dashboard/requestChat/<?php echo (int) ($request['id'] ?? 0); ?>"><i class="fa fa-comments" aria-hidden="true"></i> Chat</a>
                                                    <button type="submit" class="btn-primary request-action-apply">Aplicar</button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    <?php elseif (!$propertyCommerciallyClosed): ?>
                                        <?php
                                            $userActionOptions = App\model\Request::requesterActionsFor(
                                                $status,
                                                is_string($closingConfirmationStatus) ? $closingConfirmationStatus : null,
                                                is_string($paymentConfirmationStatus) ? $paymentConfirmationStatus : null,
                                                $disputeWindowOpen
                                            );
                                            $noteRequiredActions = [];
                                            if (isset($userActionOptions['contest_closing'])) {
                                                $noteRequiredActions[] = 'contest_closing';
                                            }
                                            if (isset($userActionOptions['cancel'])) {
                                                $noteRequiredActions[] = 'cancel';
                                            }
                                            if (isset($userActionOptions['open_dispute'])) {
                                                $noteRequiredActions[] = 'open_dispute';
                                            }
                                            $noteRequiredActionsAttr = implode(',', $noteRequiredActions);
                                        ?>
                                        <?php if (!empty($userActionOptions)): ?>
                                            <form method="POST"
                                                  class="request-action-select-form request-action-select-form--details user-action-select-form"
                                                  enctype="multipart/form-data"
                                                  data-note-required-actions="<?php echo htmlspecialchars($noteRequiredActionsAttr); ?>"
                                                  data-proof-required-actions="confirm_closing"
                                                  data-confirm-url="<?php echo DIRPAGE; ?>request/confirmClosing/<?php echo (int) ($request['id'] ?? 0); ?>"
                                                  data-contest-url="<?php echo DIRPAGE; ?>request/contestClosing/<?php echo (int) ($request['id'] ?? 0); ?>"
                                                  <?php if (isset($userActionOptions['open_dispute'])): ?>data-open-dispute-url="<?php echo DIRPAGE; ?>request/openDispute/<?php echo (int) ($request['id'] ?? 0); ?>"<?php endif; ?>
                                                  data-cancel-url="<?php echo DIRPAGE; ?>request/cancel/<?php echo (int) ($request['id'] ?? 0); ?>">
                                                <?php echo Src\classes\ClassCsrf::field(); ?>
                                                <select name="user_action" class="request-action-select" required>
                                                    <option value="">Selecionar ação</option>
                                                    <?php foreach ($userActionOptions as $optionValue => $optionLabel): ?>
                                                        <option value="<?php echo htmlspecialchars($optionValue); ?>"><?php echo htmlspecialchars($optionLabel); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="request-action-extra-fields" hidden>
                                                    <textarea name="action_note" class="request-action-note" placeholder="Observação (obrigatória para esta ação)..." rows="3" maxlength="2000"></textarea>
                                                    <label class="request-action-upload">
                                                        <span class="request-action-upload-label">Evidência (opcional)</span>
                                                        <input type="file" name="action_image" class="js-request-attachment-input" accept="image/*">
                                                        <small class="request-attachment-feedback"></small>
                                                    </label>
                                                </div>
                                                <div class="request-form-buttons">
                                                    <a class="btn-secondary dispute-detail-link request-chat-link" href="<?php echo DIRPAGE; ?>dashboard/requestChat/<?php echo (int) ($request['id'] ?? 0); ?>"><i class="fa fa-comments" aria-hidden="true"></i> Chat</a>
                                                    <button type="submit" class="btn-primary request-action-apply">Aplicar</button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <div class="request-form-buttons">
                                                <a class="btn-secondary dispute-detail-link request-chat-link" href="<?php echo DIRPAGE; ?>dashboard/requestChat/<?php echo (int) ($request['id'] ?? 0); ?>"><i class="fa fa-comments" aria-hidden="true"></i> Chat</a>
                                                <span class="request-action-empty">Sem ações disponíveis</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $showManagementColumns ? '7' : '8'; ?>" class="empty-state">
                                <div class="empty-state-content">
                                    <i class="fa fa-inbox"></i>
                                    <p>Nenhuma solicitação encontrada</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($requestsTotal > 0): ?>
            <p class="dashboard-pagination-copy" style="padding-top:10px;">
                A mostrar <?php echo count($requests ?? []); ?> de <?php echo $requestsTotal; ?>.
            </p>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <div class="dashboard-pagination-wrap dashboard-pagination-wrap-start">
                <?php if ($page > 1): ?>
                    <a href="<?php echo requestsPageUrl($page - 1, $currentView, $actionFilter); ?>" class="btn-secondary">&larr; Anterior</a>
                <?php endif; ?>
                <span class="dashboard-pagination-copy">Página <?php echo $page; ?> de <?php echo $totalPages; ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="<?php echo requestsPageUrl($page + 1, $currentView, $actionFilter); ?>" class="btn-secondary">Próxima &rarr;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
