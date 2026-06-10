<?php

use Src\classes\FeedGrouping;

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

$pendingGroups = FeedGrouping::byDueUrgency($pendingCommissions, 'due_at');
?>

<div class="container dashboard-view notification-inbox-view commission-payments-view payment-account-feed-view">
    <section class="notification-inbox-hero">
        <div class="notification-inbox-hero-main">
            <h1>Comissões a pagar</h1>
            <p class="notification-inbox-hero-meta">
                <span>Regularize os valores dos negócios fechados</span>
                <?php if ($pendingCount > 0): ?>
                    <span class="notification-feed-dot" aria-hidden="true">·</span>
                    <span class="notification-inbox-unread-pill"><?php echo (int) $pendingCount; ?> por tratar</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="notification-inbox-hero-actions">
            <a href="<?php echo DIRPAGE; ?>dashboard/paymentHistory" class="notification-inbox-text-btn">Ver movimentos</a>
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

    <div class="requests-scope-navigation commission-payments-scope">
        <div class="requests-scope-pills">
            <a href="<?php echo htmlspecialchars($commissionPaymentsTabUrl('pendentes')); ?>"
               class="requests-scope-pill <?php echo $activeTab === 'pendentes' ? 'is-active' : ''; ?>"
               aria-current="<?php echo $activeTab === 'pendentes' ? 'page' : 'false'; ?>">
                <i class="fa fa-clock-o" aria-hidden="true"></i>
                <span>Pendentes</span>
                <?php if ($pendingCount > 0): ?>
                    <span class="requests-scope-pill-badge"><?php echo (int) $pendingCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo htmlspecialchars($commissionPaymentsTabUrl('pago')); ?>"
               class="requests-scope-pill <?php echo $activeTab === 'pago' ? 'is-active' : ''; ?>"
               aria-current="<?php echo $activeTab === 'pago' ? 'page' : 'false'; ?>">
                <i class="fa fa-check-circle" aria-hidden="true"></i>
                <span>Pagas</span>
            </a>
            <a href="<?php echo htmlspecialchars($commissionPaymentsTabUrl('cancelado')); ?>"
               class="requests-scope-pill <?php echo $activeTab === 'cancelado' ? 'is-active' : ''; ?>"
               aria-current="<?php echo $activeTab === 'cancelado' ? 'page' : 'false'; ?>">
                <i class="fa fa-ban" aria-hidden="true"></i>
                <span>Canceladas</span>
            </a>
        </div>
    </div>

    <?php if ($activeTab === 'pendentes'): ?>
        <div class="notification-inbox-panel payment-account-feed-panel">
            <?php
                $shellHasItems = !empty($pendingCommissions);
                $shellEmptyIcon = 'fa-money';
                $shellEmptyTitle = 'Está tudo em dia';
                $shellEmptyMessage = 'Não tem comissões por pagar neste momento.';
                $feedGroups = $pendingGroups;
                $shellFeedItemPartial = 'commission_pending_feed_item.php';
                $shellFeedItemVarName = 'commission';
                $shellFeedExtraClass = 'commission-pending-feed';
                require __DIR__ . '/../../partials/user_feed_shell.php';
            ?>
        </div>
    <?php else: ?>
        <?php
            $historyTitle = $activeTab === 'pago' ? 'Comissões já pagas' : 'Comissões canceladas';
            $emptyCopy = $activeTab === 'pago'
                ? 'Quando concluir um pagamento, o registo aparece aqui.'
                : 'Não tem comissões canceladas.';
            $historyDateField = static function (array $commission): string {
                $paidAt = (string) ($commission['paid_at'] ?? '');
                $validatedAt = (string) ($commission['owner_payment_validated_at'] ?? '');
                $createdAt = (string) ($commission['created_at'] ?? '');

                return $paidAt !== '' ? $paidAt : ($validatedAt !== '' ? $validatedAt : $createdAt);
            };
            $historyForGrouping = array_map(static function (array $commission) use ($historyDateField): array {
                $commission['_feed_date'] = $historyDateField($commission);

                return $commission;
            }, $historyCommissions);
            $historyGroups = FeedGrouping::byRecency($historyForGrouping, '_feed_date');
        ?>
        <div class="notification-inbox-panel payment-account-feed-panel">
            <div class="requests-inbox-panel-toolbar">
                <h2 class="requests-inbox-panel-title"><?php echo htmlspecialchars($historyTitle); ?></h2>
            </div>

            <?php
                $shellHasItems = !empty($historyCommissions);
                $shellEmptyIcon = 'fa-money';
                $shellEmptyTitle = $activeTab === 'pago' ? 'Ainda sem pagamentos' : 'Nada cancelado';
                $shellEmptyMessage = $emptyCopy;
                $feedGroups = $historyGroups;
                $shellFeedItemPartial = 'commission_history_feed_item.php';
                $shellFeedItemVarName = 'commission';
                $shellFeedExtraClass = 'commission-account-feed';
                require __DIR__ . '/../../partials/user_feed_shell.php';
            ?>
        </div>
    <?php endif; ?>
</div>
