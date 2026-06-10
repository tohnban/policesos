<?php
$legalKicker = 'Informação útil';
$legalTitle = 'Política de Cookies';
$legalLead = 'O que são cookies, para que os servem na Imobil Fácil e como pode aceitar ou recusar a personalização do site.';

ob_start();
?>
<h4>Diplomas de referência</h4>
<ul>
    <li>Constituição da República de Angola — artigo 32.º, n.º 2 (privacidade e vida privada).</li>
    <li>Lei n.º 22/11, de 17 de Junho — Lei da Protecção de Dados Pessoais (LPDP).</li>
    <li>Lei n.º 23/11, de 20 de Junho — Lei das Comunicações Electrónicas e dos Serviços da Sociedade da Informação (LCE).</li>
</ul>
<h4>Artigos da LPDP (Lei 22/11) mais relevantes para cookies</h4>
<ul>
    <li><strong>Artigo 12.º</strong> — condições para tratamento de dados; consentimento; execução de contrato para cookies essenciais.</li>
    <li><strong>Artigo 19.º</strong> — requisitos para publicidade por via electrónica (quando aplicável a comunicações comerciais).</li>
    <li><strong>Artigo 25.º</strong> — dever de informar o titular dos dados.</li>
    <li><strong>Artigo 29.º</strong> — decisões com base em tratamento automatizado (personalização de listagens).</li>
    <li><strong>Artigo 30.º</strong> — medidas de segurança do tratamento.</li>
    <li><strong>Artigos 26.º a 28.º</strong> — direitos de acesso, oposição, rectificação e eliminação.</li>
</ul>
<h4>Autoridade de supervisão</h4>
<p>
    Agência de Protecção de Dados (APD). Está em curso revisão da LPDP; até entrada em vigor do novo diploma,
    aplicam-se as disposições da Lei n.º 22/11.
</p>
<?php
$legalReferencesContent = ob_get_clean();
?>
<div class="container legal-page">
    <?php include DIRREQ . 'app/view/partials/legal_page_hero.php'; ?>

    <article class="dashboard-module-card legal-document">
        <?php include DIRREQ . 'app/view/partials/legal_angola_intro.php'; ?>

        <h3>O que são cookies?</h3>
        <p>
            São pequenos ficheiros que o navegador guarda quando visita o site. Servem para manter a sessão iniciada,
            recordar preferências e permitir que a plataforma funcione com segurança.
        </p>

        <h3>Que informação podemos guardar?</h3>
        <p>
            Podemos registar como navega — imóveis vistos, favoritos ou pedidos de informação.
            Com conta, essa informação pode associar-se ao seu perfil.
            Sem conta, só usamos identificador temporário no navegador se aceitar cookies de personalização.
        </p>

        <h3>Dois tipos de cookies</h3>
        <p>
            <strong>Essenciais</strong> — necessários para login, segurança e funções básicas. Não pedem autorização extra;
            são indispensáveis ao serviço que solicita.
        </p>
        <p>
            <strong>De personalização (opcionais)</strong> — ajudam a mostrar imóveis mais relevantes para si.
            Só ficam activos se clicar em <strong>Aceitar</strong> no aviso do site.
        </p>

        <h3>Para que usamos esta informação?</h3>
        <p>
            Para tornar a pesquisa mais útil. Anúncios em destaque e regras comerciais da plataforma mantêm prioridade.
            A personalização apenas influencia a ordem das listagens, não substitui critérios de visibilidade ou patrocínio.
        </p>

        <h3>Como controlar as suas escolhas</h3>
        <p>
            Na primeira visita pode <strong>aceitar</strong> ou <strong>rejeitar</strong> cookies de personalização.
            A qualquer momento use <strong>Gerir Cookies</strong> no rodapé. Alterar a escolha não invalida
            tratamentos já feitos de forma lícita até essa data.
        </p>

        <h3>Durante quanto tempo guardamos?</h3>
        <p>
            A preferência de cookies guarda-se por período limitado. Registos de navegação mantêm-se só
            pelo tempo necessário ao serviço e à melhoria da experiência.
        </p>

        <h3>Os seus direitos</h3>
        <p>
            Pode pedir informação, correcção ou eliminação dos seus dados, e retirar o consentimento dos cookies opcionais.
            Saiba mais na <a href="<?php echo DIRPAGE; ?>privacidade">Política de Privacidade</a> ou contacte o suporte.
            Também pode apresentar reclamação à Agência de Protecção de Dados (APD).
        </p>

        <p style="margin-top:1.5rem;">
            Ver também: <a href="<?php echo DIRPAGE; ?>privacidade">Política de Privacidade</a> ·
            <a href="<?php echo DIRPAGE; ?>termos">Termos e Condições</a>
        </p>

        <?php include DIRREQ . 'app/view/partials/legal_references_block.php'; ?>
    </article>
</div>
