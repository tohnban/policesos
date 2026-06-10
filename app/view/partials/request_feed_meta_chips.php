<?php
/**
 * Secondary request meta chips (hidden on mobile main row; shown in actions panel).
 *
 * Expects variables from request_feed_item.php.
 *
 * @var string $status
 * @var string|null $paymentConfirmationStatus
 * @var string $propertyStatusLabel
 * @var string|null $closingConfirmationStatus
 * @var string $disputeStatus
 */

use App\model\Request as RequestModel;

$chipClass = 'request-feed-meta-chip';
if (!empty($forActionsPanel)) {
    $chipClass .= ' request-feed-actions-meta-chip';
} else {
    $chipClass .= ' request-feed-meta-extra';
}
?>

<?php if ($status === 'fechado_ganho'): ?>
    <span class="notification-feed-dot <?php echo $chipClass; ?>" aria-hidden="true">·</span>
    <span class="request-status-badge <?php echo $chipClass; ?> request-status-<?php echo htmlspecialchars((string) ($paymentConfirmationStatus ?: 'none'), ENT_QUOTES, 'UTF-8'); ?>">
        Pagamento: <?php echo htmlspecialchars(RequestModel::paymentConfirmationLabel($paymentConfirmationStatus)); ?>
    </span>
<?php endif; ?>
<span class="notification-feed-dot <?php echo $chipClass; ?>" aria-hidden="true">·</span>
<span class="request-feed-property-status <?php echo $chipClass; ?>">Imóvel: <?php echo htmlspecialchars($propertyStatusLabel); ?></span>
<?php if (is_string($closingConfirmationStatus) && $closingConfirmationStatus === 'pendente'): ?>
    <span class="notification-feed-dot <?php echo $chipClass; ?>" aria-hidden="true">·</span>
    <span class="request-feed-attention <?php echo $chipClass; ?>"><i class="fa fa-exclamation-circle" aria-hidden="true"></i> Confirmação pendente</span>
<?php endif; ?>
<?php if ($disputeStatus !== RequestModel::DISPUTE_STATUS_NONE): ?>
    <?php
        $disputeLabels = [
            'aberta' => 'Disputa aberta',
            'em_analise' => 'Disputa em análise',
            'julgada_procedente' => 'Disputa: procedente',
            'julgada_improcedente' => 'Disputa: improcedente',
        ];
        $disputeLabel = $disputeLabels[$disputeStatus] ?? ucfirst(str_replace('_', ' ', $disputeStatus));
    ?>
    <span class="notification-feed-dot <?php echo $chipClass; ?>" aria-hidden="true">·</span>
    <span class="request-status-badge <?php echo $chipClass; ?> request-status-em_disputa dispute-status-chip">
        <i class="fa fa-gavel" aria-hidden="true"></i> <?php echo htmlspecialchars($disputeLabel); ?>
    </span>
<?php endif; ?>
