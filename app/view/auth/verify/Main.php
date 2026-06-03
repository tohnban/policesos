<section class="auth-shell auth-shell-verify">
    <div class="auth-container auth-panel-card">
        <div class="auth-panel-head">
            <span class="sales-kicker">Verificação de Email</span>
            <?php if (isset($_GET['success']) && $_GET['success'] === 'email_change'): ?>
                <h2>Novo email confirmado</h2>
                <p>O endereço da sua conta foi actualizado. Pode iniciar sessão com o novo email.</p>
            <?php elseif (isset($_GET['success'])): ?>
                <h2>Email confirmado</h2>
                <p>Tudo certo com o seu email. Entre na Imobil Fácil, veja imóveis e acompanhe aqui o que falta para a conta ficar activa.</p>
            <?php elseif (isset($_GET['error'])): ?>
                <h2>Verificação falhou</h2>
                <p class="auth-message auth-message-error"><?php echo htmlspecialchars($_GET['error']); ?></p>
            <?php else: ?>
                <h2>Verifique o seu email</h2>
                <p>Enviamos um link de confirmação para o seu email. Por favor acesse o link para activar a sua conta.</p>
            <?php endif; ?>
        </div>
        <div class="auth-footer">
            <?php if (isset($_GET['success'])): ?>
                <p><a href="<?php echo DIRPAGE; ?>login">Ir para o login</a></p>
            <?php else: ?>
                <p><a href="<?php echo DIRPAGE; ?>login">Voltar ao login</a></p>
            <?php endif; ?>
        </div>
    </div>
</section>
