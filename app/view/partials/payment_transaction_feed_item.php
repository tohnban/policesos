<?php
/**
 * User account payment transaction row (feed).
 *
 * @var array<string, mixed> $transaction
 */

use App\model\Notification;

$transaction = is_array($transaction ?? null) ? $transaction : [];

$id = (int) ($transaction['id'] ?? 0);
$typeKey = (string) ($transaction['transaction_type'] ?? '');
$typeLabel = [
    'commission_owner_payment' => 'Comissão do imóvel',
    'commission_payout' => 'Pagamento ao afiliado',
    'system_commission' => 'Taxa da plataforma',
    'boost_fee' => 'Destaque do anúncio',
    'trust_badge_fee' => 'Selo de confiança',
    'manual_adjustment' => 'Ajuste',
    'subscription_fee' => 'Subscrição',
][$typeKey] ?? 'Movimento';

$status = (string) ($transaction['status'] ?? '');
$statusLabel = [
    'pendente' => 'Pendente',
    'processando' => 'A processar',
    'confirmado' => 'Confirmado',
    'cancelado' => 'Cancelado',
    'falhado' => 'Falhou',
    'rejeitado' => 'Recusado',
][$status] ?? ucfirst($status);
$amount = (float) ($transaction['amount'] ?? 0);
$currency = (string) ($transaction['currency'] ?? 'Kz');
$methodName = (string) ($transaction['method_name'] ?? 'N/A');
$reference = trim((string) ($transaction['reference_code'] ?? ''));
$createdAt = (string) ($transaction['created_at'] ?? '');
$confirmedAt = (string) ($transaction['confirmed_at'] ?? '');
$relativeTime = Notification::relativeTime($createdAt);
$absoluteTime = $createdAt !== '' ? date('d/m/Y H:i', strtotime($createdAt)) : '';

$statusTone = match ($status) {
    'confirmado' => 'tone-payment',
    'cancelado', 'rejeitado' => 'tone-document',
    'falhado' => 'tone-alert',
    default => 'tone-default',
};

$statusChipClass = match ($status) {
    'confirmado' => 'dashboard-chip-success',
    'cancelado', 'rejeitado' => 'dashboard-chip-neutral',
    'falhado' => 'dashboard-chip-danger',
    default => 'dashboard-chip-warning',
};

$iconMap = [
    'commission_owner_payment' => 'fa-money',
    'commission_payout' => 'fa-exchange',
    'system_commission' => 'fa-bank',
    'boost_fee' => 'fa-star',
    'trust_badge_fee' => 'fa-shield',
    'subscription_fee' => 'fa-refresh',
    'manual_adjustment' => 'fa-wrench',
];
$feedIcon = $iconMap[$typeKey] ?? 'fa-credit-card';
?>

<article class="notification-feed-item payment-account-feed-item is-read">
    <div class="notification-feed-link payment-account-feed-link">
        <span class="notification-feed-icon <?php echo htmlspecialchars($statusTone, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
            <i class="fa <?php echo htmlspecialchars($feedIcon, ENT_QUOTES, 'UTF-8'); ?>"></i>
        </span>
        <span class="notification-feed-body">
            <span class="notification-feed-text">
                <strong><?php echo htmlspecialchars($typeLabel); ?></strong>
                <span class="notification-feed-message">
                    <?php echo number_format($amount, 2, ',', '.'); ?> <?php echo htmlspecialchars($currency); ?>
                    <?php if ($methodName !== '' && $methodName !== 'N/A'): ?>
                        · <?php echo htmlspecialchars($methodName); ?>
                    <?php endif; ?>
                </span>
            </span>
            <span class="notification-feed-meta">
                <span class="dashboard-chip <?php echo htmlspecialchars($statusChipClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                <?php if ($reference !== ''): ?>
                    <span class="notification-feed-dot request-feed-meta-extra" aria-hidden="true">·</span>
                    <span class="payment-account-feed-ref request-feed-meta-extra">Ref. <?php echo htmlspecialchars($reference); ?></span>
                <?php endif; ?>
                <?php if ($confirmedAt !== ''): ?>
                    <span class="notification-feed-dot request-feed-meta-extra" aria-hidden="true">·</span>
                    <span class="payment-account-feed-confirmed request-feed-meta-extra">Confirmada <?php echo htmlspecialchars(date('d/m/Y', strtotime($confirmedAt))); ?></span>
                <?php endif; ?>
                <span class="notification-feed-dot" aria-hidden="true">·</span>
                <span class="payment-account-feed-id">#<?php echo $id; ?></span>
                <span class="notification-feed-dot" aria-hidden="true">·</span>
                <time class="notification-feed-time" datetime="<?php echo htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?>"
                      title="<?php echo htmlspecialchars($absoluteTime, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($relativeTime !== '' ? $relativeTime : $absoluteTime); ?>
                </time>
            </span>
        </span>
    </div>
</article>
