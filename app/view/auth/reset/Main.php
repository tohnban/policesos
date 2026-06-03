<section class="auth-shell auth-shell-reset">
    <div class="auth-container auth-panel-card">
        <div class="auth-panel-head">
            <span class="sales-kicker">Redefinir Senha</span>
            <h2>Crie uma nova senha</h2>
            <p>Digite sua nova senha para recuperar o acesso à conta.</p>
        </div>
        <?php if (isset($_GET['success'])): ?>
            <div class="auth-message auth-message-success">Senha redefinida com sucesso! Faça login.</div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="auth-message auth-message-error">Erro: <?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <form action="<?php echo DIRPAGE; ?>reset" method="POST" class="auth-form-grid">
            <?php echo Src\classes\ClassCsrf::field(); ?>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
            <div class="form-group">
                <label for="password">Nova Senha</label>
                <input type="password" id="password" name="password" required minlength="6" placeholder="Digite a nova senha">
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirme a Senha</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6" placeholder="Confirme a nova senha">
            </div>
            <button type="submit">Criar nova senha e aceder</button>
        </form>
        <div class="auth-footer">
            <p><a href="<?php echo DIRPAGE; ?>login">Voltar ao login</a></p>
        </div>
    </div>
</section>
