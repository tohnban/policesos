<?php
$featuredProperties = isset($featuredProperties) && is_array($featuredProperties)
    ? $featuredProperties
    : (($data['featuredProperties'] ?? []) ?: []);

$propertyStats = isset($propertyStats) && is_array($propertyStats)
    ? $propertyStats
    : (($data['propertyStats'] ?? []) ?: []);

$availableCount = (int) ($propertyStats['disponivel'] ?? 0);
$soldCount = (int) ($propertyStats['vendido'] ?? 0);
$rentedCount = (int) ($propertyStats['alugado'] ?? 0);
$approvalRate = $propertyStats['approval_rate'] ?? 0;
$featuredCount = count($featuredProperties);
$continueExploring = isset($continueExploring) && is_array($continueExploring)
    ? $continueExploring
    : (($data['continueExploring'] ?? []) ?: []);
$discoveryPersonalized = !empty($discoveryPersonalized) || !empty($data['discoveryPersonalized']);
$partialCard = DIRREQ . 'app/view/partials/property_carousel_card.php';
?>

<div class="home-page-view<?php echo !empty($continueExploring) ? ' home-has-continue' : ''; ?>">

<section class="sales-hero home-hero">
    <div class="container sales-hero-shell">
        <div class="sales-hero-copy">
            <span class="sales-kicker">Imobil Fácil — imóveis verificados em Angola</span>
            <h1 class="home-hero-title">
                <span class="home-hero-title-line">Negocie com o proprietário</span>
                <span class="home-hero-title-line">do início ao fim,</span>
                <span class="home-hero-title-highlight">Sem intermediários.</span>
            </h1>
            <p class="home-hero-lead">Casas, apartamentos, terrenos e espaços comerciais com donos confirmados.</p>
            <p class="home-hero-lead home-hero-lead-secondary">Contacto directo na plataforma — negocie preço e condições com quem realmente decide.</p>
            <div class="sales-hero-proof">
                <span><i class="fa fa-user-circle"></i> Negociação directa com o proprietário</span>
                <span><i class="fa fa-check-circle"></i> Anúncios verificados antes de publicar</span>
                <span><i class="fa fa-shield"></i> Pagamento seguro com a Pague Fácil</span>
            </div>
            <div class="sales-hero-actions">
                <a href="<?php echo DIRPAGE; ?>properties" class="btn-primary">Ver imóveis e contactar donos</a>
                <a href="<?php echo DIRPAGE; ?>featured" class="btn-secondary">Ver os mais procurados</a>
            </div>
        </div>

        <aside class="sales-hero-panel">
            <h3>O mercado agora</h3>
            <div class="sales-hero-metrics">
                <div>
                    <strong><?php echo number_format($availableCount, 0, ',', '.'); ?></strong>
                    <span>imóveis disponíveis hoje</span>
                </div>
                <div>
                    <strong><?php echo number_format($soldCount + $rentedCount, 0, ',', '.'); ?></strong>
                    <span>negócios já concluídos</span>
                </div>
                <div>
                    <strong><?php echo number_format((float) $approvalRate, 1, ',', '.'); ?>%</strong>
                    <span>dos anúncios são verificados</span>
                </div>
            </div>
            <div class="sales-hero-panel-note">
                <strong>Sem filas nem intermediários</strong>
                <span>O seu pedido chega ao proprietário. A resposta é de quem tem o imóvel — não de um intermediário.</span>
            </div>
            <p>Crie conta gratuita e seja avisado quando entrar um imóvel que combina com o que procura.</p>
        </aside>
    </div>
</section>

<section class="sales-strip">
    <div class="container sales-strip-grid">
        <article>
            <i class="fa fa-user-circle"></i>
            <div>
                <strong>Sem intermediários</strong>
                <span>Trata com o proprietário desde o primeiro contacto. Sem intermediários, sem comissões escondidas.</span>
            </div>
        </article>
        <article>
            <i class="fa fa-comments"></i>
            <div>
                <strong>Negociação transparente</strong>
                <span>Pedidos, mensagens e propostas ficam registados entre si e o dono do imóvel.</span>
            </div>
        </article>
        <article>
            <i class="fa fa-check-circle"></i>
            <div>
                <strong>Proprietários verificados</strong>
                <span>Só entram anúncios de quem confirmou identidade — sabe com quem está a falar.</span>
            </div>
        </article>
        <article>
            <i class="fa fa-lock"></i>
            <div>
                <strong>Pagamento com registo</strong>
                <span>Quando há valores envolvidos, a Pague Fácil processa com confirmação e histórico.</span>
            </div>
        </article>
    </div>
</section>

<?php if (!empty($continueExploring)): ?>
<section class="container sales-section discovery-continue-section">
    <div class="section-header sales-section-header">
        <div>
            <h2>Continuar a explorar</h2>
            <p>Imóveis que já visitou — retome de onde parou.</p>
        </div>
    </div>
    <div class="discovery-continue-grid">
        <?php foreach ($continueExploring as $property): ?>
            <?php
            if (is_file($partialCard)) {
                $badgeLabel = 'Visto recentemente';
                $showSponsoredBadge = false;
                $position = 0;
                include $partialCard;
            }
            ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="container sales-section">
    <div class="section-header sales-section-header">
        <div>
            <h2>Imóveis em Destaque</h2>
            <p>Proprietários confirmados e disponíveis para negociar directamente — sem passar por agência.</p>
        </div>
        <a href="<?php echo DIRPAGE; ?>featured" class="btn-secondary">Ver todos os destaques</a>
    </div>

    <section class="sales-carousel-section">
        <div class="sales-carousel-head">
            <div class="sales-carousel-title">
                <i class="fa fa-star"></i>
                <h2><?php echo $discoveryPersonalized ? 'Selecionados para si' : 'Os mais procurados agora'; ?></h2>
                <span class="sales-premium-badge"><?php echo $discoveryPersonalized ? 'com base no seu interesse' : 'alta procura'; ?></span>
            </div>
            <div class="sales-carousel-nav-btns">
                <button type="button" id="homeSalesPrev" aria-label="Destaque anterior"<?php echo count($featuredProperties) <= 1 ? ' disabled aria-disabled="true"' : ''; ?>><i class="fa fa-chevron-left"></i></button>
                <button type="button" id="homeSalesNext" aria-label="Proximo destaque"<?php echo count($featuredProperties) <= 1 ? ' disabled aria-disabled="true"' : ''; ?>><i class="fa fa-chevron-right"></i></button>
            </div>
        </div>
        <p class="sales-carousel-sub"><?php echo $discoveryPersonalized
            ? 'Sugestões para si — contacte o proprietário directamente. Patrocinados aparecem com selo visível.'
            : 'Alto interesse e donos que respondem — quem contacta primeiro negocia sem intermediários.'; ?></p>

        <?php if (!empty($featuredProperties)): ?>
            <div class="sales-carousel-viewport" id="homeSalesViewport">
                <div class="sales-carousel-track" id="homeSalesTrack">
                    <?php foreach ($featuredProperties as $idx => $property): ?>
                        <?php
                        $position = (int) $idx + 1;
                        $showSponsoredBadge = !empty($property['featured']);
                        if (is_file($partialCard)) {
                            include $partialCard;
                        }
                        ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="sales-carousel-dots" id="homeSalesDots"></div>
        <?php else: ?>
            <div class="sales-empty-state">
                <i class="fa fa-home"></i>
                <h3>Ainda sem destaques</h3>
                <p>Novos anúncios entram todos os dias. Explore a listagem e fale directamente com os proprietários.</p>
                <a href="<?php echo DIRPAGE; ?>properties" class="btn-primary">Ver imóveis sem intermediários</a>
            </div>
        <?php endif; ?>
    </section>
</section>

</div>

