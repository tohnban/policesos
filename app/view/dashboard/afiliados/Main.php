<?php
$activeTab      = $active_tab ?? 'my_affiliates';
$isAffiliate    = !empty($user['is_affiliate']);
$hasProperties  = !empty($has_properties);
$page           = max(1, (int) ($page ?? 1));
$totalPages     = max(1, (int) ($totalPages ?? 1));
$commissionsTotal = (int) ($commissionsTotal ?? count($commissions ?? []));
$myAffiliatesTotal = (int) ($myAffiliatesTotal ?? count($my_affiliates ?? []));
$isRequestsTab  = $activeTab === 'affiliate_requests';

function afiliadosTabUrl(string $tab, int $targetPage = 1): string {
    $params = ['tab' => $tab];
    if ($targetPage > 1) {
        $params['page'] = $targetPage;
    }
    return '?' . http_build_query($params);
}
?>
<div class="container dashboard-view afiliados-dashboard-view">
    <section class="dashboard-view-hero compact">
        <div>
            <span class="dashboard-hero-kicker">Afiliação</span>
            <h1>Afiliados</h1>
            <p>Gestão completa do programa de afiliação — as suas indicações, comissões e os promotores dos seus imóveis.</p>
        </div>
    </section>

    <?php if (!empty($_GET['error'])): ?>
        <div class="sub-feedback error"><?php echo htmlspecialchars((string) $_GET['error']); ?></div>
    <?php elseif (!empty($_GET['success'])): ?>
        <div class="sub-feedback success"><?php echo htmlspecialchars((string) $_GET['success']); ?></div>
    <?php endif; ?>

    <div class="dashboard-tab-nav afiliados-tab-nav">
        <?php if ($isAffiliate): ?>
            <a href="<?php echo afiliadosTabUrl('referrals'); ?>"
               class="dashboard-tab-link <?php echo $activeTab === 'referrals' ? 'is-active' : ''; ?>">
                <i class="fa fa-link"></i> Minhas Indicações
            </a>
            <a href="<?php echo afiliadosTabUrl('commissions'); ?>"
               class="dashboard-tab-link <?php echo $activeTab === 'commissions' ? 'is-active' : ''; ?>">
                <i class="fa fa-money"></i> Comissões
            </a>
        <?php endif; ?>
        <?php if ($hasProperties): ?>
          <a href="<?php echo afiliadosTabUrl('affiliate_requests'); ?>"
              class="dashboard-tab-link <?php echo $activeTab === 'affiliate_requests' ? 'is-active' : ''; ?>">
                <i class="fa fa-inbox"></i> Solicitações
          </a>
        <a href="<?php echo afiliadosTabUrl('my_affiliates'); ?>"
           class="dashboard-tab-link <?php echo $activeTab === 'my_affiliates' ? 'is-active' : ''; ?>">
            <i class="fa fa-users"></i> Meus Afiliados
        </a>
        <?php endif; ?>
    </div>

    <?php if ($activeTab === 'referrals' && $isAffiliate): ?>

        <!-- ── MINHAS INDICAÇÕES ── -->
        <div class="dashboard-module-card dashboard-kpi-section">
            <div class="dashboard-module-head compact">
                <div>
                    <span class="dashboard-module-kicker">Como funciona</span>
                    <h3>Programa de Indicação</h3>
                </div>
            </div>
            <p class="dashboard-inline-note">Os links abaixo só geram comissão para imóveis em que a sua afiliação foi aprovada pelo proprietário. Partilhe o link; quando o negócio fechar, a comissão é lançada automaticamente.</p>
        </div>

        <div class="dashboard-module-card">
            <div class="dashboard-module-head compact">
                <div>
                    <span class="dashboard-module-kicker">Afiliações activas</span>
                    <h3>Imóveis que está a indicar</h3>
                </div>
            </div>

            <div class="dashboard-table-wrap afiliados-table-wrap">
            <table class="commissions-table afiliados-table">
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
                            <tr class="afiliados-row">
                                <td data-label="Imóvel">
                                    <a href="<?php echo DIRPAGE; ?>property/<?php echo (int) $prop['property_id']; ?>" class="table-name-link" target="_blank" rel="noopener">
                                        <?php echo htmlspecialchars($prop['title']); ?>
                                    </a>
                                    <?php if (!empty($prop['location'])): ?>
                                        <br><small class="dashboard-inline-note"><?php echo htmlspecialchars($prop['location']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Proprietário">
                                    <?php
                                        $ownerHandle = htmlspecialchars(Src\classes\UserDisplay::publicHandleFromRow($prop, 'owner_username', 'owner_name', '–'));
                                    ?>
                                    <?php if (!empty($prop['owner_id'])): ?>
                                        <a href="<?php echo DIRPAGE; ?>property/owner/<?php echo (int) $prop['owner_id']; ?>" class="table-name-link">
                                            <?php echo $ownerHandle; ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo $ownerHandle; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($prop['owner_phone'])): ?>
                                        <br><small class="dashboard-inline-note"><?php echo htmlspecialchars($prop['owner_phone']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="dashboard-cell-nowrap" data-label="Preço"><?php echo number_format((float) $prop['price'], 0, ',', '.'); ?> Kz</td>
                                <td data-label="Indicações"><?php echo (int) ($prop['referral_count'] ?? 0); ?></td>
                                <td data-label="Estado">
                                    <?php
                                        $pStatus = $prop['property_status'] ?? '';
                                        $pLabel  = ['disponivel' => 'Disponível', 'vendido' => 'Vendido', 'alugado' => 'Alugado', 'pendente' => 'Pendente', 'em_analise' => 'Em análise', 'rejeitado' => 'Rejeitado'][$pStatus] ?? ucfirst($pStatus);
                                        $pTone   = ['disponivel' => 'pago', 'vendido' => 'cancelado', 'alugado' => 'cancelado'][$pStatus] ?? 'pendente';
                                    ?>
                                    <span class="commission-status-badge commission-status-<?php echo $pTone; ?>"><?php echo $pLabel; ?></span>
                                </td>
                                <td data-label="Link" class="col-referral-link">
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
                        <tr class="afiliados-empty-row">
                            <td colspan="6">
                                <div class="empty-state-content">
                                    <i class="fa fa-link"></i>
                                    <p>Nenhum imóvel afiliado activo. Navegue pelos imóveis e solicite afiliação.</p>
                                    <a href="<?php echo DIRPAGE; ?>properties" class="btn-primary afiliados-empty-cta">Ver Imóveis</a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

    <?php elseif ($activeTab === 'commissions' && $isAffiliate): ?>

        <!-- ── COMISSÕES ── -->
        <div class="dashboard-overview-grid dashboard-overview-grid-tight dashboard-kpi-section afiliados-kpis">
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

            <div class="dashboard-table-wrap afiliados-table-wrap">
            <table class="commissions-table afiliados-table">
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
                            <tr class="afiliados-row">
                                <td data-label="Imóvel"><?php echo htmlspecialchars($commission['title']); ?></td>
                                <td data-label="Meu valor">
                                    <?php echo number_format((float) ($commission['affiliate_amount'] ?? 0), 0, ',', '.'); ?> Kz
                                    <span class="dashboard-inline-note">(<?php echo number_format((float) ($commission['affiliate_pct'] ?? 0), 2, ',', '.'); ?>%)</span>
                                </td>
                                <td data-label="Sistema"><?php echo number_format((float) ($commission['system_amount'] ?? 0), 0, ',', '.'); ?> Kz</td>
                                <td data-label="Total"><?php echo number_format((float) $commission['amount'], 0, ',', '.'); ?> Kz</td>
                                <td data-label="Estado">
                                    <?php
                                        $affiliateSt = App\model\Commission::affiliateDisplayStatus($commission);
                                        $stLabel = App\model\Commission::affiliateDisplayStatusLabel($affiliateSt);
                                        $stKey = in_array($affiliateSt, ['pago', 'pendente', 'aguardando_pagamento', 'cancelado'], true)
                                            ? ($affiliateSt === 'aguardando_pagamento' ? 'em_analise' : $affiliateSt)
                                            : 'pendente';
                                    ?>
                                    <span class="commission-status-badge commission-status-<?php echo htmlspecialchars($stKey); ?>"><?php echo htmlspecialchars($stLabel); ?></span>
                                </td>
                                <td class="dashboard-inline-note" data-label="Referência"><?php echo htmlspecialchars($commission['payment_reference'] ?? '–'); ?></td>
                                <td class="dashboard-cell-nowrap" data-label="Data"><?php echo date('d/m/Y', strtotime($commission['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="afiliados-empty-row">
                            <td colspan="7">
                                <div class="empty-state-content">
                                    <i class="fa fa-money"></i>
                                    <p>Nenhuma comissão registada ainda. Indique imóveis para começar a ganhar.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>

            <?php if ($commissionsTotal > 0): ?>
                <p class="dashboard-pagination-copy afiliados-pagination-copy">
                    A mostrar <?php echo count($commissions ?? []); ?> de <?php echo $commissionsTotal; ?>.
                </p>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <div class="dashboard-pagination-wrap dashboard-pagination-wrap-start">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo afiliadosTabUrl('commissions', $page - 1); ?>" class="btn-secondary">&larr; Anterior</a>
                    <?php endif; ?>
                    <span class="dashboard-pagination-copy">Página <?php echo $page; ?> de <?php echo $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo afiliadosTabUrl('commissions', $page + 1); ?>" class="btn-secondary">Próxima &rarr;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <!-- ── SOLICITAÇÕES / MEUS AFILIADOS ── -->
        <?php
            $byProperty = [];
            foreach (($my_affiliates ?? []) as $row) {
                $pid = (int) $row['property_id'];
                if (!isset($byProperty[$pid])) {
                    $byProperty[$pid] = [
                        'id'       => $pid,
                        'title'    => $row['property_title'],
                        'price'    => $row['property_price'],
                        'location' => $row['property_location'],
                        'status'   => $row['property_status'],
                        'rows'     => [],
                    ];
                }
                $byProperty[$pid]['rows'][] = $row;
            }
        ?>

        <?php if (empty($byProperty)): ?>
            <div class="dashboard-module-card">
                <div class="empty-state-content">
                    <i class="fa fa-users"></i>
                    <?php if ($isRequestsTab): ?>
                        <p>Sem solicitações pendentes no momento. Quando houver novos pedidos, eles aparecerão aqui.</p>
                    <?php else: ?>
                        <p>Ainda não há afiliados ligados aos seus imóveis. Os pedidos aprovados e o histórico de afiliações aparecem aqui.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php
                $affiliateColLabel = $isRequestsTab ? 'Promotor' : 'Afiliado';
            ?>
            <?php foreach ($byProperty as $property): ?>
                <div class="dashboard-module-card afiliados-property-group">
                    <div class="dashboard-module-head compact">
                        <div>
                            <span class="dashboard-module-kicker">
                                <?php
                                    $pStatus = $property['status'] ?? '';
                                    $pLabel  = ['disponivel' => 'Disponível', 'vendido' => 'Vendido', 'alugado' => 'Alugado', 'pendente' => 'Pendente', 'em_analise' => 'Em análise', 'rejeitado' => 'Rejeitado'][$pStatus] ?? ucfirst($pStatus);
                                    $pTone   = ['disponivel' => 'pago', 'vendido' => 'cancelado', 'alugado' => 'cancelado'][$pStatus] ?? 'pendente';
                                ?>
                                <span class="commission-status-badge commission-status-<?php echo $pTone; ?>"><?php echo $pLabel; ?></span>
                            </span>
                            <h3>
                                <a href="<?php echo DIRPAGE; ?>property/<?php echo (int) $property['id']; ?>" target="_blank">
                                    <?php echo htmlspecialchars($property['title']); ?>
                                </a>
                            </h3>
                            <?php if (!empty($property['location'])): ?>
                                <p class="dashboard-inline-note"><?php echo htmlspecialchars($property['location']); ?> &middot; <?php echo number_format((float) $property['price'], 0, ',', '.'); ?> Kz</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="dashboard-table-wrap afiliados-table-wrap">
                        <table class="commissions-table afiliados-table">
                            <thead>
                                <tr>
                                    <th><?php echo htmlspecialchars($affiliateColLabel); ?></th>
                                    <th>Contacto</th>
                                    <th>Estado</th>
                                    <th>Indicações</th>
                                    <?php if (!$isRequestsTab): ?>
                                        <th>Comissões geradas</th>
                                        <th>Código</th>
                                    <?php endif; ?>
                                    <th>Pedido em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($property['rows'] as $aff): ?>
                                    <tr class="afiliados-row">
                                        <td data-label="<?php echo htmlspecialchars($affiliateColLabel); ?>">
                                            <?php
                                                $affiliateHandle = htmlspecialchars(Src\classes\UserDisplay::publicHandleFromRow($aff, 'affiliate_username', 'affiliate_name', '–'));
                                            ?>
                                            <?php if (!empty($aff['affiliate_user_id'])): ?>
                                                <strong><a href="<?php echo DIRPAGE; ?>property/owner/<?php echo (int) $aff['affiliate_user_id']; ?>" class="table-name-link"><?php echo $affiliateHandle; ?></a></strong>
                                            <?php else: ?>
                                                <strong><?php echo $affiliateHandle; ?></strong>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Contacto">
                                            <?php if (!empty($aff['affiliate_email'])): ?>
                                                <?php echo htmlspecialchars($aff['affiliate_email']); ?><br>
                                            <?php endif; ?>
                                            <?php if (!empty($aff['affiliate_phone'])): ?>
                                                <small class="dashboard-inline-note"><?php echo htmlspecialchars($aff['affiliate_phone']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Estado">
                                            <?php
                                                $stMap2 = ['ativo' => ['Aprovado', 'pago'], 'pendente' => ['Pendente', 'pendente'], 'rejeitado' => ['Rejeitado', 'cancelado']];
                                                [$stLabel2, $stKey2] = $stMap2[$aff['affiliate_status'] ?? 'pendente'] ?? ['–', 'pendente'];
                                            ?>
                                            <span class="commission-status-badge commission-status-<?php echo $stKey2; ?>"><?php echo $stLabel2; ?></span>
                                            <?php if (!empty($aff['approved_at'])): ?>
                                                <br><small class="dashboard-inline-note">desde <?php echo date('d/m/Y', strtotime($aff['approved_at'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Indicações"><?php echo (int) ($aff['referral_count'] ?? 0); ?></td>
                                        <?php if (!$isRequestsTab): ?>
                                            <td class="dashboard-cell-nowrap" data-label="Comissões"><?php echo number_format((float) ($aff['commission_total'] ?? 0), 0, ',', '.'); ?> Kz</td>
                                            <td data-label="Código"><code class="afiliados-code"><?php echo htmlspecialchars($aff['affiliate_code'] ?? '–'); ?></code></td>
                                        <?php endif; ?>
                                        <td class="dashboard-cell-nowrap dashboard-inline-note" data-label="Pedido em">
                                            <?php echo !empty($aff['requested_at']) ? date('d/m/Y', strtotime($aff['requested_at'])) : '–'; ?>
                                        </td>
                                        <td class="col-actions" data-label="Ações">
                                            <?php if ($aff['affiliate_status'] === 'pendente'): ?>
                                                <div class="afiliados-row-actions">
                                                    <form action="<?php echo DIRPAGE; ?>request/approveAffiliate/<?php echo (int) $aff['affiliate_request_id']; ?>" method="POST">
                                                        <?php echo $csrfField; ?>
                                                        <button type="submit" class="btn-primary">Aprovar</button>
                                                    </form>
                                                    <form action="<?php echo DIRPAGE; ?>request/rejectAffiliate/<?php echo (int) $aff['affiliate_request_id']; ?>" method="POST">
                                                        <?php echo $csrfField; ?>
                                                        <button type="submit" class="btn-secondary">Rejeitar</button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <span class="dashboard-inline-note">–</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($myAffiliatesTotal > 0): ?>
                <p class="dashboard-pagination-copy afiliados-pagination-copy">
                    <?php if ($isRequestsTab): ?>
                        A mostrar <?php echo count($my_affiliates ?? []); ?> pedido(s) pendente(s) de <?php echo $myAffiliatesTotal; ?>.
                    <?php else: ?>
                        A mostrar <?php echo count($my_affiliates ?? []); ?> afiliação(ões) de <?php echo $myAffiliatesTotal; ?>.
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <div class="dashboard-pagination-wrap dashboard-pagination-wrap-start">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo afiliadosTabUrl($activeTab, $page - 1); ?>" class="btn-secondary">&larr; Anterior</a>
                    <?php endif; ?>
                    <span class="dashboard-pagination-copy">Página <?php echo $page; ?> de <?php echo $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo afiliadosTabUrl($activeTab, $page + 1); ?>" class="btn-secondary">Próxima &rarr;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    <?php endif; ?>
</div>
