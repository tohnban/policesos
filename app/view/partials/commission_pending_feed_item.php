<?php
/**
 * Pending commission row for owner payment feed.
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
$dueAt = (string) ($commission['due_at'] ?? '');
$dueLabel = $dueAt !== '' ? date('d/m/Y H:i', strtotime($dueAt)) : '';
$relativeDue = $dueAt !== '' ? Notification::relativeTime($dueAt) : '';
$isOverdue = $dueAt !== '' && strtotime($dueAt) < time();
$ownerPayStatus = Commission::resolveOwnerPaymentStatus($commission);
$submitted = $ownerPayStatus === Commission::OWNER_PAYMENT_ENVIADO;
$rejected = $ownerPayStatus === Commission::OWNER_PAYMENT_REJEITADO;
$rejectReason = trim((string) ($commission['owner_payment_rejection_reason'] ?? ''));
$propertyUrl = DIRPAGE . 'property/' . $propertyId;
$paymentUrl = DIRPAGE . 'dashboard/commissionPayment/' . $commissionId;

$feedTone = $isOverdue ? 'tone-alert' : ($submitted ? 'tone-default' : 'tone-payment');
$statusLabel = $submitted
    ? 'Comprovativo em análise'
    : ($rejected ? 'Comprovativo recusado' : ($isOverdue ? 'Prazo ultrapassado' : 'Aguarda pagamento'));
?>

<article class="notification-feed-item commission-pending-feed-item commission-account-feed-item <?php echo $isOverdue ? 'is-unread' : 'is-read'; ?>"
         data-commission-id="<?php echo $commissionId; ?>">
    <a href="<?php echo htmlspecialchars($propertyUrl, ENT_QUOTES, 'UTF-8'); ?>"
       class="notification-feed-link commission-account-feed-link">
        <span class="notification-feed-icon <?php echo htmlspecialchars($feedTone, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
            <i class="fa fa-home"></i>
        </span>
        <span class="notification-feed-body">
            <span class="notification-feed-text">
                <strong><?php echo htmlspecialchars($propertyTitle); ?></strong>
                <span class="notification-feed-message"><?php echo number_format($amount, 0, ',', '.'); ?> Kz a pagar</span>
            </span>
            <span class="notification-feed-meta">
                <span class="request-status-badge <?php echo $isOverdue ? 'request-status-expirado' : 'request-status-pendente'; ?>">
                    <?php echo htmlspecialchars($statusLabel); ?>
                </span>
                <?php if ($dueLabel !== ''): ?>
                    <span class="notification-feed-dot request-feed-meta-extra" aria-hidden="true">·</span>
                    <span class="request-feed-meta-extra">Prazo: <?php echo htmlspecialchars($dueLabel); ?></span>
                <?php endif; ?>
                <?php if ($relativeDue !== ''): ?>
                    <span class="notification-feed-dot" aria-hidden="true">·</span>
                    <time class="notification-feed-time" datetime="<?php echo htmlspecialchars($dueAt, ENT_QUOTES, 'UTF-8'); ?>"
                          title="<?php echo htmlspecialchars($dueLabel, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($relativeDue); ?>
                    </time>
                <?php endif; ?>
            </span>
        </span>
        <?php if ($isOverdue && !$submitted): ?>
            <span class="notification-feed-unread-dot" aria-label="Em atraso"></span>
        <?php endif; ?>
    </a>

    <div class="commission-pending-feed-actions">
        <?php if ((float) ($commission['system_amount'] ?? 0) > 0 || (float) ($commission['affiliate_amount'] ?? 0) > 0): ?>
            <p class="commission-pending-feed-breakdown">
                Inclui taxa da plataforma
                <?php if ((float) ($commission['affiliate_amount'] ?? 0) > 0): ?>
                    e parte do afiliado
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <?php if ($rejected && $rejectReason !== ''): ?>
            <p class="commission-pending-feed-note"><?php echo htmlspecialchars($rejectReason); ?></p>
        <?php endif; ?>
        <?php if ($submitted): ?>
            <span class="dashboard-inline-note">A equipa está a validar o seu comprovativo.</span>
        <?php elseif ($rejected): ?>
            <a href="<?php echo htmlspecialchars($paymentUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn-primary commission-pending-feed-btn">Enviar novo comprovativo</a>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars($paymentUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn-primary commission-pending-feed-btn">
                <?php echo $isOverdue ? 'Regularizar pagamento' : 'Pagar comissão'; ?>
            </a>
        <?php endif; ?>
    </div>
</article>
