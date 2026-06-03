<div class="container dashboard-view">
    <?php
        $canManageSuperAdminTabs = !empty($canManageSuperAdminTabs)
            || Src\classes\ClassAccess::isSuperAdmin($user ?? null);
        $page = max(1, (int) ($page ?? 1));
        $totalPages = max(1, (int) ($totalPages ?? 1));
        $allowedTabs = ['fila', 'pendentes', 'confianca', 'acessos', 'equipa'];
        $activeTab = (string) ($activeTab ?? ($_GET['tab'] ?? 'pendentes'));
        if (!in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'pendentes';
        }
        if ($activeTab === 'acessos' && !$canManageSuperAdminTabs) {
            $activeTab = 'pendentes';
        }
        if ($activeTab === 'equipa' && !$canManageSuperAdminTabs) {
            $activeTab = 'pendentes';
        }

        $accessStatusFilter = (string) ($accessStatusFilter ?? 'all');
        $accessSearch = trim((string) ($accessSearch ?? ''));
        $manageableUsersPage = max(1, (int) ($manageableUsersPage ?? 1));
        $manageableUsersTotalPages = max(1, (int) ($manageableUsersTotalPages ?? 1));
        $manageableUsersTotal = (int) ($manageableUsersTotal ?? count($manageableUsers ?? []));
        $administrativeUsers = is_array($administrativeUsers ?? null) ? $administrativeUsers : [];
        $adminRoleOptions = is_array($adminRoleOptions ?? null) ? $adminRoleOptions : [];

        function moderateUsersTabUrl(string $tab, int $targetPage = 1): string {
            $params = ['tab' => $tab];
            if ($tab === 'pendentes' && $targetPage > 1) {
                $params['page'] = $targetPage;
            }
            return DIRPAGE . 'dashboard/moderateUsers?' . http_build_query($params);
        }

        function moderateUsersAccessUrl(string $statusFilter, string $search = '', int $targetPage = 1): string {
            $params = ['tab' => 'acessos'];
            if ($statusFilter !== 'all') {
                $params['access_status'] = $statusFilter;
            }
            if (trim($search) !== '') {
                $params['access_search'] = trim($search);
            }
            if ($targetPage > 1) {
                $params['access_page'] = $targetPage;
            }
            return DIRPAGE . 'dashboard/moderateUsers?' . http_build_query($params);
        }
    ?>
    <section class="dashboard-view-hero compact">
        <div>
            <span class="dashboard-hero-kicker">Moderação</span>
            <h1>Moderação de Usuários</h1>
            <p>Revise documentos, confiança e aprovações pendentes.</p>
        </div>
    </section>

    <?php if (!empty($_GET['error'])): ?>
        <div class="sub-feedback error"><?php echo htmlspecialchars((string) $_GET['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['success'])): ?>
        <div class="sub-feedback success"><?php echo htmlspecialchars((string) $_GET['success']); ?></div>
    <?php endif; ?>

    <div class="dashboard-tab-nav" style="margin-bottom:24px;">
        <a href="<?php echo moderateUsersTabUrl('fila'); ?>" class="dashboard-tab-link <?php echo $activeTab === 'fila' ? 'is-active' : ''; ?>">
            <i class="fa fa-tasks"></i> Fila (<?php echo (int) ($queueSummary['total'] ?? 0); ?>)
        </a>
        <a href="<?php echo moderateUsersTabUrl('pendentes'); ?>" class="dashboard-tab-link <?php echo $activeTab === 'pendentes' ? 'is-active' : ''; ?>">
            <i class="fa fa-users"></i> Utilizadores Pendentes (<?php echo (int) ($pendingUsersTotal ?? 0); ?>)
        </a>
        <a href="<?php echo moderateUsersTabUrl('confianca'); ?>" class="dashboard-tab-link <?php echo $activeTab === 'confianca' ? 'is-active' : ''; ?>">
            <i class="fa fa-shield"></i> Selo de Confiança (<?php echo (int) ($pendingTrustTotal ?? 0); ?>)
        </a>
        <?php if ($canManageSuperAdminTabs): ?>
            <a href="<?php echo moderateUsersAccessUrl($accessStatusFilter, $accessSearch); ?>" class="dashboard-tab-link <?php echo $activeTab === 'acessos' ? 'is-active' : ''; ?>">
                <i class="fa fa-user-lock"></i> Acessos (<?php echo (int) ($manageableUsersTotal ?? 0); ?>)
            </a>
            <a href="<?php echo moderateUsersTabUrl('equipa'); ?>" class="dashboard-tab-link <?php echo $activeTab === 'equipa' ? 'is-active' : ''; ?>">
                <i class="fa fa-users"></i> Equipa Admin (<?php echo count($administrativeUsers); ?>)
            </a>
        <?php endif; ?>
    </div>

    <?php if ($activeTab === 'fila'): ?>
    <div class="dashboard-module-card dashboard-kpi-section">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Prioridades</span>
                <h3>Fila de Trabalho</h3>
            </div>
        </div>

        <div class="dashboard-overview-grid dashboard-overview-grid-tight">
            <div class="kpi-card">
                <div class="kpi-label">Total</div>
                <div class="kpi-value"><?php echo (int) ($queueSummary['total'] ?? 0); ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Pendente</div>
                <div class="kpi-value"><?php echo (int) ($queueSummary['pendente'] ?? 0); ?></div>
            </div>
            <div class="kpi-card kpi-yellow">
                <div class="kpi-label">Urgente</div>
                <div class="kpi-value"><?php echo (int) ($queueSummary['urgente'] ?? 0); ?></div>
            </div>
            <div class="kpi-card kpi-red">
                <div class="kpi-label">Atrasado</div>
                <div class="kpi-value"><?php echo (int) ($queueSummary['atrasado'] ?? 0); ?></div>
            </div>
        </div>

        <div class="dashboard-table-wrap">
        <table class="commissions-table moderation-table moderation-table-queue">
            <thead>
                <tr>
                    <th>Prioridade</th>
                    <th>Tipo</th>
                    <th>Assunto</th>
                    <th>Idade</th>
                    <th>Criado em</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($adminQueue)): ?>
                    <?php foreach ($adminQueue as $item): ?>
                        <tr data-focus-user-id="<?php echo (int) ($user['id'] ?? 0); ?>">
                            <td>
                                <?php if (($item['priority'] ?? '') === 'atrasado'): ?>
                                    <span class="priority-badge priority-badge-atrasado">Atrasado</span>
                                <?php elseif (($item['priority'] ?? '') === 'urgente'): ?>
                                    <span class="priority-badge priority-badge-urgente">Urgente</span>
                                <?php else: ?>
                                    <span class="priority-badge priority-badge-pendente">Pendente</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['title'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['subject'] ?? '-'); ?></td>
                            <td><?php echo (int) ($item['age_days'] ?? 0); ?> dia(s)</td>
                            <td><?php echo !empty($item['created_at']) ? date('d/m/Y H:i', strtotime($item['created_at'])) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Sem itens na fila de trabalho.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($activeTab === 'pendentes'): ?>
    <div class="dashboard-module-card dashboard-kpi-section">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Aprovação</span>
                <h3>Utilizadores Pendentes</h3>
            </div>
        </div>

        <div class="dashboard-table-wrap">
        <table class="commissions-table moderation-table moderation-table-users">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Documento</th>
                    <th>Etapa</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pendingUsers)): ?>
                    <?php foreach ($pendingUsers as $user): ?>
                        <?php
                            $uId = (int) ($user['id'] ?? 0);
                            $cData = $userCompliance[$uId] ?? ['status' => 'missing', 'stage' => 'documental_validacao', 'latest_document' => null];
                            $complianceStatus = (string) ($cData['status'] ?? 'missing');
                            $canFinalApprove = $complianceStatus === 'compliant';
                            $latestDoc = is_array($cData['latest_document'] ?? null) ? $cData['latest_document'] : null;
                            $docFilename = trim((string) ($latestDoc['filename'] ?? $user['document_file'] ?? ''));
                            $pendingDocId = ($latestDoc && (string) ($latestDoc['status'] ?? '') === 'pendente')
                                ? (int) ($latestDoc['id'] ?? 0)
                                : 0;
                            $reviewUrl = $pendingDocId > 0
                                ? DIRPAGE . 'dashboard/reviewDocuments?document=' . $pendingDocId
                                : DIRPAGE . 'dashboard/reviewDocuments';
                        ?>
                        <tr data-focus-user-id="<?php echo $uId; ?>">
                            <td class="moderation-cell-text"><a href="<?php echo DIRPAGE; ?>property/owner/<?php echo (int)($user['id'] ?? 0); ?>" class="table-name-link"><?php echo htmlspecialchars($user['name']); ?></a></td>
                            <td class="moderation-cell-text"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="moderation-cell-text"><?php echo htmlspecialchars($user['phone']); ?></td>
                            <td class="moderation-cell-text"><?php echo htmlspecialchars($user['document_number']); ?></td>
                            <td>
                                <?php if ($canFinalApprove): ?>
                                    <span class="stage-badge stage-badge-ready">Pronto p/ Aprovação Final</span>
                                <?php elseif ($complianceStatus === 'rejected'): ?>
                                    <span class="stage-badge stage-badge-rejected">Documento rejeitado</span>
                                <?php elseif ($complianceStatus === 'pending'): ?>
                                    <span class="stage-badge stage-badge-pending">Documento em revisão</span>
                                <?php elseif ($complianceStatus === 'missing'): ?>
                                    <span class="stage-badge stage-badge-pending">Sem documento</span>
                                <?php else: ?>
                                    <span class="stage-badge stage-badge-pending">Validação documental</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="moderation-actions">
                                    <?php if ($docFilename !== ''): ?>
                                        <a href="<?php echo DIRPAGE; ?>storage/documents/<?php echo htmlspecialchars($docFilename); ?>" target="_blank" rel="noopener" class="btn-secondary">Ver doc.</a>
                                    <?php endif; ?>
                                    <a href="<?php echo htmlspecialchars($reviewUrl); ?>" class="btn-secondary">Revisão</a>
                                    <form action="<?php echo DIRPAGE; ?>dashboard/approveUser/<?php echo $user['id']; ?>" method="POST">
                                        <?php echo $csrfField; ?>
                                        <button type="submit" class="btn-primary" <?php echo !$canFinalApprove ? 'disabled title="Aguardar aprovação documental"' : ''; ?>>Aprovação final</button>
                                    </form>
                                    <form action="<?php echo DIRPAGE; ?>dashboard/rejectUser/<?php echo $user['id']; ?>" method="POST">
                                        <?php echo $csrfField; ?>
                                        <button type="submit" class="btn-secondary" data-confirm="Rejeitar esta conta? O utilizador deixará de poder aceder à plataforma.">Rejeitar conta</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Nenhum usuário pendente.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <?php if ((int) ($pendingUsersTotal ?? 0) > 0): ?>
            <p class="dashboard-pagination-copy" style="padding-top:10px;">
                A mostrar <?php echo count($pendingUsers ?? []); ?> de <?php echo (int) ($pendingUsersTotal ?? 0); ?>.
            </p>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <div class="dashboard-pagination-wrap dashboard-pagination-wrap-start">
                <?php if ($page > 1): ?>
                    <a href="<?php echo moderateUsersTabUrl('pendentes', $page - 1); ?>" class="btn-secondary">&larr; Anterior</a>
                <?php endif; ?>
                <span class="dashboard-pagination-copy">Página <?php echo $page; ?> de <?php echo $totalPages; ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="<?php echo moderateUsersTabUrl('pendentes', $page + 1); ?>" class="btn-secondary">Próxima &rarr;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($activeTab === 'confianca'): ?>
    <div class="dashboard-module-card">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Selo</span>
                <h3>Solicitações de Utilizador de Confiança
                    <?php if (($pendingTrustTotal ?? 0) > 0): ?>
                        <span class="kpi-badge kpi-badge-yellow"><?php echo (int) ($pendingTrustTotal ?? 0); ?></span>
                    <?php endif; ?>
                </h3>
            </div>

        </div>

        <?php if (!empty($pendingTrust)): ?>
        <div class="dashboard-table-wrap">
        <table class="commissions-table moderation-table moderation-table-trust">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Pedido</th>
                    <th>Duração</th>
                    <th>Taxa (Kz)</th>
                    <th>Pagamento</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingTrust as $trustUser): ?>
                    <?php $feePaid = !empty($trustUser['trust_badge_fee_paid']); ?>
                    <tr data-focus-user-id="<?php echo (int) ($trustUser['id'] ?? 0); ?>">
                        <td class="moderation-cell-text">
                            <a href="<?php echo DIRPAGE; ?>property/owner/<?php echo (int) ($trustUser['id'] ?? 0); ?>" class="table-name-link">
                                <?php echo htmlspecialchars($trustUser['name'] ?? '—'); ?>
                            </a>
                        </td>
                        <td class="moderation-cell-text"><?php echo htmlspecialchars($trustUser['email'] ?? '—'); ?></td>
                        <td class="dashboard-inline-note dashboard-cell-nowrap"><?php echo !empty($trustUser['trust_badge_requested_at']) ? date('d/m/Y', strtotime($trustUser['trust_badge_requested_at'])) : '—'; ?></td>
                        <td><?php echo !empty($trustUser['trust_badge_duration_months']) ? ((int) $trustUser['trust_badge_duration_months'] . ' mes(es)') : '—'; ?></td>
                        <td><?php echo number_format((float) ($trustUser['trust_badge_fee_required'] ?? 0), 0, ',', '.'); ?></td>
                        <td>
                            <span class="request-status-badge <?php echo $feePaid ? 'request-status-aceite' : 'request-status-pendente'; ?>">
                                <?php echo $feePaid ? 'Pago' : 'Pendente'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($feePaid): ?>
                                <form action="<?php echo DIRPAGE; ?>dashboard/approveTrustedBadge/<?php echo (int) ($trustUser['id'] ?? 0); ?>" method="POST" class="request-actions">
                                    <?php echo $csrfField; ?>
                                    <button type="submit" class="btn-primary">Aprovar</button>
                                </form>
                            <?php else: ?>
                                <a href="<?php echo DIRPAGE; ?>dashboard/payments?tab=trust&user=<?php echo (int) ($trustUser['id'] ?? 0); ?>" class="dashboard-btn-link">Ver pagamento</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php if (($pendingTrustTotal ?? 0) > 5): ?>
            <p class="dashboard-inline-note" style="padding:10px 20px 16px;">
                A mostrar 5 de <?php echo (int) $pendingTrustTotal; ?>.
                <a href="<?php echo DIRPAGE; ?>dashboard/payments?tab=trust">Ver todos &rarr;</a>
            </p>
        <?php endif; ?>
        <?php else: ?>
            <p class="dashboard-empty-copy">Nenhuma solicitação de selo pendente.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($activeTab === 'acessos' && $canManageSuperAdminTabs): ?>
    <div class="dashboard-module-card dashboard-kpi-section">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Administração</span>
                <h3>Gestão de Acessos de Utilizadores</h3>
            </div>
        </div>

        <div class="account-state-structure account-state-structure--admin" style="margin: 0 0 1rem;">
            <p class="account-state-structure-intro" style="margin:0;">
                <strong>Conta</strong> (<code>users.status</code>): <em>Pendente</em> · <em>Ativo</em> · <em>Rejeitado</em> — ciclo de moderação do registo.
                <strong>Suspenso</strong> (<code>suspended_until</code>): bloqueio de acesso, sem alterar o status da conta.
                Não confundir <em>Rejeitado</em> com suspensão.
            </p>
        </div>

        <form method="GET" action="<?php echo DIRPAGE; ?>dashboard/moderateUsers" class="filter-toolbar-form" style="padding: 0 0 12px;">
            <input type="hidden" name="tab" value="acessos">
            <div class="filter-toolbar filter-toolbar-sticky filter-toolbar-dashboard">
                <label class="filter-toolbar-field filter-toolbar-field-grow">
                    <span class="filter-toolbar-field-label">Pesquisa rápida</span>
                    <span class="filter-toolbar-input-wrap">
                        <i class="fa fa-search filter-toolbar-input-icon" aria-hidden="true"></i>
                        <input id="accessSearch"
                               name="access_search"
                               type="search"
                               class="filter-toolbar-input"
                               value="<?php echo htmlspecialchars($accessSearch); ?>"
                               placeholder="Nome, @username, email, telefone ou documento"
                               autocomplete="off">
                    </span>
                </label>
                <div class="filter-toolbar-field">
                    <label class="filter-toolbar-field-label" for="accessStatusFilter">Estado</label>
                    <select id="accessStatusFilter" name="access_status" class="request-history-filter-select filter-toolbar-select">
                        <option value="all" <?php echo $accessStatusFilter === 'all' ? 'selected' : ''; ?>>Todos</option>
                        <option value="ativo" <?php echo $accessStatusFilter === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="suspenso" <?php echo $accessStatusFilter === 'suspenso' ? 'selected' : ''; ?>>Suspenso</option>
                        <option value="rejeitado" <?php echo $accessStatusFilter === 'rejeitado' ? 'selected' : ''; ?>>Rejeitado</option>
                        <option value="pendente" <?php echo $accessStatusFilter === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                    </select>
                </div>
                <div class="filter-toolbar-actions">
                    <button type="submit" class="btn-primary filter-toolbar-submit"><i class="fa fa-search" aria-hidden="true"></i><span>Filtrar</span></button>
                    <a href="<?php echo moderateUsersTabUrl('acessos'); ?>" class="btn-secondary">Limpar</a>
                </div>
            </div>
        </form>

        <div class="dashboard-table-wrap">
        <table class="commissions-table moderation-table moderation-table-users">
            <thead>
                <tr>
                    <th>Utilizador</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Estado</th>
                    <th>Registado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($manageableUsers)): ?>
                    <?php foreach ($manageableUsers as $managedUser): ?>
                        <?php
                            $managedStatus = (string) ($managedUser['status'] ?? 'pendente');
                            $suspendedUntil = !empty($managedUser['suspended_until']) ? strtotime((string) $managedUser['suspended_until']) : null;
                            $isSuspended = $suspendedUntil !== false && $suspendedUntil > time();
                        ?>
                        <tr>
                            <td class="moderation-cell-text">
                                <?php
                                    $managedHandle = htmlspecialchars(Src\classes\UserDisplay::publicHandleFromRow($managedUser, 'username', 'name', '–'));
                                ?>
                                <a href="<?php echo DIRPAGE; ?>property/owner/<?php echo (int) ($managedUser['id'] ?? 0); ?>" class="table-name-link"><?php echo $managedHandle; ?></a>
                                <?php if (!empty($managedUser['name']) && !empty($managedUser['username'])): ?>
                                    <br><small class="dashboard-inline-note"><?php echo htmlspecialchars((string) $managedUser['name']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="moderation-cell-text"><?php echo htmlspecialchars((string) ($managedUser['email'] ?? '-')); ?></td>
                            <td class="moderation-cell-text"><?php echo htmlspecialchars((string) ($managedUser['phone'] ?? '-')); ?></td>
                            <td>
                                <div class="account-state-badge-stack">
                                    <?php foreach (Src\classes\UserAccountState::adminRowBadge($managedUser) as $badge): ?>
                                        <?php
                                            $badgeClass = match ($badge['tone'] ?? 'muted') {
                                                'success' => 'request-status-aceite',
                                                'danger' => 'request-status-cancelado',
                                                'warning' => 'request-status-pendente',
                                                default => 'request-status-pendente',
                                            };
                                        ?>
                                        <span class="request-status-badge <?php echo $badgeClass; ?>" title="<?php echo htmlspecialchars((string) ($badge['title'] ?? '')); ?>">
                                            <?php echo htmlspecialchars((string) ($badge['label'] ?? '')); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td><?php echo !empty($managedUser['created_at']) ? date('d/m/Y', strtotime((string) $managedUser['created_at'])) : '-'; ?></td>
                            <td>
                                <div class="moderation-actions">
                                    <?php if ($isSuspended): ?>
                                        <form action="<?php echo DIRPAGE; ?>dashboard/unsuspendUserAccess/<?php echo (int) ($managedUser['id'] ?? 0); ?>" method="POST" class="request-actions">
                                            <?php echo $csrfField; ?>
                                            <button type="submit" class="btn-primary" data-confirm="Levantar a suspensão deste utilizador?">Levantar suspensão</button>
                                        </form>
                                    <?php elseif ($managedStatus === 'ativo'): ?>
                                        <form action="<?php echo DIRPAGE; ?>dashboard/suspendUserAccess/<?php echo (int) ($managedUser['id'] ?? 0); ?>" method="POST" class="request-actions" style="display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                            <?php echo $csrfField; ?>
                                            <label style="display:flex;align-items:center;gap:6px;margin:0;">
                                                <span style="font-size:12px;">Suspender por</span>
                                                <input type="number" name="suspend_days" min="1" max="365" value="7" style="width:70px;padding:6px 8px;font-size:13px;" required>
                                                <span style="font-size:12px;">dias</span>
                                            </label>
                                            <button type="submit" class="btn-secondary" data-confirm="Suspender o acesso deste utilizador?">Suspender</button>
                                        </form>
                                        <form action="<?php echo DIRPAGE; ?>dashboard/blockUserAccess/<?php echo (int) ($managedUser['id'] ?? 0); ?>" method="POST" class="request-actions">
                                            <?php echo $csrfField; ?>
                                            <button type="submit" class="btn-secondary" data-confirm="Suspender o acesso por um período longo? O estado «ativo» mantém-se.">Suspender acesso (longo)</button>
                                        </form>
                                    <?php elseif (in_array($managedStatus, ['pendente', 'rejeitado'], true)): ?>
                                        <form action="<?php echo DIRPAGE; ?>dashboard/blockUserAccess/<?php echo (int) ($managedUser['id'] ?? 0); ?>" method="POST" class="request-actions">
                                            <?php echo $csrfField; ?>
                                            <button type="submit" class="btn-secondary" data-confirm="Suspender o acesso? O estado da conta (<?php echo htmlspecialchars($managedStatus); ?>) não será alterado.">Suspender acesso</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Nenhum utilizador encontrado para os filtros selecionados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <?php if ($manageableUsersTotal > 0): ?>
            <p class="dashboard-pagination-copy" style="padding-top:10px;">
                A mostrar <?php echo count($manageableUsers ?? []); ?> de <?php echo (int) $manageableUsersTotal; ?>.
            </p>
        <?php endif; ?>

        <?php if ($manageableUsersTotalPages > 1): ?>
            <div class="dashboard-pagination-wrap dashboard-pagination-wrap-start">
                <?php if ($manageableUsersPage > 1): ?>
                    <a href="<?php echo moderateUsersAccessUrl($accessStatusFilter, $accessSearch, $manageableUsersPage - 1); ?>" class="btn-secondary">&larr; Anterior</a>
                <?php endif; ?>
                <span class="dashboard-pagination-copy">Página <?php echo $manageableUsersPage; ?> de <?php echo $manageableUsersTotalPages; ?></span>
                <?php if ($manageableUsersPage < $manageableUsersTotalPages): ?>
                    <a href="<?php echo moderateUsersAccessUrl($accessStatusFilter, $accessSearch, $manageableUsersPage + 1); ?>" class="btn-secondary">Próxima &rarr;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($activeTab === 'equipa' && $canManageSuperAdminTabs): ?>
    <div class="dashboard-module-card dashboard-kpi-section">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Administração Principal</span>
                <h3>Gestão de Papéis Administrativos</h3>
            </div>
        </div>

        <div class="dashboard-table-wrap">
        <table class="commissions-table moderation-table moderation-table-users">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Estado</th>
                    <th>Papel atual</th>
                    <th>Alterar papel</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($administrativeUsers)): ?>
                    <?php foreach ($administrativeUsers as $adminMember): ?>
                        <?php $memberRole = (string) ($adminMember['role'] ?? 'utilizador'); ?>
                        <tr>
                            <td class="moderation-cell-text"><a href="<?php echo DIRPAGE; ?>property/owner/<?php echo (int) ($adminMember['id'] ?? 0); ?>" class="table-name-link"><?php echo htmlspecialchars((string) ($adminMember['name'] ?? '-')); ?></a></td>
                            <td class="moderation-cell-text"><?php echo htmlspecialchars((string) ($adminMember['email'] ?? '-')); ?></td>
                            <td class="moderation-cell-text"><?php echo htmlspecialchars((string) ($adminMember['phone'] ?? '-')); ?></td>
                            <td>
                                <?php $memberStatus = (string) ($adminMember['status'] ?? 'pendente'); ?>
                                <?php if ($memberStatus === 'ativo'): ?>
                                    <span class="request-status-badge request-status-aceite">Ativo</span>
                                <?php elseif ($memberStatus === 'rejeitado'): ?>
                                    <span class="request-status-badge request-status-cancelado">Bloqueado</span>
                                <?php else: ?>
                                    <span class="request-status-badge request-status-pendente">Pendente</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(Src\classes\ClassAccess::roleLabel($adminMember)); ?></td>
                            <td>
                                <form action="<?php echo DIRPAGE; ?>dashboard/setAdminRole/<?php echo (int) ($adminMember['id'] ?? 0); ?>" method="POST" class="moderation-actions">
                                    <?php echo $csrfField; ?>
                                    <select name="role" class="request-history-filter-select" style="min-width:170px;">
                                        <?php foreach ($adminRoleOptions as $roleKey => $roleLabel): ?>
                                            <option value="<?php echo htmlspecialchars((string) $roleKey); ?>" <?php echo $memberRole === (string) $roleKey ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars((string) $roleLabel); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn-secondary" data-confirm="Atualizar o papel administrativo deste utilizador?">Atualizar papel</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Nenhum membro administrativo encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>
</div>