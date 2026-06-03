<?php
/** @var array $user */
/** @var array $pendingCommissions */
/** @var array $historyCommissions */
/** @var array $historyCounts */
/** @var string $activeTab */
/** @var int $pendingCount */
/** @var string $csrfField */

$pendingCommissions = is_array($pendingCommissions ?? null) ? $pendingCommissions : [];
$historyCommissions = is_array($historyCommissions ?? null) ? $historyCommissions : [];
$historyCounts = is_array($historyCounts ?? null) ? $historyCounts : [];
$activeTab = in_array($activeTab ?? '', ['pendentes', 'pago', 'cancelado'], true) ? $activeTab : 'pendentes';
$pendingCount = (int) ($pendingCount ?? count($pendingCommissions));

$commissionPaymentsTabUrl = static function (string $tab) use ($activeTab): string {
    $base = DIRPAGE . 'dashboard/commissionPayments';
    $params = [];
    if ($tab !== 'pendentes') {
        $params['tab'] = $tab;
    }
    foreach (['error', 'success'] as $flashKey) {
        $flash = trim((string) ($_GET[$flashKey] ?? ''));
        if ($flash !== '' && $tab === $activeTab) {
            $params[$flashKey] = $flash;
        }
    }

    return empty($params) ? $base : $base . '?' . http_build_query($params);
};
?>

<div class="container dashboard-view commission-payments-view">
    <section class="dashboard-view-hero compact">
        <div>
            <span class="dashboard-hero-kicker">Financeiro</span>
            <h1>Pagar comissões</h1>
            <p>Regularize as comissões dos fechos comerciais dos seus imóveis. Envie o comprovativo para validação da equipa.</p>
        </div>
    </section>

    <?php
        $commissionError = trim((string) ($_GET['error'] ?? ''));
        $hideOverdueError = $commissionError !== ''
            && Src\classes\ClassAuth::check()
            && App\model\Commission::getOverdueBlockReason((int) (Src\classes\ClassAuth::user()['id'] ?? 0)) !== null;
    ?>
    <?php if ($commissionError !== '' && !$hideOverdueError): ?>
        <div class="sub-feedback error"><?php echo htmlspecialchars($commissionError); ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['success'])): ?>
        <div class="sub-feedback success"><?php echo htmlspecialchars((string) $_GET['success']); ?></div>
    <?php endif; ?>

    <div class="dashboard-tab-nav commission-payments-tab-nav">
        <a href="<?php echo htmlspecialchars($commissionPaymentsTabUrl('pendentes')); ?>"
           class="dashboard-tab-link <?php echo $activeTab === 'pendentes' ? 'is-active' : ''; ?>">
            <i class="fa fa-clock-o"></i> Pendentes
            <?php if ($pendingCount > 0): ?>
                <span class="dashboard-tab-badge"><?php echo $pendingCount; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo htmlspecialchars($commissionPaymentsTabUrl('pago')); ?>"
           class="dashboard-tab-link <?php echo $activeTab === 'pago' ? 'is-active' : ''; ?>">
            <i class="fa fa-check-circle"></i> Pagas
            <?php if (($historyCounts['pago'] ?? 0) > 0): ?>
                <span class="dashboard-tab-badge"><?php echo (int) $historyCounts['pago']; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo htmlspecialchars($commissionPaymentsTabUrl('cancelado')); ?>"
           class="dashboard-tab-link <?php echo $activeTab === 'cancelado' ? 'is-active' : ''; ?>">
            <i class="fa fa-ban"></i> Canceladas
            <?php if (($historyCounts['cancelado'] ?? 0) > 0): ?>
                <span class="dashboard-tab-badge"><?php echo (int) $historyCounts['cancelado']; ?></span>
            <?php endif; ?>
        </a>
    </div>

    <?php if ($activeTab === 'pendentes'): ?>
        <div class="dashboard-module-card">
            <div class="dashboard-module-head compact">
                <div>
                    <span class="dashboard-module-kicker">Pendentes</span>
                    <h3>Comissões a regularizar</h3>
                </div>
            </div>

            <?php if (!empty($pendingCommissions)): ?>
                <div class="commission-payments-list">
                    <?php foreach ($pendingCommissions as $commission): ?>
                        <?php
                            $commissionId = (int) ($commission['id'] ?? 0);
                            $amount = (float) ($commission['amount'] ?? 0);
                            $dueAt = (string) ($commission['due_at'] ?? '');
                            $dueLabel = $dueAt !== '' ? date('d/m/Y H:i', strtotime($dueAt)) : '—';
                            $isOverdue = $dueAt !== '' && strtotime($dueAt) < time();
                            $ownerPayStatus = App\model\Commission::resolveOwnerPaymentStatus($commission);
                            $submitted = $ownerPayStatus === App\model\Commission::OWNER_PAYMENT_ENVIADO;
                            $rejected = $ownerPayStatus === App\model\Commission::OWNER_PAYMENT_REJEITADO;
                            $rejectReason = trim((string) ($commission['owner_payment_rejection_reason'] ?? ''));
                        ?>
                        <article class="commission-payments-item <?php echo $isOverdue ? 'is-overdue' : ''; ?> <?php echo $submitted ? 'is-awaiting' : ''; ?> <?php echo $rejected ? 'is-rejected' : ''; ?>">
                            <div class="commission-payments-item-main">
                                <h4>
                                    <a href="<?php echo DIRPAGE; ?>property/<?php echo (int) ($commission['property_id'] ?? 0); ?>" class="table-name-link">
                                        <?php echo htmlspecialchars((string) ($commission['property_title'] ?? 'Imóvel')); ?>
                                    </a>
                                </h4>
                                <p class="commission-payments-amount">
                                    Pague <strong><?php echo number_format($amount, 0, ',', '.'); ?> Kz</strong>
                                    até <strong><?php echo htmlspecialchars($dueLabel); ?></strong>
                                </p>
                                <?php if ((float) ($commission['system_amount'] ?? 0) > 0 || (float) ($commission['affiliate_amount'] ?? 0) > 0): ?>
                                    <small class="dashboard-inline-note">
                                        Plataforma: <?php echo number_format((float) ($commission['system_amount'] ?? 0), 0, ',', '.'); ?> Kz
                                        <?php if ((float) ($commission['affiliate_amount'] ?? 0) > 0): ?>
                                            · Afiliado: <?php echo number_format((float) ($commission['affiliate_amount'] ?? 0), 0, ',', '.'); ?> Kz
                                        <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="commission-payments-item-actions">
                                <?php if ($submitted): ?>
                                    <span class="request-status-badge request-status-em_analise">Aguardando validação</span>
                                <?php elseif ($rejected): ?>
                                    <span class="request-status-badge request-status-expirado">Comprovativo rejeitado</span>
                                    <?php if ($rejectReason !== ''): ?>
                                        <small class="dashboard-inline-note"><?php echo htmlspecialchars($rejectReason); ?></small>
                                    <?php endif; ?>
                                    <a href="<?php echo DIRPAGE; ?>dashboard/commissionPayment/<?php echo $commissionId; ?>" class="btn-primary">Reenviar comprovativo</a>
                                <?php elseif ($isOverdue): ?>
                                    <span class="request-status-badge request-status-expirado">Vencida</span>
                                    <a href="<?php echo DIRPAGE; ?>dashboard/commissionPayment/<?php echo $commissionId; ?>" class="btn-primary">Enviar comprovativo</a>
                                <?php else: ?>
                                    <span class="request-status-badge request-status-pendente">Pendente</span>
                                    <a href="<?php echo DIRPAGE; ?>dashboard/commissionPayment/<?php echo $commissionId; ?>" class="btn-primary">Pagar agora</a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="dashboard-empty-copy">Não tem comissões pendentes de pagamento.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php
            $historyTitle = $activeTab === 'pago' ? 'Comissões pagas' : 'Comissões canceladas';
            $historyKicker = $activeTab === 'pago' ? 'Liquidadas' : 'Anuladas';
            $emptyCopy = $activeTab === 'pago'
                ? 'Ainda não tem comissões pagas registadas.'
                : 'Não tem comissões canceladas.';
        ?>
        <div class="dashboard-module-card">
            <div class="dashboard-module-head compact">
                <div>
                    <span class="dashboard-module-kicker"><?php echo htmlspecialchars($historyKicker); ?></span>
                    <h3><?php echo htmlspecialchars($historyTitle); ?></h3>
                </div>
            </div>

            <?php if (!empty($historyCommissions)): ?>
                <div class="dashboard-table-wrap commission-payments-history-wrap">
                    <table class="dashboard-table commissions-table commission-payments-history-table">
                        <thead>
                            <tr>
                                <th>Imóvel</th>
                                <th>Valor</th>
                                <th>Estado</th>
                                <th>Comprovativo</th>
                                <th>Referência</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historyCommissions as $commission): ?>
                                <?php
                                    $amount = (float) ($commission['amount'] ?? 0);
                                    $statusKey = (string) ($commission['status'] ?? $activeTab);
                                    $ownerPayStatus = App\model\Commission::resolveOwnerPaymentStatus($commission);
                                    $ownerRef = trim((string) ($commission['owner_payment_reference'] ?? ''));
                                    if ($ownerRef === '') {
                                        $ownerRef = trim((string) ($commission['payment_reference'] ?? ''));
                                    }
                                    $paidAt = (string) ($commission['paid_at'] ?? '');
                                    $validatedAt = (string) ($commission['owner_payment_validated_at'] ?? '');
                                    $createdAt = (string) ($commission['created_at'] ?? '');
                                    $dateRaw = $paidAt !== '' ? $paidAt : ($validatedAt !== '' ? $validatedAt : $createdAt);
                                    $dateLabel = $dateRaw !== '' ? date('d/m/Y H:i', strtotime($dateRaw)) : '—';
                                ?>
                                <tr class="commission-payments-history-row">
                                    <td data-label="Imóvel" class="col-stack">
                                        <a href="<?php echo DIRPAGE; ?>property/<?php echo (int) ($commission['property_id'] ?? 0); ?>" class="table-name-link">
                                            <?php echo htmlspecialchars((string) ($commission['property_title'] ?? 'Imóvel')); ?>
                                        </a>
                                        <?php if ((float) ($commission['system_amount'] ?? 0) > 0 || (float) ($commission['affiliate_amount'] ?? 0) > 0): ?>
                                            <small class="dashboard-inline-note">
                                                Plataforma: <?php echo number_format((float) ($commission['system_amount'] ?? 0), 0, ',', '.'); ?> Kz
                                                <?php if ((float) ($commission['affiliate_amount'] ?? 0) > 0): ?>
                                                    · Afiliado: <?php echo number_format((float) ($commission['affiliate_amount'] ?? 0), 0, ',', '.'); ?> Kz
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="dashboard-cell-nowrap" data-label="Valor">
                                        <strong><?php echo number_format($amount, 0, ',', '.'); ?> Kz</strong>
                                    </td>
                                    <td data-label="Estado" class="col-stack">
                                        <span class="commission-status-badge commission-status-<?php echo htmlspecialchars($statusKey); ?>">
                                            <?php echo htmlspecialchars(App\model\Commission::statusLabel($statusKey)); ?>
                                        </span>
                                    </td>
                                    <td class="dashboard-inline-note" data-label="Comprovativo">
                                        <?php echo htmlspecialchars(App\model\Commission::ownerPaymentStatusLabel($ownerPayStatus)); ?>
                                    </td>
                                    <td class="dashboard-inline-note" data-label="Referência"><?php echo $ownerRef !== '' ? htmlspecialchars($ownerRef) : '—'; ?></td>
                                    <td class="dashboard-cell-nowrap" data-label="Data"><?php echo htmlspecialchars($dateLabel); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="dashboard-empty-copy"><?php echo htmlspecialchars($emptyCopy); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
