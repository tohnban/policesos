<div class="container dashboard-view">
    <section class="dashboard-view-hero compact">
        <div>
            <span class="dashboard-hero-kicker">Receita</span>
            <h1>Minhas Comissões</h1>
            <p>Veja o histórico das comissões geradas pelos seus imóveis indicados.</p>
        </div>
    </section>

    <div class="dashboard-overview-grid dashboard-overview-grid-tight dashboard-kpi-section">
        <div class="kpi-card">
            <div class="kpi-label">Total gerado</div>
            <div class="kpi-value"><?php echo number_format((float)($summary['earned_total']??0),0,',','.'); ?> Kz</div>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-label">Já recebido</div>
            <div class="kpi-value"><?php echo number_format((float)($summary['earned_paid']??0),0,',','.'); ?> Kz</div>
        </div>
        <div class="kpi-card kpi-yellow">
            <div class="kpi-label">Pendente</div>
            <div class="kpi-value"><?php echo number_format((float)($summary['earned_pending']??0),0,',','.'); ?> Kz</div>
        </div>
        <div class="kpi-card kpi-blue">
            <div class="kpi-label">Este mês</div>
            <div class="kpi-value"><?php echo number_format((float)($summary['earned_this_month']??0),0,',','.'); ?> Kz</div>
        </div>
    </div>

    <div class="dashboard-module-card">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Histórico</span>
                <h3>Lançamentos de Comissão</h3>
            </div>
        </div>

        <div class="dashboard-table-wrap">
        <table class="commissions-table">
            <thead>
                <tr>
                    <th>Imóvel</th>
                    <th>Meu valor</th>
                    <th>Sistema</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Referência</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($commissions)): ?>
                    <?php foreach ($commissions as $commission): ?>
                        <tr data-focus-commission-id="<?php echo (int) ($commission['id'] ?? 0); ?>">
                            <td><?php echo htmlspecialchars($commission['title']); ?></td>
                            <td><?php echo number_format((float)($commission['affiliate_amount']??0),0,',','.'); ?> Kz
                                <span class="dashboard-inline-note">(<?php echo number_format((float)($commission['affiliate_pct']??0),2,',','.'); ?>%)</span>
                            </td>
                            <td><?php echo number_format((float)($commission['system_amount']??0),0,',','.'); ?> Kz</td>
                            <td><?php echo number_format((float)$commission['amount'],0,',','.'); ?> Kz</td>
                            <td>
                                <?php
                                    $stMap = ['pendente'=>['Pendente','pendente'],'pending'=>['Pendente','pendente'],'pago'=>['Pago','pago'],'paid'=>['Pago','pago'],'cancelado'=>['Cancelado','cancelado'],'cancelled'=>['Cancelado','cancelado']];
                                    [$stLabel,$stKey] = $stMap[$commission['status']??'pendente'] ?? ['–','pendente'];
                                ?>
                                <span class="commission-status-badge commission-status-<?php echo $stKey; ?>"><?php echo $stLabel; ?></span>
                            </td>
                            <td class="dashboard-inline-note"><?php echo htmlspecialchars($commission['payment_reference']??'–'); ?></td>
                            <td class="dashboard-cell-nowrap"><?php echo date('d/m/Y', strtotime($commission['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <div class="empty-state-content">
                                <i class="fa fa-money"></i>
                                <p>Nenhuma comissão encontrada.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
