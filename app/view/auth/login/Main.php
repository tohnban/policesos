<?php
$loginErrors = [
    'blocked' => 'Muitas tentativas falhadas. Aguarde alguns minutos e tente novamente.',
    'unverified_email' => 'Por favor confirme o seu email antes de fazer login. Verifique a sua caixa de entrada.',
    'account_rejected' => 'A sua conta foi rejeitada. Contacte o suporte se acredita que isto é um erro.',
    'suspended' => 'A sua conta está suspensa temporariamente. Tente novamente mais tarde ou contacte o suporte.',
    'inactive' => 'A sua conta não está activa para login. Contacte o suporte se precisar de ajuda.',
    'invalid_password' => 'Senha incorreta. Verifique e tente novamente, ou recupere o acesso por email.',
    'invalid_credentials' => 'Email ou telefone não encontrado. Verifique os dados ou registe-se.',
    '1' => 'Credenciais inválidas. Verifique email/telefone e senha.',
];
$loginErrorCode = isset($_GET['error']) ? trim((string) $_GET['error']) : '';
$loginErrorMessage = $loginErrors[$loginErrorCode] ?? ($loginErrorCode !== '' ? $loginErrors['invalid_credentials'] : null);
?>
<section class="auth-shell auth-shell-login">
    <div class="auth-shell-copy">
        <span class="sales-kicker">Acesso seguro</span>
        <h1>Bem-vindo de volta. Continue a encontrar o seu imóvel.</h1>
        <p>Aceda ao seu painel, veja as suas solicitações e não perca nenhuma novidade.</p>

        <div class="auth-shell-highlights">
            <article>
                <strong>Tudo num só lugar</strong>
                <span>Alertas, solicitações e imóveis favoritos no mesmo painel.</span>
            </article>
            <article>
                <strong>Conta protegida</strong>
                <span>Autenticação com sessão segura e acesso condicionado por permissões.</span>
            </article>
            <article>
                <strong>Sem complicações</strong>
                <span>Do primeiro contacto ao fecho do negócio, tudo acontece aqui.</span>
            </article>
        </div>
    </div>

    <div class="auth-container auth-panel-card">
        <div class="auth-panel-head">
            <span class="sales-kicker">Login</span>
            <h2>Entrar na Imobil Fácil</h2>
            <p>Use email ou telefone para voltar ao seu painel.</p>
        </div>

        <?php if ($loginErrorMessage !== null): ?>
            <div class="auth-message auth-message-error" role="alert"><?php echo htmlspecialchars($loginErrorMessage); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="auth-message auth-message-success">Conta criada com sucesso! Faça login.</div>
        <?php endif; ?>

        <form action="<?php echo DIRPAGE; ?>authenticate" method="POST" class="auth-form-grid" id="loginForm" novalidate>
            <?php echo Src\classes\ClassCsrf::field(); ?>
            <div class="form-group">
                <label for="login">Email ou Telefone</label>
                <input type="text" id="login" name="login" required autocomplete="username" placeholder="email@exemplo.com ou +244 912 345 678" minlength="3">
                <small class="auth-helper-text">Telefone: pode usar +244, espaços ou só os 9 dígitos (ex.: 912345678).</small>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Digite a sua senha" minlength="6">
            </div>

            <button type="submit">Aceder ao painel</button>
        </form>

        <div class="auth-footer">
            <p><a href="<?php echo DIRPAGE; ?>recover">Esqueceu a senha? Recupere por email</a></p>
            <p>Ainda não tem conta? <a href="<?php echo DIRPAGE; ?>register">Registre-se</a></p>
        </div>
    </div>
</section>
