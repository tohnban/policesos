<?php
    $amountSummary = is_array($amountSummary ?? null) ? $amountSummary : App\model\Request::negotiationAmountSummary(is_array($request ?? null) ? $request : []);
    $totalAmount = (float) ($amountSummary['total_amount'] ?? 0);
    $totalFormatted = (string) ($amountSummary['total_formatted'] ?? App\model\Request::formatAmountKz($totalAmount));
    $breakdown = isset($amountSummary['breakdown']) ? (string) $amountSummary['breakdown'] : '';
    $referenceLine = isset($amountSummary['reference_line']) ? (string) $amountSummary['reference_line'] : '';
    $isRentalModality = !empty($amountSummary['is_rental_modality']);
?>
<?php if ($totalAmount > 0): ?>
<div class="request-payment-amount-block">
    <span class="request-payment-amount-kicker"><?php echo $isRentalModality ? 'Valor total da modalidade' : 'Valor total a pagar'; ?></span>
    <strong class="request-payment-amount-total"><?php echo htmlspecialchars($totalFormatted); ?></strong>
    <?php if ($breakdown !== ''): ?>
        <p class="request-payment-amount-breakdown">
            <i class="fa fa-calculator" aria-hidden="true"></i>
            <?php echo htmlspecialchars($breakdown); ?>
        </p>
        <small class="request-payment-amount-note">O valor acima corresponde ao total da modalidade escolhida nesta solicitação.</small>
    <?php elseif ($referenceLine !== ''): ?>
        <small class="request-payment-amount-note"><?php echo htmlspecialchars($referenceLine); ?></small>
    <?php endif; ?>
</div>
<?php endif; ?>
