<div class="container dashboard-view">
    <section class="dashboard-view-hero compact">
        <div>
            <span class="dashboard-hero-kicker">Promoção</span>
            <h1>Promoção de Imóveis</h1>
            <p>Gira os imóveis que promove e acompanhe as suas comissões num único lugar.</p>
        </div>
    </section>

    <?php
        $activeTab = $active_tab ?? 'referrals';
    ?>

    <!-- Tab navigation -->
    <div class="dashboard-tab-nav" style="margin-bottom:24px;">
        <a href="?tab=referrals"
           class="dashboard-tab-link <?php echo $activeTab === 'referrals' ? 'is-active' : ''; ?>">
            <i class="fa fa-link"></i> Indicações
        </a>
        <a href="?tab=commissions"
           class="dashboard-tab-link <?php echo $activeTab === 'commissions' ? 'is-active' : ''; ?>">
            <i class="fa fa-money"></i> Comissões
        </a>
    </div>

    <?php if ($activeTab === 'referrals'): ?>

        <!-- ── INDICAÇÕES TAB ── -->
        <div class="dashboard-module-card dashboard-kpi-section">
            <div class="dashboard-module-head compact">
                <div>
                    <span class="dashboard-module-kicker">Regra do programa</span>
                    <h3>Como a indicação funciona</h3>
                </div>
            </div>
            <p class="dashboard-inline-note">Os links abaixo só geram comissão para imóveis em que a sua afiliação já foi aprovada pelo proprietário. Partilhe o link; quando um negócio fechar, a comissão é lançada automaticamente.</p>
        </div>

        <div class="dashboard-module-card">
            <div class="dashboard-module-head compact">
                <div>
                    <span class="dashboard-module-kicker">Afiliações activas</span>
                    <h3>Imóveis que está a promover</h3>
                </div>
            </div>

            <div class="dashboard-table-wrap">
            <table class="commissions-table">
                <thead>
                    <tr>
                        <th>Imóvel</th>
                        <th>Proprietário</th>
                        <th>Preço</th>
                        <th>Indicações</th>
                        <th>Estado</th>
                        <th>Link de Indicação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($affiliated_properties)): ?>
                        <?php foreach ($affiliated_properties as $prop): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo DIRPAGE; ?>property/<?php echo (int) $prop['property_id']; ?>" target="_blank">
                                        <?php echo htmlspecialchars($prop['title']); ?>
                                    </a>
                                    <?php if (!empty($prop['location'])): ?>
                                        <small class="dashboard-inline-note"><?php echo htmlspecialchars($prop['location']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($prop['owner_name'] ?? '–'); ?>
                                    <?php if (!empty($prop['owner_phone'])): ?>
                                        <small class="dashboard-inline-note"><?php echo htmlspecialchars($prop['owner_phone']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="dashboard-cell-nowrap"><?php echo number_format((float) $prop['price'], 0, ',', '.'); ?> Kz</td>
                                <td><?php echo (int) ($prop['referral_count'] ?? 0); ?></td>
                                <td>
                                    <?php
                                        $pStatus = $prop['property_status'] ?? '';
                                        $pLabel  = ['disponivel' => 'Disponível', 'vendido' => 'Vendido', 'alugado' => 'Alugado', 'pendente' => 'Pendente', 'em_analise' => 'Em análise', 'rejeitado' => 'Rejeitado'][$pStatus] ?? ucfirst($pStatus);
                                        $pTone   = ['disponivel' => 'pago', 'vendido' => 'cancelado', 'alugado' => 'cancelado'][$pStatus] ?? 'pendente';
                                    ?>
                                    <span class="commission-status-badge commission-status-<?php echo $pTone; ?>"><?php echo $pLabel; ?></span>
                                </td>
                                <td>
                                    <div class="referral-link-wrap">
                                        <input type="text"
                                               id="ref-aff-<?php echo (int) $prop['property_id']; ?>"
                                               readonly
                                               value="<?php echo DIRPAGE; ?>property/<?php echo (int) $prop['property_id']; ?>?ref=<?php echo htmlspecialchars($affiliate_code ?? ($user['affiliate_code'] ?? '')); ?>"
                                               class="referral-link-input">
                                        <button type="button"
                                                class="btn-secondary referral-copy-btn"
                                                data-copy-target="ref-aff-<?php echo (int) $prop['property_id']; ?>"
                                                title="Copiar link">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state-content">
                                    <i class="fa fa-link"></i>
                                    <p>Nenhum imóvel afiliado activo. Navegue pelos imóveis disponíveis e solicite afiliação.</p>
                                    <a href="<?php echo DIRPAGE; ?>properties" class="btn-primary" style="margin-top:12px;">Ver Imóveis</a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

    <?php else: ?>

        <!-- ── COMISSÕES TAB ── -->
        <div class="dashboard-overview-grid dashboard-overview-grid-tight dashboard-kpi-section">
            <div class="kpi-card">
                <div class="kpi-label">Total gerado</div>
                <div class="kpi-value"><?php echo number_format((float) ($summary['earned_total'] ?? 0), 0, ',', '.'); ?> Kz</div>
            </div>
            <div class="kpi-card kpi-green">
                <div class="kpi-label">Já recebido</div>
                <div class="kpi-value"><?php echo number_format((float) ($summary['earned_paid'] ?? 0), 0, ',', '.'); ?> Kz</div>
            </div>
            <div class="kpi-card kpi-yellow">
                <div class="kpi-label">Pendente</div>
                <div class="kpi-value"><?php echo number_format((float) ($summary['earned_pending'] ?? 0), 0, ',', '.'); ?> Kz</div>
            </div>
            <div class="kpi-card kpi-blue">
                <div class="kpi-label">Este mês</div>
                <div class="kpi-value"><?php echo number_format((float) ($summary['earned_this_month'] ?? 0), 0, ',', '.'); ?> Kz</div>
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
                        <th>Estado</th>
                        <th>Referência</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($commissions)): ?>
                        <?php foreach ($commissions as $commission): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($commission['title']); ?></td>
                                <td>
                                    <?php echo number_format((float) ($commission['affiliate_amount'] ?? 0), 0, ',', '.'); ?> Kz
                                    <span class="dashboard-inline-note">(<?php echo number_format((float) ($commission['affiliate_pct'] ?? 0), 2, ',', '.'); ?>%)</span>
                                </td>
                                <td><?php echo number_format((float) ($commission['system_amount'] ?? 0), 0, ',', '.'); ?> Kz</td>
                                <td><?php echo number_format((float) $commission['amount'], 0, ',', '.'); ?> Kz</td>
                                <td>
                                    <?php
                                        $stMap = ['pendente' => ['Pendente', 'pendente'], 'pending' => ['Pendente', 'pendente'], 'pago' => ['Pago', 'pago'], 'paid' => ['Pago', 'pago'], 'cancelado' => ['Cancelado', 'cancelado'], 'cancelled' => ['Cancelado', 'cancelado']];
                                        [$stLabel, $stKey] = $stMap[$commission['status'] ?? 'pendente'] ?? ['–', 'pendente'];
                                    ?>
                                    <span class="commission-status-badge commission-status-<?php echo $stKey; ?>"><?php echo $stLabel; ?></span>
                                </td>
                                <td class="dashboard-inline-note"><?php echo htmlspecialchars($commission['payment_reference'] ?? '–'); ?></td>
                                <td class="dashboard-cell-nowrap"><?php echo date('d/m/Y', strtotime($commission['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state-content">
                                    <i class="fa fa-money"></i>
                                    <p>Nenhuma comissão registada ainda. Promova imóveis para começar a ganhar.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

    <?php endif; ?>
</div>
