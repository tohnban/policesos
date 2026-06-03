<?php
/** @var array<int, array<string, mixed>> $notifications */
$notifications = is_array($notifications ?? null) ? $notifications : [];
$page = (int) ($page ?? 1);
$limit = (int) ($limit ?? 20);
$totalPages = (int) ($totalPages ?? 1);
$total = (int) ($total ?? count($notifications));
$typeFilter = isset($typeFilter) ? $typeFilter : null;
$cursorMode = !empty($cursorMode) || !empty($data['cursorMode']);
$nextCursor = (string) ($nextCursor ?? ($data['nextCursor'] ?? ''));

$queryParams = $_GET;
unset($queryParams['filters_open']);
$buildPageUrl = static function (int $targetPage) use ($queryParams, $typeFilter): string {
    $params = $queryParams;
    unset($params['cursor']);
    $params['page'] = $targetPage;
    if ($typeFilter !== null && $typeFilter !== '') {
        $params['type'] = $typeFilter;
    } else {
        unset($params['type']);
    }
    return DIRPAGE . 'notification/archive?' . http_build_query($params);
};
$buildCursorUrl = static function (string $cursorValue) use ($queryParams, $typeFilter): string {
    $params = $queryParams;
    unset($params['page']);
    $params['cursor'] = $cursorValue;
    if ($typeFilter !== null && $typeFilter !== '') {
        $params['type'] = $typeFilter;
    } else {
        unset($params['type']);
    }
    return DIRPAGE . 'notification/archive?' . http_build_query($params);
};

$types = [
    '' => 'Todas',
    'Chat' => 'Chat',
    'Solicitações' => 'Solicitações',
    'Disputas' => 'Disputas',
    'Pagamentos' => 'Pagamentos',
    'Documentos' => 'Documentos',
    'Confiança' => 'Confiança',
    'Conta' => 'Conta',
    'Destaque' => 'Destaque',
    'Acompanhamento' => 'Acompanhamento',
    'Notificação' => 'Outras',
];
?>

<div class="container dashboard-view">
    <section class="dashboard-view-hero compact">
        <div>
            <span class="dashboard-hero-kicker">Notificações</span>
            <h1>Arquivo</h1>
            <p>Histórico de notificações arquivadas por tipo.</p>
        </div>
        <div class="request-actions">
            <a href="<?php echo DIRPAGE; ?>notification/inbox" class="btn-secondary">Voltar à inbox</a>
        </div>
    </section>

    <?php if (!empty($_GET['error'])): ?>
        <div class="sub-feedback error"><?php echo htmlspecialchars((string) $_GET['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['success'])): ?>
        <div class="sub-feedback success"><?php echo htmlspecialchars((string) $_GET['success']); ?></div>
    <?php endif; ?>

    <div class="dashboard-tab-nav payments-admin-tabs" style="margin-bottom: 12px;">
        <?php foreach ($types as $value => $label): ?>
            <?php
                $isActive = ($value === '' && ($typeFilter === null || $typeFilter === '')) || ($value !== '' && $typeFilter === $value);
                $href = DIRPAGE . 'notification/archive?' . http_build_query(array_filter([
                    'type' => $value !== '' ? $value : null,
                ]));
            ?>
            <a href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>" class="dashboard-tab-link <?php echo $isActive ? 'is-active' : ''; ?>">
                <?php echo htmlspecialchars($label); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="dashboard-module-card">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Arquivo</span>
                <h3>Notificações arquivadas</h3>
            </div>
            <small class="dashboard-inline-note">Total: <?php echo (int) $total; ?></small>
        </div>

        <?php if (empty($notifications)): ?>
            <p class="dashboard-empty-copy">Sem notificações arquivadas para este filtro.</p>
        <?php else: ?>
            <div class="dashboard-table-wrap">
                <table class="commissions-table moderation-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Arquivada</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $n): ?>
                            <?php $id = (int) ($n['id'] ?? 0); ?>
                            <tr>
                                <td class="col-stack">
                                    <strong><?php echo htmlspecialchars((string) ($n['title'] ?? 'Notificação')); ?></strong>
                                    <?php if (!empty($n['message'])): ?>
                                        <span class="dashboard-inline-note"><?php echo htmlspecialchars((string) $n['message']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="dashboard-inline-note"><?php echo htmlspecialchars((string) ($n['type_label'] ?? $n['type'] ?? 'Notificação')); ?></td>
                                <td class="dashboard-inline-note dashboard-cell-nowrap">
                                    <?php echo !empty($n['created_at']) ? date('d/m/Y H:i', strtotime((string) $n['created_at'])) : '—'; ?>
                                </td>
                                <td class="col-actions">
                                    <div class="request-actions">
                                        <form action="<?php echo DIRPAGE; ?>notification/unarchive" method="POST" class="request-actions">
                                            <?php echo Src\classes\ClassCsrf::field(); ?>
                                            <input type="hidden" name="notification_id" value="<?php echo $id; ?>">
                                            <button type="submit" class="btn-secondary">Restaurar</button>
                                        </form>
                                        <form action="<?php echo DIRPAGE; ?>notification/delete" method="POST" class="request-actions">
                                            <?php echo Src\classes\ClassCsrf::field(); ?>
                                            <input type="hidden" name="notification_id" value="<?php echo $id; ?>">
                                            <button type="submit" class="btn-secondary" data-confirm="Apagar definitivamente esta notificação?">Apagar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!$cursorMode && $totalPages > 1): ?>
                <div class="dashboard-pagination-wrap dashboard-pagination-wrap-start">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo htmlspecialchars($buildPageUrl($page - 1)); ?>" class="btn-secondary">&larr; Anterior</a>
                    <?php endif; ?>
                    <span class="dashboard-pagination-copy">Página <?php echo (int) $page; ?> de <?php echo (int) $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo htmlspecialchars($buildPageUrl($page + 1)); ?>" class="btn-secondary">Próxima &rarr;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($cursorMode && $nextCursor !== ''): ?>
                <div class="dashboard-pagination-wrap dashboard-pagination-wrap-start">
                    <a href="<?php echo htmlspecialchars($buildCursorUrl($nextCursor)); ?>" class="btn-secondary">Próxima &rarr;</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

