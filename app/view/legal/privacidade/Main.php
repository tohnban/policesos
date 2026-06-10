<?php
$legalKicker = 'Informação útil';
$legalTitle = 'Política de Privacidade';
$legalLead = 'Que dados recolhemos na Imobil Fácil, porque precisamos deles, com quem os partilhamos e quais são os seus direitos.';

ob_start();
?>
<h4>Diplomas de referência</h4>
<ul>
    <li>Constituição da República de Angola — artigo 32.º, n.º 2.</li>
    <li>Lei n.º 22/11, de 17 de Junho — LPDP (regime geral de tratamento de dados pessoais).</li>
    <li>Lei n.º 23/11, de 20 de Junho — LCE (dados e privacidade em serviços da sociedade da informação).</li>
    <li>Lei n.º 15/03, de 22 de Julho — Lei de Defesa do Consumidor (quando aplicável à relação de consumo).</li>
    <li>Lei n.º 1/07, de 14 de Maio — Lei das Actividades Comerciais (comércio electrónico).</li>
</ul>
<h4>LPDP — artigos relevantes para esta política</h4>
<ul>
    <li><strong>Artigos 11.º e 12.º</strong> — princípios e bases do tratamento (consentimento, contrato, obrigação legal, interesse legítimo).</li>
    <li><strong>Artigo 25.º</strong> — direito de informação do titular.</li>
    <li><strong>Artigos 26.º a 28.º</strong> — acesso, oposição, rectificação, actualização e eliminação.</li>
    <li><strong>Artigo 29.º</strong> — decisões automatizadas (ex.: ordenação de imóveis com base em interacções).</li>
    <li><strong>Artigos 21.º a 23.º</strong> — comunicação de dados e subcontratados.</li>
    <li><strong>Artigos 30.º e 31.º</strong> — segurança do tratamento.</li>
    <li><strong>Artigos 33.º e 34.º</strong> — transferência internacional de dados.</li>
    <li><strong>Artigos 35.º a 38.º</strong> — notificação e publicidade de tratamentos perante a APD, quando exigido.</li>
    <li><strong>Artigos 42.º e 43.º</strong> — sector privado e cooperativo.</li>
    <li><strong>Artigo 55.º</strong> — regime sancionatório por incumprimento.</li>
</ul>
<h4>Autoridade de supervisão</h4>
<p>Agência de Protecção de Dados (APD).</p>
<?php
$legalReferencesContent = ob_get_clean();
?>
<div class="container legal-page">
    <?php include DIRREQ . 'app/view/partials/legal_page_hero.php'; ?>

    <article class="dashboard-module-card legal-document">
        <?php include DIRREQ . 'app/view/partials/legal_angola_intro.php'; ?>

        <p class="legal-meta">Última actualização: junho de 2026 · <strong>Responsável pelo tratamento:</strong> Pague Fácil, Comércio e Serviços, LDA</p>

        <h2>Quem somos</h2>
        <p>
            A Imobil Fácil é uma plataforma angolana para encontrar, anunciar e negociar imóveis.
            Tratamos os seus dados pessoais com transparência e em respeito pela sua privacidade.
        </p>

        <h2>A quem se aplica</h2>
        <p>
            Visitantes do site e utilizadores com conta: proprietários, compradores, inquilinos, afiliados
            e outras pessoas envolvidas nos processos da plataforma.
        </p>

        <h2>Que dados podemos recolher</h2>
        <ul>
            <li><strong>Conta:</strong> nome, e-mail, telefone, utilizador, palavra-passe (protegida), tipo de perfil, documentos de verificação.</li>
            <li><strong>Imóveis:</strong> descrições, fotos, vídeos, preço, localização, estado do anúncio.</li>
            <li><strong>Pedidos e mensagens:</strong> interesse num imóvel, evolução do pedido, conversas na plataforma.</li>
            <li><strong>Pagamentos e comissões:</strong> referências, comprovativos e dados para liquidar obrigações.</li>
            <li><strong>Navegação:</strong> páginas visitadas, preferências de cookies — ver <a href="<?php echo DIRPAGE; ?>cookies">Política de Cookies</a>.</li>
            <li><strong>Segurança:</strong> registos de acesso e medidas anti-fraude.</li>
        </ul>

        <h2>Porque usamos os seus dados</h2>
        <ul>
            <li>Criar e gerir a sua conta e permitir o uso seguro do site.</li>
            <li>Publicar imóveis, processar pedidos de interesse e mensagens entre as partes.</li>
            <li>Calcular e comunicar comissões quando um negócio é fechado através da plataforma.</li>
            <li>Enviar avisos importantes (estado de pedidos, pagamentos, conta).</li>
            <li>Prevenir fraude e manter o marketplace fiável.</li>
            <li>Personalizar listagens, se aceitar cookies de personalização ou nos termos do serviço com conta.</li>
        </ul>
        <p>
            Em cada caso baseamo-nos no seu <strong>consentimento</strong>, na <strong>execução do contrato</strong>
            connosco, em <strong>obrigações legais</strong> ou em <strong>interesse legítimo</strong>, quando a lei o permitir
            e os seus direitos não prevalecerem.
        </p>

        <h2>Sugestões de imóveis adaptadas a si</h2>
        <p>
            Podemos ordenar listagens com base no que já viu ou guardou. Isto não substitui anúncios em destaque
            nem regras de visibilidade. Quem navega sem conta só recebe personalização após aceitar cookies no aviso do site.
            Pode pedir esclarecimentos ao suporte sobre este tipo de tratamento.
        </p>

        <h2>Com quem partilhamos dados</h2>
        <ul>
            <li><strong>Outras partes no negócio</strong> — no âmbito de um pedido (contacto e estado, conforme regras da plataforma).</li>
            <li><strong>Prestadores de apoio técnico</strong> — alojamento, e-mail, imagens, com dever de confidencialidade.</li>
            <li><strong>Perfil público de agência</strong> — apenas o que escolheu tornar visível.</li>
            <li><strong>Autoridades</strong> — quando a lei ou ordem judicial o exigir.</li>
        </ul>
        <p><strong>Não vendemos</strong> os seus dados para publicidade de terceiros.</p>

        <h2>Dados fora de Angola</h2>
        <p>
            Preferimos tratar dados em condições compatíveis com a protecção exigida em Angola.
            Se algum prestador processar dados no estrangeiro, aplicamos garantias contratuais e legais adequadas.
        </p>

        <h2>Por quanto tempo guardamos</h2>
        <p>
            Enquanto a conta estiver activa e pelo tempo necessário a obrigações legais, comissões, litígios
            ou defesa de direitos. Alguns registos de operações podem conservar-se mais tempo quando justificado.
        </p>

        <h2>Como protegemos a informação</h2>
        <p>
            Controlos de acesso, protecção das comunicações, limites de tentativas de login e registo de acções sensíveis.
            Se ocorrer incidente grave de segurança, actuamos conforme os deveres legais, incluindo comunicação à APD quando aplicável.
        </p>

        <h2>Os seus direitos</h2>
        <ul>
            <li>Saber que dados temos sobre si;</li>
            <li>Corrigir informação incorrecta;</li>
            <li>Pedir eliminação ou limitação, quando a lei o permitir;</li>
            <li>Opor-se a certos tratamentos;</li>
            <li>Retirar consentimento (ex.: cookies opcionais).</li>
        </ul>
        <p>
            Contacte o suporte indicando a sua conta. Podemos pedir prova de identidade.
            Pode também reclamar junto da <strong>Agência de Protecção de Dados (APD)</strong>.
        </p>

        <h2>Menores</h2>
        <p>
            O serviço destina-se a adultos com capacidade para negociar. Não recolhemos intencionalmente
            dados de menores sem autorização dos responsáveis.
        </p>

        <h2>Alterações</h2>
        <p>
            Podemos actualizar esta política. A data no topo será revista; mudanças importantes podem ser comunicadas no site ou por e-mail.
        </p>

        <h2>Outros documentos</h2>
        <ul>
            <li><a href="<?php echo DIRPAGE; ?>cookies">Política de Cookies</a></li>
            <li><a href="<?php echo DIRPAGE; ?>termos">Termos e Condições</a></li>
        </ul>

        <?php include DIRREQ . 'app/view/partials/legal_references_block.php'; ?>
    </article>
</div>
