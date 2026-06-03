<?php
/** @var array<int, array<string, mixed>> $notifications */
$notifications = is_array($notifications ?? null) ? $notifications : [];
$page = (int) ($page ?? 1);
$limit = (int) ($limit ?? 15);
$totalPages = (int) ($totalPages ?? 1);
$total = (int) ($total ?? count($notifications));
$unread = (int) ($unread ?? 0);
$cursorMode = !empty($cursorMode) || !empty($data['cursorMode']);
$nextCursor = (string) ($nextCursor ?? ($data['nextCursor'] ?? ''));

$queryParams = $_GET;
unset($queryParams['filters_open']);
$buildPageUrl = static function (int $targetPage) use ($queryParams): string {
    $params = $queryParams;
    unset($params['cursor']);
    $params['page'] = $targetPage;
    return DIRPAGE . 'notification/inbox?' . http_build_query($params);
};
$buildCursorUrl = static function (string $cursorValue) use ($queryParams): string {
    $params = $queryParams;
    unset($params['page']);
    $params['cursor'] = $cursorValue;
    return DIRPAGE . 'notification/inbox?' . http_build_query($params);
};
?>

<div class="container dashboard-view">
    <section class="dashboard-view-hero compact">
        <div>
            <span class="dashboard-hero-kicker">Notificações</span>
            <h1>Inbox</h1>
            <p>Veja alertas, conversas e atualizações do sistema num único lugar.</p>
        </div>
        <div class="request-actions">
            <a href="<?php echo DIRPAGE; ?>notification/archive" class="btn-secondary">Arquivo</a>
            <?php if ($unread > 0): ?>
                <form action="<?php echo DIRPAGE; ?>notification/mark_all_as_read" method="POST" class="request-actions">
                    <?php echo Src\classes\ClassCsrf::field(); ?>
                    <button type="submit" class="btn-primary">Marcar todas como lidas (<?php echo (int) $unread; ?>)</button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($_GET['error'])): ?>
        <div class="sub-feedback error"><?php echo htmlspecialchars((string) $_GET['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['success'])): ?>
        <div class="sub-feedback success"><?php echo htmlspecialchars((string) $_GET['success']); ?></div>
    <?php endif; ?>

    <div class="dashboard-module-card dashboard-kpi-section">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Resumo</span>
                <h3>Estado das notificações</h3>
            </div>
        </div>
        <div class="dashboard-overview-grid dashboard-overview-grid-tight dashboard-kpi-section">
            <div class="kpi-card kpi-blue">
                <div class="kpi-label">Total</div>
                <div class="kpi-value"><?php echo (int) $total; ?></div>
            </div>
            <div class="kpi-card kpi-yellow">
                <div class="kpi-label">Não lidas</div>
                <div class="kpi-value"><?php echo (int) $unread; ?></div>
            </div>
        </div>
    </div>

    <div class="dashboard-module-card">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Inbox</span>
                <h3>Notificações recentes</h3>
            </div>
        </div>

        <?php if (empty($notifications)): ?>
            <p class="dashboard-empty-copy">Sem notificações no momento.</p>
        <?php else: ?>
            <div class="dashboard-table-wrap">
                <table class="commissions-table moderation-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Recebida</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $n): ?>
                            <?php
                                $id = (int) ($n['id'] ?? 0);
                                $isRead = !empty($n['is_read']);
                                $targetUrl = (string) ($n['target_url'] ?? '');
                                $targetUrl = $targetUrl !== '' ? $targetUrl : (DIRPAGE . 'notification/inbox');
                            ?>
                            <tr class="<?php echo $isRead ? '' : 'is-unread'; ?>">
                                <td class="col-stack">
                                    <strong>
                                        <a class="table-name-link" href="<?php echo htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars((string) ($n['title'] ?? 'Notificação')); ?>
                                        </a>
                                    </strong>
                                    <?php if (!empty($n['message'])): ?>
                                        <span class="dashboard-inline-note"><?php echo htmlspecialchars((string) $n['message']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="dashboard-inline-note"><?php echo htmlspecialchars((string) ($n['type_label'] ?? $n['type'] ?? 'Notificação')); ?></td>
                                <td class="dashboard-inline-note dashboard-cell-nowrap">
                                    <?php echo !empty($n['created_at']) ? date('d/m/Y H:i', strtotime((string) $n['created_at'])) : '—'; ?>
                                </td>
                                <td>
                                    <span class="request-status-badge <?php echo $isRead ? 'request-status-aceite' : 'request-status-pendente'; ?>">
                                        <?php echo $isRead ? 'Lida' : 'Não lida'; ?>
                                    </span>
                                </td>
                                <td class="col-actions">
                                    <div class="request-actions">
                                        <?php if (!$isRead): ?>
                                            <form action="<?php echo DIRPAGE; ?>notification/mark_as_read" method="POST" class="request-actions">
                                                <?php echo Src\classes\ClassCsrf::field(); ?>
                                                <input type="hidden" name="notification_id" value="<?php echo $id; ?>">
                                                <button type="submit" class="btn-secondary">Marcar lida</button>
                                            </form>
                                        <?php endif; ?>
                                        <form action="<?php echo DIRPAGE; ?>notification/archiveItem" method="POST" class="request-actions">
                                            <?php echo Src\classes\ClassCsrf::field(); ?>
                                            <input type="hidden" name="notification_id" value="<?php echo $id; ?>">
                                            <button type="submit" class="btn-secondary">Arquivar</button>
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

