<?php
$legalKicker = 'Base legal';
$legalTitle = 'Política de Cookies';
$legalLead = 'Transparência sobre como usamos cookies e informações de navegação para operar a plataforma com segurança e melhorar a sua experiência.';
?>
<div class="container legal-page">
    <?php include DIRREQ . 'app/view/partials/legal_page_hero.php'; ?>

    <article class="dashboard-module-card legal-document" style="padding:1.5rem 2rem; margin-bottom:2rem;">
        <h3>1. O que são cookies</h3>
        <p>
            Cookies são pequenos ficheiros guardados no seu navegador para lembrar preferências,
            manter sessões ativas e melhorar funcionalidades do sistema.
        </p>

        <h3>2. O que recolhemos</h3>
        <p>
            Podemos usar um identificador de sessão (<code>visitor_key</code>) para analisar interações de navegação,
            como visualização de imóveis, favoritos e solicitações. Para utilizadores autenticados,
            esses sinais também podem ser associados ao <code>user_id</code>.
        </p>

        <h3>3. Tipos de cookies utilizados</h3>
        <p>
            <strong>Essenciais:</strong> necessários para autenticação, segurança e funcionamento básico da aplicação.
            <br>
            <strong>Personalização (opcional):</strong> usados apenas com o seu consentimento para melhorar a experiência,
            incluindo a forma como os imóveis são apresentados.
        </p>

        <h3>4. Finalidade do tratamento</h3>
        <p>
            As informações recolhidas permitem oferecer uma navegação mais relevante,
            sem comprometer prioridades comerciais e regras operacionais da plataforma.
        </p>

        <h3>5. Consentimento e controlo</h3>
        <p>
            O uso de cookies de personalização só ocorre após aceite explícito no banner.
            Pode rejeitar, aceitar ou alterar a sua escolha a qualquer momento em <strong>Gerir Cookies</strong> no rodapé.
        </p>

        <h3>6. Base legal</h3>
        <p>
            Cookies essenciais são tratados com base em necessidade técnica para prestação do serviço.
            Cookies de personalização são tratados com base no seu consentimento.
        </p>

        <h3>7. Prazo de retenção</h3>
        <p>
            A preferência de consentimento é mantida por período limitado e pode ser renovada.
            Eventos de navegação são mantidos pelo tempo estritamente necessário para finalidades operacionais e melhoria de experiência.
        </p>

        <h3>8. Direitos do utilizador</h3>
        <p>
            Pode solicitar informações sobre tratamento de dados e exercer os direitos previstos na legislação aplicável,
            incluindo revisão de consentimento e pedidos relacionados com privacidade — ver
            <a href="<?php echo DIRPAGE; ?>privacidade">Política de Privacidade</a>.
        </p>

        <h3>9. Contacto</h3>
        <p>
            Para esclarecimentos sobre privacidade e tratamento de dados, contacte a equipa de suporte da plataforma.
        </p>

        <p style="margin-top:1.5rem;">
            <a href="<?php echo DIRPAGE; ?>termos">Termos e Condições</a>
        </p>
    </article>
</div>
