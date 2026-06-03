<?php
$overviewCards = [
    [
        'label' => 'Solicitações',
        'value' => count($requests),
        'tone' => 'blue',
        'icon' => 'fa-inbox',
        'meta' => !empty($user['is_admin']) ? 'Fluxo geral do sistema' : 'Contatos ligados ao seu perfil',
    ],
    [
        'label' => 'Não lidas',
        'value' => (int) $unreadNotifications,
        'tone' => 'red',
        'icon' => 'fa-bell',
        'meta' => 'Alertas e atualizações recentes',
    ],
];

if (!empty($user['is_affiliate'])) {
    $overviewCards[] = [
        'label' => 'Imóveis Indicados',
        'value' => (int) ($stats['properties'] ?? 0),
        'tone' => 'green',
        'icon' => 'fa-link',
        'meta' => 'Ativos com link de referência',
    ];
    $overviewCards[] = [
        'label' => 'Comissões',
        'value' => number_format((float) ($stats['commissions'] ?? 0), 0, ',', '.') . ' Kz',
        'tone' => 'yellow',
        'icon' => 'fa-money',
        'meta' => 'Acumulado no seu ciclo atual',
    ];
}

if (!empty($rejectedDocuments)) {
    $overviewCards[] = [
        'label' => 'Documentos Pendentes',
        'value' => count($rejectedDocuments),
        'tone' => 'red',
        'icon' => 'fa-exclamation-triangle',
        'meta' => 'Precisam de nova submissão',
    ];
}

$quickActions = [];
if (empty($user['is_admin'])) {
    $quickActions[] = ['href' => DIRPAGE . 'property/create', 'icon' => 'fa-plus-circle', 'title' => 'Novo Imóvel', 'description' => 'Cadastre um anúncio e envie para aprovação.'];
}
$quickActions[] = ['href' => DIRPAGE . 'requests', 'icon' => 'fa-inbox', 'title' => 'Solicitações', 'description' => 'Acompanhe negociações e mudanças de estado.'];
$quickActions[] = ['href' => DIRPAGE . 'dashboard/requestChats', 'icon' => 'fa-comments', 'title' => 'Conversas', 'description' => 'Acesse todas as negociações com mensagens em um único lugar.'];

if (!empty($user['is_affiliate'])) {
    $quickActions[] = ['href' => DIRPAGE . 'commissions', 'icon' => 'fa-money', 'title' => 'Comissões', 'description' => 'Veja valores pagos e pendentes.'];
    $quickActions[] = ['href' => DIRPAGE . 'referrals', 'icon' => 'fa-link', 'title' => 'Indicações', 'description' => 'Copie links e monitore seu alcance.'];
}
if (Src\classes\ClassAccess::can('users.review', $user)) {
    $quickActions[] = ['href' => DIRPAGE . 'dashboard/moderate_users', 'icon' => 'fa-users', 'title' => 'Perfis', 'description' => 'Revise aprovações e confiança.'];
}
if (Src\classes\ClassAccess::can('properties.moderate', $user)) {
    $quickActions[] = ['href' => DIRPAGE . 'property/moderate', 'icon' => 'fa-building-o', 'title' => 'Imóveis', 'description' => 'Aprove ou rejeite anúncios pendentes.'];
}
if (Src\classes\ClassAccess::can('documents.review', $user)) {
    $quickActions[] = ['href' => DIRPAGE . 'dashboard/reviewDocuments', 'icon' => 'fa-file-text-o', 'title' => 'Documentos', 'description' => 'Valide envios e conformidade.'];
}
if (Src\classes\ClassAccess::can('requests.manage', $user)) {
    $quickActions[] = ['href' => DIRPAGE . 'dashboard/disputes', 'icon' => 'fa-balance-scale', 'title' => 'Disputas', 'description' => 'Analise e resolva conflitos entre partes.'];
}
if (Src\classes\ClassAccess::isSuperAdmin($user)) {
    $quickActions[] = ['href' => DIRPAGE . 'dashboard/kpi', 'icon' => 'fa-line-chart', 'title' => 'KPIs', 'description' => 'Analise números críticos do sistema.'];
}
if (Src\classes\ClassAccess::can('payments.manage', $user)) {
    $quickActions[] = ['href' => DIRPAGE . 'dashboard/payments', 'icon' => 'fa-credit-card', 'title' => 'Pagamentos', 'description' => 'Confirme repasses e referências.'];
}
if (Src\classes\ClassAccess::can('audit.view', $user)) {
    $quickActions[] = ['href' => DIRPAGE . 'dashboard/auditLog', 'icon' => 'fa-shield', 'title' => 'Auditoria', 'description' => 'Rastreie ações e eventos críticos.'];
}

$notificationGroupPriority = [
    'Chat' => 10,
    'Solicitações' => 20,
    'Disputas' => 30,
    'Pagamentos' => 40,
    'Documentos' => 50,
    'Confiança' => 60,
    'Conta' => 70,
    'Destaque' => 80,
    'Acompanhamento' => 90,
    'Notificação' => 999,
];

$notificationGroups = [];
foreach (($notifications ?? []) as $notificationItem) {
    $groupLabel = trim((string) ($notificationItem['type_label'] ?? 'Notificação'));
    if ($groupLabel === '') {
        $groupLabel = 'Notificação';
    }

    if (!isset($notificationGroups[$groupLabel])) {
        $notificationGroups[$groupLabel] = [];
    }

    $notificationGroups[$groupLabel][] = $notificationItem;
}

foreach ($notificationGroups as $groupLabel => &$groupItems) {
    usort($groupItems, static function (array $left, array $right): int {
        $leftUnread = empty($left['is_read']) ? 1 : 0;
        $rightUnread = empty($right['is_read']) ? 1 : 0;
        if ($leftUnread !== $rightUnread) {
            return $rightUnread <=> $leftUnread;
        }

        $leftTime = strtotime((string) ($left['created_at'] ?? '')) ?: 0;
        $rightTime = strtotime((string) ($right['created_at'] ?? '')) ?: 0;
        return $rightTime <=> $leftTime;
    });
}
unset($groupItems);

uksort($notificationGroups, static function (string $left, string $right) use ($notificationGroupPriority, $notificationGroups): int {
    $leftPriority = $notificationGroupPriority[$left] ?? 500;
    $rightPriority = $notificationGroupPriority[$right] ?? 500;

    if ($leftPriority !== $rightPriority) {
        return $leftPriority <=> $rightPriority;
    }

    $leftUnreadCount = count(array_filter($notificationGroups[$left] ?? [], static function (array $item): bool {
        return empty($item['is_read']);
    }));
    $rightUnreadCount = count(array_filter($notificationGroups[$right] ?? [], static function (array $item): bool {
        return empty($item['is_read']);
    }));

    if ($leftUnreadCount !== $rightUnreadCount) {
        return $rightUnreadCount <=> $leftUnreadCount;
    }

    $leftLatest = 0;
    foreach (($notificationGroups[$left] ?? []) as $item) {
        $leftLatest = max($leftLatest, strtotime((string) ($item['created_at'] ?? '')) ?: 0);
    }

    $rightLatest = 0;
    foreach (($notificationGroups[$right] ?? []) as $item) {
        $rightLatest = max($rightLatest, strtotime((string) ($item['created_at'] ?? '')) ?: 0);
    }

    return $rightLatest <=> $leftLatest;
});
?>

<div class="container dashboard-home dashboard-home-overview">
    <section class="dashboard-hero-panel dashboard-hero-panel--overview">
        <div class="dashboard-hero-copy">
            <span class="dashboard-hero-kicker">Centro de controlo</span>
            <h1>Meu Painel</h1>
            <p>
                <?php if (!empty($user['is_admin'])): ?>
                    Gestão consolidada de solicitações, moderação e operação diária da plataforma.
                <?php else: ?>
                    Acompanhe oportunidades, resposta do mercado e a saúde da sua conta em um só lugar.
                <?php endif; ?>
            </p>

            <div class="dashboard-hero-tags">
                <span><i class="fa fa-bell"></i> <?php echo (int) $unreadNotifications; ?> alertas ativos</span>
                <span><i class="fa fa-check-circle"></i> <?php echo !empty($trust['verified']) ? 'Perfil verificado' : 'Verificação pendente'; ?></span>
                <?php if (!empty($user['is_affiliate'])): ?>
                    <span><i class="fa fa-link"></i> Afiliado ativo</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-hero-side">
            <div class="dashboard-hero-highlight">
                <div class="dashboard-hero-highlight-label">Foco imediato</div>
                <div class="dashboard-hero-highlight-value"><?php echo !empty($rejectedDocuments) ? count($rejectedDocuments) . ' ajustes' : max(1, (int) $unreadNotifications) . ' sinais'; ?></div>
                <p><?php echo !empty($rejectedDocuments) ? 'Existem documentos rejeitados para regularizar no seu fluxo.' : 'Mantenha respostas e notificações sob controlo ao longo do dia.'; ?></p>
            </div>
        </div>
    </section>

    <section class="dashboard-overview-grid" aria-labelledby="dashboard-overview-title">
        <h2 class="dashboard-section-title" id="dashboard-overview-title">Resumo da conta</h2>
        <?php foreach ($overviewCards as $card): ?>
            <article class="dashboard-overview-card tone-<?php echo htmlspecialchars($card['tone']); ?>">
                <div class="dashboard-overview-icon"><i class="fa <?php echo htmlspecialchars($card['icon']); ?>"></i></div>
                <div class="dashboard-overview-body">
                    <span><?php echo htmlspecialchars($card['label']); ?></span>
                    <strong><?php echo htmlspecialchars((string) $card['value']); ?></strong>
                    <small><?php echo htmlspecialchars($card['meta']); ?></small>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="dashboard-quick-actions-section" aria-labelledby="dashboard-quick-actions-title">
        <div class="dashboard-module-card dashboard-quick-actions-panel">
            <div class="dashboard-module-head compact">
                <div>
                    <span class="dashboard-module-kicker">Atalhos</span>
                    <h2 class="dashboard-section-title dashboard-section-title--inline" id="dashboard-quick-actions-title">Acesso rápido</h2>
                </div>
            </div>
            <div class="dashboard-action-grid">
                <?php foreach ($quickActions as $action): ?>
                    <a href="<?php echo htmlspecialchars((string) ($action['href'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="dashboard-action-card">
                        <div class="dashboard-action-icon"><i class="fa <?php echo htmlspecialchars($action['icon']); ?>"></i></div>
                        <div>
                            <strong><?php echo htmlspecialchars($action['title']); ?></strong>
                            <p><?php echo htmlspecialchars($action['description']); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="dashboard-notifications-section" id="notifications" aria-labelledby="dashboard-notifications-title">
        <div class="dashboard-module-card dashboard-notifications-panel">
            <div class="dashboard-module-head dashboard-notifications-head">
                <div>
                    <span class="dashboard-module-kicker">Fluxo recente</span>
                    <h2 class="dashboard-section-title dashboard-section-title--inline" id="dashboard-notifications-title">Notificações</h2>
                    <?php if (!empty($unreadNotifications)): ?>
                        <p class="dashboard-notifications-mobile-hint"><?php echo (int) $unreadNotifications; ?> por ler</p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($unreadNotifications)): ?>
                    <form action="<?php echo DIRPAGE; ?>dashboard/markNotificationsRead" method="POST" class="dashboard-inline-form-reset">
                        <?php echo Src\classes\ClassCsrf::field(); ?>
                        <button type="submit" class="btn-secondary">Marcar todas como lidas</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (!empty($notificationGroups)): ?>
                <div class="dashboard-notification-groups">
                    <?php foreach ($notificationGroups as $groupLabel => $groupItems): ?>
                        <section class="dashboard-notification-group">
                            <div class="dashboard-notification-group-head">
                                <strong><?php echo htmlspecialchars((string) $groupLabel); ?></strong>
                                <?php
                                    $groupUnreadCount = count(array_filter($groupItems, static function (array $item): bool {
                                        return empty($item['is_read']);
                                    }));
                                ?>
                                <span><?php echo $groupUnreadCount > 0 ? $groupUnreadCount . ' por ler' : count($groupItems) . ' no total'; ?></span>
                            </div>

                            <div class="dashboard-notification-list">
                                <?php foreach ($groupItems as $notification): ?>
                                    <article class="dashboard-notification-item <?php echo !empty($notification['is_read']) ? 'is-read' : 'is-unread'; ?>"
                                             data-notification-id="<?php echo (int) ($notification['id'] ?? 0); ?>"
                                             data-notification-read-url="<?php echo DIRPAGE; ?>dashboard/markNotificationRead/<?php echo (int) ($notification['id'] ?? 0); ?>">
                                        <a href="<?php echo htmlspecialchars((string) ($notification['target_url'] ?? (DIRPAGE . 'notification/inbox'))); ?>"
                                           class="dashboard-notification-item-link"
                                           data-notification-id="<?php echo (int) ($notification['id'] ?? 0); ?>"
                                           data-notification-read-url="<?php echo DIRPAGE; ?>dashboard/markNotificationRead/<?php echo (int) ($notification['id'] ?? 0); ?>">
                                            <div class="dashboard-notification-head">
                                                <span class="notification-type-badge"><?php echo htmlspecialchars((string) ($notification['type_label'] ?? 'Notificação')); ?></span>
                                                <span class="dashboard-notification-action"><?php echo htmlspecialchars((string) ($notification['action_label'] ?? 'Abrir')); ?></span>
                                            </div>
                                            <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></small>
                                        </a>
                                        <button type="button"
                                                class="dashboard-inline-link notification-toggle-read-btn"
                                                data-notification-id="<?php echo (int) ($notification['id'] ?? 0); ?>"
                                                data-notification-unread-url="<?php echo DIRPAGE; ?>dashboard/markNotificationUnread/<?php echo (int) ($notification['id'] ?? 0); ?>"
                                                <?php echo !empty($notification['is_read']) ? '' : 'hidden'; ?>>Marcar não lido</button>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="dashboard-empty-copy">Sem notificações no momento.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="dashboard-home-grid">
        <div class="dashboard-home-main">
            <?php if (!empty($rejectedDocuments)): ?>
            <div class="dashboard-alert-card dashboard-alert-card-spaced" id="rejected-documents">
                <div class="dashboard-module-head">
                    <div>
                        <span class="dashboard-module-kicker">Ação necessária</span>
                        <h3><i class="fa fa-exclamation-triangle"></i> Documentos Rejeitados</h3>
                    </div>
                    <span class="dashboard-alert-count"><?php echo count($rejectedDocuments); ?> itens</span>
                </div>

                <div class="dashboard-alert-list">
                    <?php foreach ($rejectedDocuments as $doc): ?>
                        <div class="dashboard-alert-item">
                            <div class="dashboard-alert-copy">
                                <strong><?php echo htmlspecialchars(str_replace('_', ' ', $doc['type'])); ?> (<?php echo htmlspecialchars($doc['version']); ?>)</strong>
                                <p>Rejeitado em <?php echo date('d/m/Y H:i', strtotime($doc['reviewed_at'])); ?></p>
                                <?php if (!empty($doc['rejection_reason'])): ?>
                                    <div class="dashboard-alert-reason"><strong>Motivo:</strong> <?php echo htmlspecialchars($doc['rejection_reason']); ?></div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn-primary" data-doc-modal-open="resubmitModal<?php echo $doc['id']; ?>">
                                Resubmeter
                            </button>
                        </div>

                        <div class="doc-modal" id="resubmitModal<?php echo $doc['id']; ?>" hidden>
                            <div class="doc-modal-panel" role="dialog" aria-modal="true" aria-labelledby="resubmitTitle<?php echo $doc['id']; ?>">
                                <div class="doc-modal-head">
                                    <h5 id="resubmitTitle<?php echo $doc['id']; ?>">Resubmeter Documento</h5>
                                    <button type="button" class="doc-modal-close" data-doc-modal-close aria-label="Fechar">&times;</button>
                                </div>
                                <form method="POST" action="<?php echo DIRPAGE; ?>dashboard/resubmitDocument/<?php echo $doc['id']; ?>" enctype="multipart/form-data">
                                    <div class="doc-modal-body">
                                        <div class="alert alert-info mb-3">
                                            <strong>Documento:</strong> <?php echo htmlspecialchars(str_replace('_', ' ', $doc['type'])); ?>
                                            <br><strong>Versão Anterior:</strong> <?php echo htmlspecialchars($doc['version']); ?>
                                            <br><strong>Nova Versão:</strong> v<?php echo (int) substr($doc['version'], 1) + 1; ?>
                                        </div>
                                        <div class="alert alert-warning">
                                            <strong>Motivo da Rejeição:</strong>
                                            <p class="dashboard-alert-reason-copy">
                                                <?php echo htmlspecialchars($doc['rejection_reason'] ?? 'Sem motivo especificado'); ?>
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Carregar Novo Documento *</label>
                                            <input type="file" class="form-control" name="document_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                            <small class="form-text text-muted">Formatos aceitos: PDF, JPG, PNG | Tamanho máximo: 1MB</small>
                                        </div>
                                    </div>
                                    <div class="doc-modal-foot">
                                        <button type="button" class="btn-secondary" data-doc-modal-close>Cancelar</button>
                                        <?php echo Src\classes\ClassCsrf::field(); ?>
                                        <button type="submit" class="btn-primary">Resubmeter Documento</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <aside class="dashboard-home-side">
            <div class="dashboard-module-card trust-panel dashboard-trust-card">
                <div class="dashboard-module-head compact">
                    <div>
                        <span class="dashboard-module-kicker">Confiança</span>
                        <h2 class="dashboard-section-title dashboard-section-title--inline">Nível da conta</h2>
                    </div>
                </div>

                <div class="trust-badges">
                    <?php if (!empty($trust['verified'])): ?>
                        <span class="property-verified-badge"><i class="fa fa-check-circle"></i> Perfil verificado</span>
                    <?php endif; ?>
                    <?php if (!empty($trust['trusted'])): ?>
                        <span class="property-trust-badge"><i class="fa fa-shield"></i> Utilizador de confiança</span>
                    <?php endif; ?>
                    <?php if (empty($trust['verified']) && empty($trust['trusted'])): ?>
                        <span class="trust-muted">Ainda sem selo de confiança</span>
                    <?php endif; ?>
                </div>

                <?php if (($trust['badge_status'] ?? 'nenhum') === 'pendente'): ?>
                    <p class="trust-progress">Seu pedido de "Utilizador de confiança" está em análise pela equipa.</p>
                <?php elseif (($trust['badge_status'] ?? 'nenhum') === 'aprovado' && empty($trust['fee_paid'])): ?>
                    <p class="trust-progress">Pedido aprovado. Falta concluir o pagamento da taxa: <?php echo number_format((float) ($trust['fee_required'] ?? 0), 0, ',', '.'); ?> Kz.</p>
                <?php elseif (($trust['badge_status'] ?? 'nenhum') === 'rejeitado'): ?>
                    <p class="trust-progress">Seu pedido foi rejeitado. Você pode solicitar novamente no perfil.</p>
                <?php elseif (empty($trust['trusted']) && !empty($trust['blockers'])): ?>
                    <p class="trust-progress">Requisitos em falta: <?php echo htmlspecialchars(implode('; ', $trust['blockers'])); ?>.</p>
                <?php elseif (empty($trust['trusted'])): ?>
                    <p class="trust-progress">Para obter "Utilizador de confiança", solicite a avaliação no seu perfil.</p>
                <?php endif; ?>
            </div>
        </aside>
    </section>
</div>