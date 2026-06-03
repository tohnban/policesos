<?php
$actionLabels = [
    'approve_user'                    => 'Aprovação de utilizador',
    'reject_user'                     => 'Rejeição de utilizador',
    'approve_trusted_badge'           => 'Aprovação de selo',
    'reject_trusted_badge'            => 'Rejeição de selo',
    'confirm_trusted_badge_payment'   => 'Pagamento de selo confirmado',
    'create_property'                 => 'Imóvel criado',
    'approve_property'                => 'Imóvel aprovado',
    'reject_property'                 => 'Imóvel rejeitado',
    'update_request_status'           => 'Status de solicitação actualizado',
    'create_request'                  => 'Solicitação criada',
];
$entityLabels = [
    'user'     => 'Utilizador',
    'property' => 'Imóvel',
    'request'  => 'Solicitação',
];
?>
<div class="container dashboard-view">
    <section class="dashboard-view-hero compact">
        <div>
            <span class="dashboard-hero-kicker">Rastreabilidade</span>
            <h1>Registo de Auditoria</h1>
                <?php if (!empty($filterType) && !empty($filterId)): ?>
                    <p>Historial para <strong><?php echo htmlspecialchars($entityLabels[$filterType] ?? $filterType); ?> #<?php echo (int) $filterId; ?></strong>.
                       <a href="<?php echo DIRPAGE; ?>dashboard/auditLog" class="dashboard-inline-link">Ver todos</a>
                    </p>
                <?php else: ?>
                    <p><?php echo (int) $total; ?> entradas registadas no sistema.</p>
                <?php endif; ?>
        </div>
    </section>

    <div class="dashboard-module-card">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Eventos</span>
                <h3>Histórico de Ações</h3>
            </div>
        </div>

        <?php if (!empty($logs)): ?>
            <div class="dashboard-table-wrap">
            <table class="commissions-table">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Actor</th>
                        <th>Ação</th>
                        <th>Entidade</th>
                        <th>Detalhe</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="dashboard-inline-note dashboard-cell-nowrap">
                                <?php echo !empty($log['created_at']) ? date('d/m/Y H:i', strtotime($log['created_at'])) : '–'; ?>
                            </td>
                            <td><?php echo htmlspecialchars($log['actor_name'] ?? '–'); ?></td>
                            <td>
                                <span class="audit-action-badge">
                                    <?php echo htmlspecialchars($actionLabels[$log['action'] ?? ''] ?? ($log['action'] ?? '–')); ?>
                                </span>
                            </td>
                            <td class="dashboard-cell-nowrap">
                                <?php if (!empty($log['entity_type'])): ?>
                                    <span class="audit-entity-type"><?php echo htmlspecialchars($entityLabels[$log['entity_type']] ?? $log['entity_type']); ?></span>
                                    <?php if (!empty($log['entity_id'])): ?>
                                        <a href="<?php echo DIRPAGE; ?>dashboard/auditLog/<?php echo htmlspecialchars($log['entity_type']); ?>/<?php echo (int) $log['entity_id']; ?>"
                                           title="Filtrar por esta entidade" class="dashboard-entity-link">#<?php echo (int) $log['entity_id']; ?></a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    –
                                <?php endif; ?>
                            </td>
                            <td class="dashboard-inline-note dashboard-cell-break">
                                <?php echo htmlspecialchars($log['details'] ?? ''); ?>
                            </td>
                            <td class="dashboard-inline-note dashboard-cell-nowrap">
                                <?php echo htmlspecialchars($log['ip_address'] ?? ''); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <?php if (empty($filterType) && (int) $totalPages > 1): ?>
                <div class="dashboard-pagination-wrap dashboard-pagination-wrap-start">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo DIRPAGE; ?>dashboard/auditLog?page=<?php echo $page - 1; ?>" class="btn-secondary">&larr; Anterior</a>
                    <?php endif; ?>
                    <span class="dashboard-pagination-copy">Página <?php echo $page; ?> de <?php echo $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo DIRPAGE; ?>dashboard/auditLog?page=<?php echo $page + 1; ?>" class="btn-secondary">Próxima &rarr;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="dashboard-empty-copy dashboard-empty-copy-spaced">Sem entradas de auditoria registadas.</p>
        <?php endif; ?>
    </div>

</div>
