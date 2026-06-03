<section class="auth-shell auth-shell-recover">
    <div class="auth-container auth-panel-card">
        <div class="auth-panel-head">
            <span class="sales-kicker">Recuperar Conta</span>
            <h2>Recupere o acesso por email</h2>
            <p>Informe seu email cadastrado para receber instruções de recuperação.</p>
        </div>
        <?php if (isset($_GET['success'])): ?>
            <div class="auth-message auth-message-success">Se o email existir, você receberá instruções em instantes.</div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="auth-message auth-message-error">Erro: <?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <form action="<?php echo DIRPAGE; ?>recover" method="POST" class="auth-form-grid">
            <?php echo Src\classes\ClassCsrf::field(); ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autocomplete="email" placeholder="email@exemplo.com">
            </div>
            <button type="submit">Recuperar meu acesso</button>
        </form>
        <div class="auth-footer">
            <p><a href="<?php echo DIRPAGE; ?>login">Voltar ao login</a></p>
        </div>
    </div>
</section>
