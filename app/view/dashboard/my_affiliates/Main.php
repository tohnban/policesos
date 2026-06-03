<div class="container dashboard-view">
    <section class="dashboard-view-hero compact">
        <div>
            <span class="dashboard-hero-kicker">Afiliados</span>
            <h1>Meus Afiliados</h1>
            <p>Promotores aprovados e pendentes para os seus imóveis.</p>
        </div>
    </section>

    <?php
        // Group by property
        $byProperty = [];
        foreach ($affiliates as $row) {
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
                <p>Ainda não há promotores nos seus imóveis. Quando alguém solicitar afiliação, aparecerá aqui.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($byProperty as $property): ?>
            <div class="dashboard-module-card" style="margin-bottom:24px;">
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

                <div class="dashboard-table-wrap">
                <table class="commissions-table">
                    <thead>
                        <tr>
                            <th>Promotor</th>
                            <th>Contacto</th>
                            <th>Estado</th>
                            <th>Indicações</th>
                            <th>Comissões geradas</th>
                            <th>Código</th>
                            <th>Data do pedido</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($property['rows'] as $aff): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($aff['affiliate_name']); ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($aff['affiliate_email'])): ?>
                                        <span><?php echo htmlspecialchars($aff['affiliate_email']); ?></span><br>
                                    <?php endif; ?>
                                    <?php if (!empty($aff['affiliate_phone'])): ?>
                                        <small class="dashboard-inline-note"><?php echo htmlspecialchars($aff['affiliate_phone']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $stMap2 = ['ativo' => ['Aprovado', 'pago'], 'pendente' => ['Pendente', 'pendente'], 'rejeitado' => ['Rejeitado', 'cancelado']];
                                        [$stLabel2, $stKey2] = $stMap2[$aff['affiliate_status'] ?? 'pendente'] ?? ['–', 'pendente'];
                                    ?>
                                    <span class="commission-status-badge commission-status-<?php echo $stKey2; ?>"><?php echo $stLabel2; ?></span>
                                    <?php if (!empty($aff['approved_at'])): ?>
                                        <small class="dashboard-inline-note">desde <?php echo date('d/m/Y', strtotime($aff['approved_at'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo (int) ($aff['referral_count'] ?? 0); ?></td>
                                <td class="dashboard-cell-nowrap"><?php echo number_format((float) ($aff['commission_total'] ?? 0), 0, ',', '.'); ?> Kz</td>
                                <td>
                                    <code style="font-size:12px;"><?php echo htmlspecialchars($aff['affiliate_code'] ?? '–'); ?></code>
                                </td>
                                <td class="dashboard-cell-nowrap dashboard-inline-note">
                                    <?php echo !empty($aff['requested_at']) ? date('d/m/Y', strtotime($aff['requested_at'])) : '–'; ?>
                                </td>
                                <td>
                                    <?php if ($aff['affiliate_status'] === 'pendente'): ?>
                                        <form action="<?php echo DIRPAGE; ?>request/approveAffiliate/<?php echo (int) $aff['affiliate_request_id']; ?>" method="POST" style="display:inline;">
                                            <?php echo $csrfField; ?>
                                            <button type="submit" class="btn-primary" style="padding:4px 10px;font-size:13px;">Aprovar</button>
                                        </form>
                                        <form action="<?php echo DIRPAGE; ?>request/rejectAffiliate/<?php echo (int) $aff['affiliate_request_id']; ?>" method="POST" style="display:inline;margin-left:4px;">
                                            <?php echo $csrfField; ?>
                                            <button type="submit" class="btn-secondary" style="padding:4px 10px;font-size:13px;">Rejeitar</button>
                                        </form>
                                    <?php elseif ($aff['affiliate_status'] === 'ativo'): ?>
                                        <span class="dashboard-inline-note">–</span>
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
    <?php endif; ?>
</div>
