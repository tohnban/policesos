<div class="container dashboard-view">
    <?php
        $page = max(1, (int) ($page ?? 1));
        $totalPages = max(1, (int) ($totalPages ?? 1));
        $allowedTabs = ['boosts', 'properties'];
        $activeTab = (string) ($_GET['tab'] ?? 'properties');
        if (!in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'properties';
        }

        function moderatePropertiesTabUrl(string $tab, int $targetPage = 1): string {
            $params = ['tab' => $tab];
            if ($tab === 'properties' && $targetPage > 1) {
                $params['page'] = $targetPage;
            }
            return DIRPAGE . 'property/moderate?' . http_build_query($params);
        }
    ?>
    <section class="dashboard-view-hero compact">
        <div>
            <span class="dashboard-hero-kicker">Moderação</span>
            <h1>Moderação de Imóveis</h1>
            <p>Revise e decida sobre imóveis em fila de análise.</p>
        </div>
    </section>

    <div class="dashboard-tab-nav" style="margin-bottom:24px;">
        <a href="<?php echo moderatePropertiesTabUrl('properties'); ?>" class="dashboard-tab-link <?php echo $activeTab === 'properties' ? 'is-active' : ''; ?>">
            <i class="fa fa-building"></i> Imóveis Pendentes (<?php echo (int) ($pendingTotal ?? 0); ?>)
        </a>
        <a href="<?php echo moderatePropertiesTabUrl('boosts'); ?>" class="dashboard-tab-link <?php echo $activeTab === 'boosts' ? 'is-active' : ''; ?>">
            <i class="fa fa-star"></i> Destaques Pendentes (<?php echo (int) ($pendingBoostsTotal ?? 0); ?>)
        </a>
    </div>

    <?php if ($activeTab === 'boosts'): ?>
    <div class="dashboard-module-card">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Destaque</span>
                <h3>Destaques Pendentes
                    <?php if (($pendingBoostsTotal ?? 0) > 0): ?>
                        <span class="kpi-badge kpi-badge-yellow"><?php echo (int) ($pendingBoostsTotal ?? 0); ?></span>
                    <?php endif; ?>
                </h3>
            </div>

        </div>

        <?php if (!empty($pendingBoosts)): ?>
        <div class="dashboard-table-wrap">
        <table class="commissions-table moderation-table">
            <thead>
                <tr>
                    <th>Imóvel</th>
                    <th>Solicitante</th>
                    <th>Duração</th>
                    <th>Valor</th>
                    <th>Solicitado em</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingBoosts as $boost): ?>
                    <tr>
                        <td class="moderation-cell-text">
                            <a href="<?php echo DIRPAGE; ?>property/<?php echo (int) ($boost['property_id'] ?? 0); ?>" class="owner-name-link">
                                <?php echo htmlspecialchars($boost['property_title'] ?? 'N/A'); ?>
                            </a>
                        </td>
                        <td class="moderation-cell-text">
                            <?php if (!empty($boost['requester_id'])): ?>
                                <a href="<?php echo DIRPAGE; ?>property/owner/<?php echo (int) ($boost['requester_id'] ?? 0); ?>" class="table-name-link">
                                    <?php echo htmlspecialchars($boost['requester_name'] ?? 'N/A'); ?>
                                </a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($boost['requester_name'] ?? 'N/A'); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int) ($boost['duration_days'] ?? 0); ?> dias</td>
                        <td><?php echo number_format((float) ($boost['fee_required'] ?? 0), 0, ',', '.'); ?> Kz</td>
                        <td class="dashboard-inline-note dashboard-cell-nowrap"><?php echo !empty($boost['requested_at']) ? date('d/m/Y H:i', strtotime($boost['requested_at'])) : '–'; ?></td>
                        <td>
                            <a href="<?php echo DIRPAGE; ?>dashboard/payments?tab=boosts&boost_id=<?php echo (int) ($boost['id'] ?? 0); ?>" class="dashboard-btn-link">Ver pagamento</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php if (($pendingBoostsTotal ?? 0) > 5): ?>
            <p class="dashboard-inline-note" style="padding:10px 20px 16px;">
                A mostrar 5 de <?php echo (int) $pendingBoostsTotal; ?>.
                <a href="<?php echo DIRPAGE; ?>dashboard/payments?tab=boosts">Ver todos &rarr;</a>
            </p>
        <?php endif; ?>
        <?php else: ?>
            <p class="dashboard-empty-copy">Nenhuma solicitação de destaque pendente.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($activeTab === 'properties'): ?>
    <div class="dashboard-module-card">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Fila</span>
                <h3>Imóveis Pendentes</h3>
            </div>
        </div>

        <div class="dashboard-table-wrap">
        <table class="commissions-table moderation-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Proprietário</th>
                    <th>Preço</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pending)): ?>
                    <?php foreach ($pending as $property): ?>
                        <tr>
                            <td class="moderation-cell-text">
                                <a href="<?php echo DIRPAGE; ?>property/<?php echo (int) $property['id']; ?>" class="owner-name-link">
                                    <?php echo htmlspecialchars($property['title']); ?>
                                </a>
                            </td>
                            <td class="moderation-cell-text">
                                <?php if (!empty($property['affiliate_id'])): ?>
                                    <a href="<?php echo DIRPAGE; ?>property/owner/<?php echo (int) $property['affiliate_id']; ?>" class="owner-name-link">
                                        <?php echo htmlspecialchars($property['owner_name'] ?? 'N/A'); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($property['owner_name'] ?? 'N/A'); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($property['price'], 0, ',', '.'); ?> Kz</td>
                            <td>
                                <?php
                                    $moderationStatus = (string) ($property['status'] ?? 'pendente');
                                    $moderationLabel = [
                                        'pendente' => 'Pendente',
                                        'em_analise' => 'Em análise',
                                        'disponivel' => 'Aprovado',
                                        'rejeitado' => 'Rejeitado',
                                    ][$moderationStatus] ?? ucfirst($moderationStatus);
                                ?>
                                <span class="request-status-badge request-status-<?php echo htmlspecialchars($moderationStatus); ?>">
                                    <?php echo htmlspecialchars($moderationLabel); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($property['created_at'])); ?></td>
                            <td>
                                <div class="moderation-actions">
                                <?php if (($property['status'] ?? '') === 'pendente'): ?>
                                    <form action="<?php echo DIRPAGE; ?>property/startAnalysis/<?php echo $property['id']; ?>" method="POST">
                                        <?php echo Src\classes\ClassCsrf::field(); ?>
                                        <button type="submit" class="btn-secondary">Iniciar análise</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (($property['status'] ?? '') === 'em_analise'): ?>
                                    <form action="<?php echo DIRPAGE; ?>property/approve/<?php echo $property['id']; ?>" method="POST">
                                        <?php echo Src\classes\ClassCsrf::field(); ?>
                                        <button type="submit" class="btn-primary">Aprovar visibilidade</button>
                                    </form>
                                    <form action="<?php echo DIRPAGE; ?>property/reject/<?php echo $property['id']; ?>" method="POST">
                                        <?php echo Src\classes\ClassCsrf::field(); ?>
                                        <button type="submit" class="btn-secondary">Reprovar</button>
                                    </form>
                                <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Nenhum imóvel em fila de moderação.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <?php if ((int) ($pendingTotal ?? 0) > 0): ?>
            <p class="dashboard-pagination-copy" style="padding-top:10px;">
                A mostrar <?php echo count($pending ?? []); ?> de <?php echo (int) ($pendingTotal ?? 0); ?>.
            </p>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <div class="dashboard-pagination-wrap dashboard-pagination-wrap-start">
                <?php if ($page > 1): ?>
                    <a href="<?php echo moderatePropertiesTabUrl('properties', $page - 1); ?>" class="btn-secondary">&larr; Anterior</a>
                <?php endif; ?>
                <span class="dashboard-pagination-copy">Página <?php echo $page; ?> de <?php echo $totalPages; ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="<?php echo moderatePropertiesTabUrl('properties', $page + 1); ?>" class="btn-secondary">Próxima &rarr;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>