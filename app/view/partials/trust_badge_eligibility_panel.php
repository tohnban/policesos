<?php
/**
 * Requisitos para solicitar selo de confiança (sem formulário).
 *
 * @var array $trustEligibility
 * @var array<int,string> $trustBlockers
 * @var string $trustBadgeStatus
 */
$trustWonDeals = is_array($trustEligibility['won_deals'] ?? null) ? $trustEligibility['won_deals'] : [];
$trustAccountAge = is_array($trustEligibility['account_age_days'] ?? null) ? $trustEligibility['account_age_days'] : [];
$wonRequired = (int) ($trustWonDeals['required'] ?? 0);
$wonCurrent = (int) ($trustWonDeals['current'] ?? 0);
$daysRequired = (int) ($trustAccountAge['required'] ?? 0);
$daysCurrent = (int) ($trustAccountAge['current'] ?? 0);
$wonPct = $wonRequired > 0 ? min(100, (int) round(($wonCurrent / $wonRequired) * 100)) : 100;
$daysPct = $daysRequired > 0 ? min(100, (int) round(($daysCurrent / $daysRequired) * 100)) : 100;
?>
<div class="trust-badge-eligibility-locked" role="status" aria-live="polite">
    <div class="trust-badge-eligibility-alert">
        <i class="fa fa-lock" aria-hidden="true"></i>
        <div>
            <strong>Solicitação bloqueada</strong>
            <p>Cumpra todos os requisitos abaixo para desbloquear o pedido de selo de confiança.</p>
            <?php if (!empty($trustBlockers)): ?>
                <p class="trust-badge-eligibility-blockers"><?php echo htmlspecialchars(implode(' ', $trustBlockers)); ?>.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (($trustBadgeStatus ?? '') === 'rejeitado'): ?>
        <p class="dashboard-inline-note" style="color:#c0392b;margin:0 0 1rem;">
            O pedido anterior foi rejeitado. Após cumprir os requisitos, o botão de envio ficará disponível.
        </p>
    <?php endif; ?>

    <ul class="trust-badge-eligibility-list">
        <?php if ($wonRequired > 0): ?>
        <li class="trust-badge-eligibility-item<?php echo !empty($trustWonDeals['met']) ? ' is-met' : ''; ?>">
            <div class="trust-badge-eligibility-item-head">
                <span>Negociações ganhas confirmadas</span>
                <strong><?php echo $wonCurrent; ?> / <?php echo $wonRequired; ?></strong>
            </div>
            <div class="trust-badge-eligibility-progress" aria-hidden="true">
                <span style="width:<?php echo $wonPct; ?>%;"></span>
            </div>
            <?php
                $tbExcluded = (int) ($trustWonDeals['excluded_contested'] ?? 0) + (int) ($trustWonDeals['excluded_pending'] ?? 0);
                $tbTotalWon = (int) ($trustWonDeals['total_fechado_ganho'] ?? 0);
            ?>
            <?php if ($tbTotalWon > $wonCurrent): ?>
                <small class="dashboard-inline-note">
                    <?php echo $tbTotalWon; ?> fecho(s) ganho(s) no total;
                    <?php if ($tbExcluded > 0): ?>
                        <?php echo $tbExcluded; ?> não conta(m) (contestado ou pendente de confirmação).
                    <?php endif; ?>
                </small>
            <?php endif; ?>
        </li>
        <?php endif; ?>

        <?php if ($daysRequired > 0): ?>
        <li class="trust-badge-eligibility-item<?php echo !empty($trustAccountAge['met']) ? ' is-met' : ''; ?>">
            <div class="trust-badge-eligibility-item-head">
                <span>Tempo na plataforma</span>
                <strong><?php echo $daysCurrent; ?> / <?php echo $daysRequired; ?> dias</strong>
            </div>
            <div class="trust-badge-eligibility-progress" aria-hidden="true">
                <span style="width:<?php echo $daysPct; ?>%;"></span>
            </div>
        </li>
        <?php endif; ?>
    </ul>

    <p class="dashboard-inline-note trust-badge-eligibility-foot">
        O formulário de pedido só aparece quando todos os requisitos estiverem completos.
    </p>
</div>
