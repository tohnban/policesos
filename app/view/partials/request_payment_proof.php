<?php
    $paymentProofPath = trim((string) ($paymentProofPath ?? ''));
    $paymentDeclaredAt = $paymentDeclaredAt ?? null;
    $proofUrl = $paymentProofPath !== '' ? App\model\Request::paymentProofPublicUrl($paymentProofPath) : '';
?>
<?php if ($proofUrl !== ''): ?>
<div class="request-payment-proof-block">
    <strong>Comprovativo de pagamento</strong>
    <?php if ($paymentDeclaredAt): ?>
        <small class="request-payment-proof-meta">
            Declarado em <?php echo htmlspecialchars((string) date('d/m/Y H:i', strtotime((string) $paymentDeclaredAt))); ?>
        </small>
    <?php endif; ?>
    <a href="<?php echo htmlspecialchars($proofUrl); ?>" target="_blank" rel="noopener" class="moderation-proof-link" title="Abrir comprovativo em tamanho completo">
        <img src="<?php echo htmlspecialchars($proofUrl); ?>" alt="Comprovativo de pagamento" class="moderation-proof-thumb">
    </a>
</div>
<?php endif; ?>
