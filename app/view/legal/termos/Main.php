<?php
$legalKicker = 'Informação útil';
$legalTitle = 'Termos e Condições';
$legalLead = 'Regras de utilização da Imobil Fácil para proprietários, quem procura imóvel, afiliados e visitantes.';

ob_start();
?>
<h4>Diplomas de referência</h4>
<ul>
    <li>Constituição da República de Angola — artigo 32.º, n.º 2.</li>
    <li>Lei n.º 1/07, de 14 de Maio — Lei das Actividades Comerciais (comércio electrónico, artigo 13.º).</li>
    <li>Lei n.º 15/03, de 22 de Julho — Lei de Defesa do Consumidor (artigos 20.º a 22.º).</li>
    <li>Lei n.º 23/11, de 20 de Junho — LCE (contratação electrónica, artigo 28.º).</li>
    <li>Lei n.º 22/11, de 17 de Junho — LPDP (dados pessoais no âmbito da conta e dos pedidos).</li>
    <li>Código Civil e demais legislação aplicável a contratos e responsabilidade civil.</li>
</ul>
<h4>Relação com regulamento interno</h4>
<p>
    Fluxos de pedidos, comissões e janela de atribuição (em regra 90 dias) seguem também o regulamento
    operacional da plataforma, sem prejuízo das leis acima citadas.
</p>
<?php
$legalReferencesContent = ob_get_clean();
?>
<div class="container legal-page">
    <?php include DIRREQ . 'app/view/partials/legal_page_hero.php'; ?>

    <article class="dashboard-module-card legal-document">
        <?php include DIRREQ . 'app/view/partials/legal_angola_intro.php'; ?>

        <p class="legal-meta">Última actualização: junho de 2026 · <strong>Prestador:</strong> Pague Fácil, Comércio e Serviços, LDA</p>

        <h2>1. Aceitação</h2>
        <p>
            Ao usar a Imobil Fácil, aceita estes Termos, a
            <a href="<?php echo DIRPAGE; ?>privacidade">Política de Privacidade</a> e a
            <a href="<?php echo DIRPAGE; ?>cookies">Política de Cookies</a>.
        </p>

        <h2>2. O que a plataforma faz</h2>
        <p>Permite:</p>
        <ul>
            <li>Procurar imóveis para comprar ou arrendar;</li>
            <li>Publicar e gerir anúncios (com moderação quando necessário);</li>
            <li>Enviar pedidos de interesse e acompanhar o estado;</li>
            <li>Trocar mensagens no âmbito dos pedidos;</li>
            <li>Actuar como afiliado, com autorização do proprietário;</li>
            <li>Pagar comissões e taxas conforme o preçário em vigor.</li>
        </ul>
        <p>
            Somos intermediário digital. <strong>Não substituímos</strong> advogado, notário ou contabilista —
            o contrato final entre comprador e vendedor é da responsabilidade das partes.
        </p>

        <h2>3. Informação clara (defesa do consumidor)</h2>
        <p>
            Informação sobre imóveis, preços e comissões deve ser <strong>correcta, clara e em português</strong>.
            Ofertas e publicidade na plataforma vinculam quem as publica, na medida em que fazem parte da relação consigo.
            São proibidas práticas enganosas ou abusivas.
        </p>

        <h2>4. Contratos feitos em linha</h2>
        <p>
            A criação de conta, aceitação de condições no site e confirmações electrónicas (incluindo afiliação
            ou estados de pedidos) são válidas como manifestação de vontade nos termos da lei das comunicações electrónicas,
            sem prejuízo de formalidades especiais que a lei imponha a certos negócios imobiliários.
        </p>

        <h2>5. A sua conta</h2>
        <ul>
            <li>Dados verdadeiros e actualizados.</li>
            <li>Palavra-passe em segredo.</li>
            <li>Verificação de e-mail ou documentos quando pedida.</li>
            <li>Possível suspensão por fraude ou incumprimento grave.</li>
            <li>Planos pagos e selos de confiança com regras no painel.</li>
        </ul>

        <h2>6. Anúncios</h2>
        <ul>
            <li>O proprietário responde pela veracidade do anúncio.</li>
            <li>Podem ficar em espera, em análise, activos ou recusados.</li>
            <li>Proibidos anúncios falsos, enganadores ou ilegais.</li>
            <li>Destaques e selos seguem o pacote contratado.</li>
        </ul>

        <h2>7. Pedidos e negociação</h2>
        <p>
            Interesse sério num imóvel deve gerar um <strong>pedido na plataforma</strong>, com estados que reflectem
            a realidade (pendente, em contacto, proposta, fechado com ou sem sucesso, expirado, em disputa).
        </p>
        <ul>
            <li>Actualize o estado com honestidade.</li>
            <li>Pedidos parados podem expirar após lembretes.</li>
            <li>Telefonar ou reunir-se <strong>não substitui</strong> registar o fecho aqui, se o contacto começou na plataforma.</li>
            <li>Desacordos sobre pagamento podem abrir estado de disputa.</li>
        </ul>

        <h2>8. Comissões e prazo de 90 dias</h2>
        <p>
            Negócio fechado entre as mesmas partes e o mesmo imóvel dentro do prazo de atribuição (em regra,
            <strong>90 dias</strong> após o pedido) pode gerar comissão, incluindo parte do afiliado se houver.
            Fechar fora da plataforma só para evitar comissão viola estes termos e pode levar a suspensão e cobrança em dívida.
        </p>

        <h2>9. Afiliados</h2>
        <ul>
            <li>Exige autorização do proprietário ou regra do anúncio.</li>
            <li>Parceiro independente, não empregado da Pague Fácil.</li>
            <li>Comissão conforme política publicada.</li>
            <li>Fraude anula direitos e pode implicar responsabilidade legal.</li>
        </ul>

        <h2>10. Pagamentos</h2>
        <ul>
            <li>Comissão devida após fecho confirmado.</li>
            <li>Dinheiro entre particulares pode mudar de mãos fora do site, mas o <strong>registo do fecho</strong> e o pagamento à Pague Fácil são obrigatórios.</li>
            <li>Cumpra prazos e instruções de pagamento.</li>
            <li>Atrasos podem limitar novas acções na conta.</li>
        </ul>

        <h2>11. Conteúdos e marca</h2>
        <p>
            Ao publicar fotos ou textos, confirma que tem direito a usá-los e autoriza a exibição na plataforma.
            Não copie a marca Imobil Fácil nem o software sem permissão.
        </p>

        <h2>12. Conduta proibida</h2>
        <ul>
            <li>Violar a lei ou direitos de terceiros;</li>
            <li>Assédio, discriminação, spam;</li>
            <li>Ataques ao sistema ou automação não autorizada;</li>
            <li>Burlar regras de segurança ou comissões;</li>
            <li>Phishing ou conteúdo malicioso.</li>
        </ul>

        <h2>13. Responsabilidade</h2>
        <p>
            Prestamos o serviço com diligência, mas podem ocorrer interrupções. Não garantimos que todo o negócio se concretiza.
            Litígios entre comprador e vendedor são, em primeira linha, entre essas partes, nos limites que a lei permitir à operadora.
        </p>

        <h2>14. Conflitos</h2>
        <p>
            Use primeiro as ferramentas da plataforma (disputa, suporte, equipa financeira).
            Pode recorrer aos meios de defesa do consumidor e, se necessário, aos tribunais competentes em Angola.
        </p>

        <h2>15. Dados pessoais</h2>
        <p>
            Ver <a href="<?php echo DIRPAGE; ?>privacidade">Política de Privacidade</a>.
            Ao criar conta, autoriza o tratamento necessário ao serviço; outros tratamentos dependem de consentimento ou de outra base legal indicada nessa política.
        </p>

        <h2>16. Alterações</h2>
        <p>
            Podemos alterar termos ou preços com aviso quando a mudança for importante.
            O uso após a data anunciada implica aceitação; pode encerrar a conta antes.
        </p>

        <h2>17. Contacto</h2>
        <p>
            Suporte no painel ou contactos oficiais da Pague Fácil.
            Reclamações sobre dados: Agência de Protecção de Dados (APD).
        </p>

        <h2>18. Leia também</h2>
        <ul>
            <li><a href="<?php echo DIRPAGE; ?>privacidade">Política de Privacidade</a></li>
            <li><a href="<?php echo DIRPAGE; ?>cookies">Política de Cookies</a></li>
        </ul>

        <?php include DIRREQ . 'app/view/partials/legal_references_block.php'; ?>
    </article>
</div>
