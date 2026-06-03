<?php
/** @var array $accountState */
$accountState = is_array($accountState ?? null) ? $accountState : [];
$document = is_array($accountState['document'] ?? null) ? $accountState['document'] : null;
$showDocumentLayer = $document !== null && ($accountState['access'] ?? '') !== Src\classes\UserAccountState::ACCESS_FULL;

$statusTone = match ($accountState['status'] ?? '') {
    Src\classes\UserAccountState::STATUS_ATIVO => 'is-success',
    Src\classes\UserAccountState::STATUS_REJEITADO => 'is-danger',
    Src\classes\UserAccountState::STATUS_PENDENTE => 'is-warning',
    default => 'is-muted',
};

$accessTone = match ($accountState['access'] ?? '') {
    Src\classes\UserAccountState::ACCESS_FULL => 'is-success',
    Src\classes\UserAccountState::ACCESS_SUSPENDED => 'is-danger',
    Src\classes\UserAccountState::ACCESS_CORRECTION => 'is-danger',
    Src\classes\UserAccountState::ACCESS_ONBOARDING => 'is-warning',
    default => 'is-muted',
};

$docTone = match ($document['tone'] ?? '') {
    'green' => 'is-success',
    'red' => 'is-danger',
    'yellow' => 'is-warning',
    default => 'is-muted',
};
?>

<section class="account-state-structure" aria-label="Resumo do seu registo">
    <header class="account-state-structure-head">
        <h2 class="account-state-structure-title">Como está o seu registo</h2>
        <p class="account-state-structure-intro">
            Resumimos em três partes: o que a equipa já decidiu sobre a <strong>conta</strong>, o que pode <strong>fazer agora</strong>
            no site e o que se passa com o <strong>documento</strong> que enviou. Cada uma evolui ao seu ritmo.
        </p>
    </header>

    <ol class="account-state-layers">
        <li class="account-state-layer <?php echo htmlspecialchars($statusTone); ?>">
            <span class="account-state-layer-kicker">A sua conta</span>
            <strong class="account-state-layer-value"><?php echo htmlspecialchars((string) ($accountState['status_label'] ?? '—')); ?></strong>
            <p class="account-state-layer-note"><?php echo htmlspecialchars((string) ($accountState['status_description'] ?? '')); ?></p>
        </li>

        <li class="account-state-layer <?php echo htmlspecialchars($accessTone); ?>">
            <span class="account-state-layer-kicker">O que pode fazer agora</span>
            <strong class="account-state-layer-value"><?php echo htmlspecialchars((string) ($accountState['access_label'] ?? '—')); ?></strong>
            <?php if (!empty($accountState['is_suspended'])): ?>
                <p class="account-state-layer-note">O acesso está em pausa até <?php echo date('d/m/Y', strtotime((string) $accountState['suspended_until'])); ?>. Se não perceber o motivo, fale connosco pelo suporte.</p>
            <?php elseif (($accountState['access'] ?? '') === Src\classes\UserAccountState::ACCESS_ONBOARDING): ?>
                <p class="account-state-layer-note">Pode explorar imóveis à vontade. Visitas, compras, arrendamentos e anúncios próprios abrem quando a conta for aprovada.</p>
            <?php elseif (($accountState['access'] ?? '') === Src\classes\UserAccountState::ACCESS_CORRECTION): ?>
                <p class="account-state-layer-note">Com a conta rejeitada para correcção, pode ajustar nome, número de BI e documento abaixo. Telefone e email mudam-se depois, no perfil.</p>
            <?php elseif (($accountState['access'] ?? '') === Src\classes\UserAccountState::ACCESS_ONBOARDING && !empty($accountState['can_submit_documents_on_account_page'])): ?>
                <p class="account-state-layer-note">Enquanto a conta está em análise, pode enviar o documento de identificação. Nome e número de BI só mudam se a conta for rejeitada para correcção.</p>
            <?php endif; ?>
        </li>

        <?php if ($showDocumentLayer): ?>
        <li class="account-state-layer <?php echo htmlspecialchars($docTone); ?>">
            <span class="account-state-layer-kicker">Documento de identificação</span>
            <strong class="account-state-layer-value"><?php echo htmlspecialchars((string) ($document['label'] ?? '—')); ?></strong>
            <p class="account-state-layer-note"><?php echo htmlspecialchars((string) ($document['note'] ?? '')); ?></p>
        </li>
        <?php endif; ?>
    </ol>
</section>
