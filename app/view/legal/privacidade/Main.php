<?php
$legalKicker = 'Base legal';
$legalTitle = 'Política de Privacidade';
$legalLead = 'Como a Imobil Fácil recolhe, utiliza e protege os seus dados pessoais no contexto da negociação imobiliária em Angola.';
?>
<div class="container legal-page">
    <?php include DIRREQ . 'app/view/partials/legal_page_hero.php'; ?>

    <article class="dashboard-module-card legal-document" style="padding:1.5rem 2rem; margin-bottom:2rem;">
        <p class="legal-meta">Última atualização: junho de 2026 · Responsável: <strong>Pague Fácil, Comércio e Serviços, LDA</strong> (operador da plataforma <strong>Imobil Fácil</strong>)</p>

        <h2>1. Âmbito</h2>
        <p>
            Esta política aplica-se a visitantes e utilizadores registados da Imobil Fácil — plataforma digital
            para publicação, descoberta e negociação de imóveis em Angola, com gestão de solicitações,
            afiliação, comissões e comunicação entre partes.
        </p>

        <h2>2. Responsável pelo tratamento</h2>
        <p>
            O tratamento de dados pessoais é realizado pela <strong>Pague Fácil, Comércio e Serviços, LDA</strong>,
            enquanto entidade que explora a plataforma Imobil Fácil. Para exercício de direitos ou questões de privacidade,
            utilize os canais de suporte disponíveis na área autenticada ou o endereço de contacto indicado nas comunicações oficiais da plataforma.
        </p>

        <h2>3. Categorias de dados tratados</h2>
        <p>Consoante a sua utilização do serviço, podemos tratar:</p>
        <ul>
            <li><strong>Identificação e conta:</strong> nome, e-mail, telefone, nome de utilizador, palavra-passe (armazenada de forma segura), tipo de perfil (particular ou empresa), documentos de verificação quando exigidos.</li>
            <li><strong>Imóveis e negócio:</strong> título, descrição, localização, preço, imagens, vídeos, estado do anúncio, dados de proprietário/afiliado associados ao imóvel.</li>
            <li><strong>Solicitações e mensagens:</strong> intenção de compra ou aluguer, histórico de estados, chat interno associado ao pedido, comprovativos de pagamento quando aplicável.</li>
            <li><strong>Financeiro e comissões:</strong> referências de pagamento manual, transações registadas na plataforma, contas de recebimento configuradas por utilizadores autorizados.</li>
            <li><strong>Navegação e preferências:</strong> sessão, endereço IP, identificador de visitante (<code>visitor_key</code>), eventos de interação (visualizações, favoritos, pedidos), preferência de cookies de personalização.</li>
            <li><strong>Segurança:</strong> registos de tentativas de acesso, tokens de API quando utilizados, logs técnicos com identificador de pedido (<code>request_id</code>) para diagnóstico de incidentes.</li>
        </ul>

        <h2>4. Finalidades e bases legais</h2>
        <table class="legal-table">
            <thead>
                <tr>
                    <th>Finalidade</th>
                    <th>Base legal (resumo)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Criar e gerir a sua conta, autenticação e recuperação de acesso</td>
                    <td>Execução de contrato / diligências pré-contratuais</td>
                </tr>
                <tr>
                    <td>Publicar imóveis, processar solicitações e mensagens entre partes</td>
                    <td>Execução de contrato</td>
                </tr>
                <tr>
                    <td>Calcular, emitir e acompanhar comissões e obrigações financeiras da plataforma</td>
                    <td>Execução de contrato e cumprimento de obrigações legais</td>
                </tr>
                <tr>
                    <td>Moderar conteúdos, prevenir fraude e garantir integridade comercial</td>
                    <td>Interesse legítimo e obrigações legais</td>
                </tr>
                <tr>
                    <td>Enviar notificações operacionais (estado de pedidos, comissões, conta)</td>
                    <td>Execução de contrato / interesse legítimo</td>
                </tr>
                <tr>
                    <td>Personalizar a ordem de imóveis apresentados (ranking comportamental)</td>
                    <td>Consentimento (cookies de personalização) ou interesse legítimo limitado para utilizadores autenticados conforme configuração</td>
                </tr>
                <tr>
                    <td>Cookies essenciais (sessão, segurança, CSRF)</td>
                    <td>Necessidade técnica para prestação do serviço</td>
                </tr>
            </tbody>
        </table>
        <p>
            O detalhe sobre cookies encontra-se na
            <a href="<?php echo DIRPAGE; ?>cookies">Política de Cookies</a>.
        </p>

        <h2>5. Ranking comportamental e visitantes anónimos</h2>
        <p>
            A plataforma pode registar eventos como visualização de imóvel, favorito ou criação de solicitação,
            associados ao seu <code>user_id</code> (se autenticado) e/ou a um identificador de sessão anónimo.
            Estes sinais servem para melhorar a relevância das listagens, sem substituir critérios comerciais
            (destaques patrocinados, disponibilidade e regras de visibilidade mantêm prioridade).
            Visitantes sem conta só são perfilados de forma comportamental após consentimento explícito para cookies de personalização.
        </p>

        <h2>6. Partilha de dados</h2>
        <p>Os seus dados podem ser visíveis ou partilhados nas seguintes situações:</p>
        <ul>
            <li><strong>Entre utilizadores da negociação:</strong> proprietário, interessado e afiliado envolvido numa solicitação veem informação necessária ao processo (ex.: contacto após regras da plataforma, estado do pedido).</li>
            <li><strong>Perfis públicos:</strong> páginas de agência ou promotor institucional exibem dados que o utilizador optou por tornar públicos no âmbito do plano.</li>
            <li><strong>Prestadores técnicos:</strong> alojamento, e-mail (SMTP), processamento de imagens em fila — apenas na medida necessária e com obrigações de confidencialidade.</li>
            <li><strong>Autoridades:</strong> quando exigido por lei ou decisão judicial válida.</li>
        </ul>
        <p>Não vendemos os seus dados pessoais a terceiros para marketing externo.</p>

        <h2>7. Conservação</h2>
        <p>
            Os dados são conservados pelo tempo necessário à finalidade que motivou a recolha: vigência da conta,
            cumprimento de obrigações comerciais e fiscais, resolução de disputas e prazos legais aplicáveis.
            Registos de auditoria e eventos críticos de negócio podem ser mantidos por períodos superiores quando
            necessários para defesa de direitos ou conformidade do modelo de comissões.
        </p>

        <h2>8. Segurança</h2>
        <p>
            Adotamos medidas técnicas e organizativas adequadas, incluindo controlo de acesso por perfil,
            proteção CSRF em alterações de estado, limitação de tentativas de login, validação de documentos
            e registo de operações sensíveis. Nenhum sistema é absolutamente inviolável; em caso de incidente
            relevante, adotaremos medidas de mitigação e comunicação conforme a lei aplicável.
        </p>

        <h2>9. Os seus direitos</h2>
        <p>Nos termos da legislação de proteção de dados aplicável em Angola, pode solicitar, entre outros:</p>
        <ul>
            <li>Acesso e cópia dos dados que tratamos sobre si;</li>
            <li>Retificação de dados inexatos ou incompletos;</li>
            <li>Eliminação ou limitação do tratamento, quando aplicável;</li>
            <li>Oposição a tratamentos baseados em interesse legítimo, quando previsto;</li>
            <li>Retirada do consentimento (ex.: cookies de personalização), sem afetar tratamentos já realizados licitamente.</li>
        </ul>
        <p>
            Pedidos devem ser enviados através dos canais oficiais de suporte, identificando a conta associada.
            Podemos solicitar informação adicional para confirmar a sua identidade antes de responder.
        </p>

        <h2>10. Menores</h2>
        <p>
            A plataforma destina-se a utilizadores com capacidade legal para contratar. Não recolhemos
            intencionalmente dados de menores sem autorização parental adequada.
        </p>

        <h2>11. Transferências internacionais</h2>
        <p>
            Os dados são tratados preferencialmente em infraestrutura compatível com a operação em Angola.
            Se algum subcontratante processar dados fora do país, garantimos salvaguardas contratuais adequadas.
        </p>

        <h2>12. Alterações</h2>
        <p>
            Esta política pode ser atualizada para refletir novas funcionalidades ou requisitos legais.
            A data de revisão no topo da página será alterada; alterações materiais podem ser comunicadas
            na plataforma ou por e-mail aos utilizadores registados.
        </p>

        <h2>13. Documentos relacionados</h2>
        <ul>
            <li><a href="<?php echo DIRPAGE; ?>cookies">Política de Cookies</a></li>
            <li><a href="<?php echo DIRPAGE; ?>termos">Termos e Condições de Utilização</a></li>
        </ul>
    </article>
</div>
