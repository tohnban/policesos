<?php
$requestedPath = isset($requestedPath) ? (string) $requestedPath : (string) ($data['requestedPath'] ?? '');
$isAuthenticated = !empty($isAuthenticated) || !empty($data['isAuthenticated']);
$showPath = $requestedPath !== '' && strlen($requestedPath) <= 120;
?>

<div class="error-404-page-view">
    <section class="container error-404-shell">
        <div class="error-404-card">
            <div class="error-404-visual" aria-hidden="true">
                <span class="error-404-code">404</span>
            </div>

            <div class="error-404-copy">
                <span class="sales-kicker">Página não encontrada</span>
                <h1>Esta página não existe ou foi movida</h1>
                <p>O endereço pode estar incorrecto, desactualizado ou o conteúdo já não está disponível. Use os atalhos abaixo para continuar a explorar imóveis.</p>

                <?php if ($showPath): ?>
                    <p class="error-404-path">
                        <i class="fa fa-link" aria-hidden="true"></i>
                        <code><?php echo htmlspecialchars('/' . $requestedPath); ?></code>
                    </p>
                <?php endif; ?>
            </div>

            <div class="error-404-actions">
                <a href="<?php echo DIRPAGE; ?>" class="btn-primary error-404-cta">
                    <i class="fa fa-home" aria-hidden="true"></i> Voltar ao início
                </a>
                <a href="<?php echo DIRPAGE; ?>properties" class="btn-secondary error-404-cta">
                    <i class="fa fa-search" aria-hidden="true"></i> Ver imóveis
                </a>
            </div>

            <nav class="error-404-nav" aria-label="Atalhos úteis">
                <a href="<?php echo DIRPAGE; ?>featured" class="error-404-nav-link">
                    <i class="fa fa-star" aria-hidden="true"></i>
                    <span>
                        <strong>Destaques</strong>
                        <small>Os imóveis mais procurados agora</small>
                    </span>
                </a>
                <?php if ($isAuthenticated): ?>
                    <a href="<?php echo DIRPAGE; ?>dashboard" class="error-404-nav-link">
                        <i class="fa fa-th-large" aria-hidden="true"></i>
                        <span>
                            <strong>Painel</strong>
                            <small>Retome solicitações e favoritos</small>
                        </span>
                    </a>
                <?php else: ?>
                    <a href="<?php echo DIRPAGE; ?>login" class="error-404-nav-link">
                        <i class="fa fa-sign-in" aria-hidden="true"></i>
                        <span>
                            <strong>Entrar</strong>
                            <small>Aceda à sua conta Imobil Fácil</small>
                        </span>
                    </a>
                <?php endif; ?>
                <a href="<?php echo DIRPAGE; ?>cookies" class="error-404-nav-link">
                    <i class="fa fa-shield" aria-hidden="true"></i>
                    <span>
                        <strong>Ajuda</strong>
                        <small>Política de cookies e preferências</small>
                    </span>
                </a>
            </nav>
        </div>
    </section>
</div>
