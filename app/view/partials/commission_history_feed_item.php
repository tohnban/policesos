<?php
/**
 * Owner commission history row (feed).
 *
 * @var array<string, mixed> $commission
 */

use App\model\Commission;
use App\model\Notification;

$commission = is_array($commission ?? null) ? $commission : [];

$commissionId = (int) ($commission['id'] ?? 0);
$propertyId = (int) ($commission['property_id'] ?? 0);
$propertyTitle = (string) ($commission['property_title'] ?? 'Imóvel');
$amount = (float) ($commission['amount'] ?? 0);
$statusKey = (string) ($commission['status'] ?? '');
$ownerPayStatus = Commission::resolveOwnerPaymentStatus($commission);
$ownerRef = trim((string) ($commission['owner_payment_reference'] ?? ''));
if ($ownerRef === '') {
    $ownerRef = trim((string) ($commission['payment_reference'] ?? ''));
}

$paidAt = (string) ($commission['paid_at'] ?? '');
$validatedAt = (string) ($commission['owner_payment_validated_at'] ?? '');
$createdAt = (string) ($commission['created_at'] ?? '');
$dateRaw = $paidAt !== '' ? $paidAt : ($validatedAt !== '' ? $validatedAt : $createdAt);
$relativeTime = Notification::relativeTime($dateRaw);
$absoluteTime = $dateRaw !== '' ? date('d/m/Y H:i', strtotime($dateRaw)) : '';

$statusTone = $statusKey === 'pago' ? 'tone-payment' : 'tone-document';
$propertyUrl = DIRPAGE . 'property/' . $propertyId;
?>

<article class="notification-feed-item commission-account-feed-item is-read">
    <a href="<?php echo htmlspecialchars($propertyUrl, ENT_QUOTES, 'UTF-8'); ?>" class="notification-feed-link commission-account-feed-link">
        <span class="notification-feed-icon <?php echo htmlspecialchars($statusTone, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
            <i class="fa fa-home"></i>
        </span>
        <span class="notification-feed-body">
            <span class="notification-feed-text">
                <strong><?php echo htmlspecialchars($propertyTitle); ?></strong>
                <span class="notification-feed-message">
                    <?php echo number_format($amount, 0, ',', '.'); ?> Kz
                    <?php echo $statusKey === 'pago' ? 'pagos' : ''; ?>
                </span>
            </span>
            <span class="notification-feed-meta">
                <span class="commission-status-badge commission-status-<?php echo htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars(Commission::statusLabel($statusKey)); ?>
                </span>
                <span class="notification-feed-dot request-feed-meta-extra" aria-hidden="true">·</span>
                <span class="request-feed-meta-extra"><?php echo htmlspecialchars(Commission::ownerPaymentStatusLabel($ownerPayStatus)); ?></span>
                <?php if ($ownerRef !== ''): ?>
                    <span class="notification-feed-dot request-feed-meta-extra" aria-hidden="true">·</span>
                    <span class="request-feed-meta-extra">Ref. <?php echo htmlspecialchars($ownerRef); ?></span>
                <?php endif; ?>
                <span class="notification-feed-dot" aria-hidden="true">·</span>
                <time class="notification-feed-time" datetime="<?php echo htmlspecialchars($dateRaw, ENT_QUOTES, 'UTF-8'); ?>"
                      title="<?php echo htmlspecialchars($absoluteTime, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($relativeTime !== '' ? $relativeTime : $absoluteTime); ?>
                </time>
            </span>
        </span>
    </a>
</article>
