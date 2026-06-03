<?php
$registerErrorCode = isset($_GET['error']) ? trim((string) $_GET['error']) : '';
$registerErrorMessage = $registerErrorCode !== ''
    ? (\Src\classes\AuthRegisterFeedback::isKnownCode($registerErrorCode)
        ? \Src\classes\AuthRegisterFeedback::message($registerErrorCode)
        : null)
    : null;
?>
<section class="auth-shell auth-shell-register">
    <div class="auth-shell-copy">
        <span class="sales-kicker">Nova conta</span>
        <h1>Crie a sua conta e comece a explorar imóveis verificados.</h1>
        <p>Registe-se para pesquisar imóveis, entrar em contacto com proprietários e acompanhar as suas solicitações.</p>

        <div class="auth-shell-highlights">
            <article>
                <strong>Conta segura</strong>
                <span>Perfil verificado — os proprietários sabem com quem falam.</span>
            </article>
            <article>
                <strong>Identificação</strong>
                <span>BI ou NIF e documento para aprovarmos o seu registo.</span>
            </article>
            <article>
                <strong>Comece hoje</strong>
                <span>Explore imóveis enquanto a conta é analisada.</span>
            </article>
        </div>
    </div>

    <div class="auth-container auth-panel-card auth-register-panel">
        <div class="auth-panel-head">
            <span class="sales-kicker">Registo</span>
            <h2>Registar na Imobil Fácil</h2>
            <p>Dois passos. Campos com * são obrigatórios.</p>
        </div>

        <?php if ($registerErrorMessage !== null): ?>
            <div class="auth-message auth-message-error" role="alert"><?php echo htmlspecialchars($registerErrorMessage); ?></div>
        <?php endif; ?>

        <form action="<?php echo DIRPAGE; ?>store" method="POST" enctype="multipart/form-data" class="auth-form-grid auth-form-grid-register auth-register-form" id="registerForm" novalidate>
            <?php echo Src\classes\ClassCsrf::field(); ?>

            <section class="auth-form-block auth-register-step auth-form-span-full" aria-labelledby="register-step-one-title">
                <header class="auth-form-block-head">
                    <span class="auth-form-block-step" aria-hidden="true">1</span>
                    <div>
                        <h3 class="auth-form-block-title" id="register-step-one-title">Dados e identificação</h3>
                        <p class="auth-form-block-lead">Nome, documento e foto opcional. O nome de utilizador (@…) é criado automaticamente a partir do nome indicado.</p>
                    </div>
                </header>
                <div class="auth-form-block-body">
                    <div class="auth-register-intro-row">
                        <div class="form-group auth-register-name-field">
                            <label for="name">Nome completo ou razão social *</label>
                            <input type="text" id="name" name="name" required autocomplete="name" placeholder="Ex.: Maria Silva ou Empresa LDA">
                        </div>
                        <div class="auth-register-photo">
                            <span class="auth-register-photo-label">Foto <span class="auth-register-optional">(opcional)</span></span>
                            <div class="auth-profile-preview" id="register-profile-preview" hidden>
                                <img id="register-profile-preview-image" src="" alt="Pré-visualização da foto de perfil">
                            </div>
                            <div class="auth-photo-uploader auth-photo-uploader-compact" id="register-photo-uploader">
                                <input type="file" id="profile_photo" name="profile_photo" class="auth-register-file-native" accept="image/*" tabindex="-1">
                                <label for="profile_photo" class="auth-photo-pick">Escolher</label>
                            </div>
                            <small class="auth-helper-text" id="register-photo-feedback">JPG, até 512 KB.</small>
                        </div>
                    </div>

                    <div class="auth-register-id-fields auth-form-block-identification is-pending-type" id="register-identification" aria-labelledby="register-block-id-title">
                        <p class="auth-register-id-kicker" id="register-block-id-title">Identificação *</p>
                        <div class="form-group">
                            <label for="user_type">Tipo de conta *</label>
                            <select id="user_type" name="user_type" required>
                                <option value="">Seleccione o tipo</option>
                                <option value="pessoa_fisica">Pessoa física</option>
                                <option value="pessoa_juridica">Pessoa jurídica</option>
                            </select>
                            <small class="auth-helper-text">Física: BI/NIF 14 dígitos · Jurídica: NIF 10 dígitos.</small>
                        </div>
                        <div class="form-group" id="document_group">
                            <label for="document_number" id="document_label">Número de identificação *</label>
                            <input type="text" id="document_number" name="document_number" placeholder="Seleccione o tipo acima" inputmode="numeric" autocomplete="off" disabled>
                            <small id="document_hint" class="auth-helper-text">Escolha o tipo de conta para ver o formato.</small>
                        </div>
                        <div class="form-group auth-register-doc-upload" id="document_upload_group">
                            <label for="document_file" id="document_file_label">Documento *</label>
                            <div class="auth-register-file-row">
                                <input type="file" id="document_file" name="document_file" class="auth-register-file-native" accept=".pdf,.jpg,.jpeg,.png" disabled tabindex="-1">
                                <label for="document_file" class="auth-file-pick auth-register-file-btn">
                                    <i class="fa fa-upload" aria-hidden="true"></i>
                                    <span>Seleccionar</span>
                                </label>
                                <span class="auth-file-name auth-register-file-label" id="document_file_name">Nenhum ficheiro</span>
                            </div>
                            <small class="auth-helper-text">PDF ou JPG/PNG, máx. 1 MB.</small>
                        </div>
                    </div>
                </div>
            </section>

            <section class="auth-form-block auth-register-step auth-form-span-full" aria-labelledby="register-step-two-title">
                <header class="auth-form-block-head">
                    <span class="auth-form-block-step" aria-hidden="true">2</span>
                    <div>
                        <h3 class="auth-form-block-title" id="register-step-two-title">Acesso e contacto</h3>
                        <p class="auth-form-block-lead">Email, telefone e senha para entrar na plataforma.</p>
                    </div>
                </header>
                <div class="auth-form-block-body auth-form-block-body-split">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required autocomplete="email" placeholder="email@exemplo.com">
                    </div>
                    <div class="form-group">
                        <label for="phone">Telefone *</label>
                        <input type="tel" id="phone" name="phone" required autocomplete="tel" placeholder="+244 912 345 678" minlength="9">
                        <small class="auth-helper-text">+244 ou 9 dígitos.</small>
                    </div>
                    <div class="form-group">
                        <label for="password">Senha *</label>
                        <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="Mín. 6 caracteres" minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Confirmar senha *</label>
                        <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password" placeholder="Repita a senha" minlength="6">
                        <small class="auth-helper-text" id="password-confirm-feedback" aria-live="polite"></small>
                    </div>
                </div>

                <div class="auth-register-affiliate">
                    <label class="checkbox-label auth-checkbox-card">
                        <input type="checkbox" name="affiliate_interest" value="1">
                        <span class="auth-checkbox-text">
                            Interesse em ser parceiro/afiliado
                            <small class="auth-checkbox-note">Activável no painel após aprovação da conta.</small>
                        </span>
                    </label>
                </div>

                <div class="auth-register-submit">
                    <button type="submit" class="auth-register-submit-btn">Criar minha conta grátis</button>
                </div>
            </section>
        </form>

        <div class="auth-footer">
            <p>Já tem conta? <a href="<?php echo DIRPAGE; ?>login">Faça login</a></p>
        </div>
    </div>
</section>
