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
            <span class="sales-kicker">Imobil Fácil — Imóveis verificados em Angola</span>
            <h1>O seu próximo imóvel está aqui. Encontre, visite e feche negócio.</h1>
            <p>Imóveis residenciais, comerciais, industriais, terrenos e opções de turismo com proprietários confirmados. Sem surpresas, sem intermediários desnecessários.</p>
            <div class="sales-hero-proof">
                <span><i class="fa fa-check-circle"></i> Proprietários verificados</span>
                <span><i class="fa fa-shield"></i> Pagamento seguro via Pague Fácil</span>
                <span><i class="fa fa-bolt"></i> Resposta em menos de 24h</span>
            </div>
            <div class="sales-hero-actions">
                <a href="<?php echo DIRPAGE; ?>properties" class="btn-primary">Ver imóveis disponíveis agora</a>
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
                <strong>Imóveis saem rapidamente</strong>
                <span>Os melhores imóveis são reservados em poucos dias. Não deixe para amanhã.</span>
            </div>
            <p>Registe-se gratuitamente e receba alertas dos novos imóveis primeiro.</p>
        </aside>
    </div>
</section>

<section class="sales-strip">
    <div class="container sales-strip-grid">
        <article>
            <i class="fa fa-check-circle"></i>
            <div>
                <strong>Sabe exatamente o que está a comprar</strong>
                <span>Todos os imóveis passam por verificação antes de aparecerem aqui.</span>
            </div>
        </article>
        <article>
            <i class="fa fa-user-circle"></i>
            <div>
                <strong>Fala diretamente com o proprietário</strong>
                <span>Sem intermediários. Contacto direto, negociação mais rápida.</span>
            </div>
        </article>
        <article>
            <i class="fa fa-lock"></i>
            <div>
                <strong>O seu dinheiro está protegido</strong>
                <span>Pagamentos processados pela Pague Fácil, com registo e confirmação.</span>
            </div>
        </article>
        <article>
            <i class="fa fa-bell"></i>
            <div>
                <strong>Não perde nenhuma oportunidade</strong>
                <span>Receba alertas quando entrar um imóvel que corresponde ao que procura.</span>
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
            <p>Selecionados com mais atenção — proprietários confirmados, fotos reais e resposta rápida garantida.</p>
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
            ? 'Combinação dinâmica de imóveis para si — inclui patrocinados misturados, identificados com o selo Patrocinado.'
            : 'Imóveis com proprietários verificados e alto interesse — os primeiros a ir são sempre os melhores.'; ?></p>

        <?php if (!empty($featuredProperties)): ?>
            <div class="sales-carousel-viewport" id="homeSalesViewport">
                <div class="sales-carousel-track" id="homeSalesTrack">
                    <?php foreach ($featuredProperties as $idx => $property): ?>
                        <?php
                        $position = (int) $idx + 1;
                        $isFav = in_array((int) ($property['id'] ?? 0), $favoriteIds ?? [], true);
                        $createdAt = strtotime((string) ($property['created_at'] ?? ''));
                        $daysDiff = $createdAt > 0 ? (int) floor((time() - $createdAt) / 86400) : 99;
                        if ($daysDiff === 0) {
                            $urgencyText = 'Publicado hoje';
                        } elseif ($daysDiff === 1) {
                            $urgencyText = 'Publicado ontem';
                        } elseif ($daysDiff <= 7) {
                            $urgencyText = 'Publicado há ' . $daysDiff . ' dias';
                        } else {
                            $urgencyTags = ['Alta procura', 'Muito procurado', 'Preço competitivo'];
                            $urgencyText = $urgencyTags[$idx % count($urgencyTags)];
                        }
                        $imagesList = json_decode((string) ($property['images'] ?? '[]'), true);
                        $firstImage = (is_array($imagesList) && !empty($imagesList[0])) ? (string) $imagesList[0] : '';
                        if ($firstImage !== '' && !preg_match('#^https?://#i', $firstImage)) {
                            $firstImage = DIRPAGE . ltrim($firstImage, '/');
                        }
                        $coverImage = $firstImage !== '' ? $firstImage : (DIRIMG . 'apt20.avif');
                        $ownerPhoneDigits = preg_replace('/\D+/', '', (string) ($property['owner_phone'] ?? ''));
                        if ($ownerPhoneDigits !== '' && strpos($ownerPhoneDigits, '244') !== 0) {
                            $ownerPhoneDigits = '244' . $ownerPhoneDigits;
                        }
                        $waMessage = rawurlencode('Ola, vi o imovel ' . ($property['title'] ?? '') . ' em ' . DIRPAGE . ' e quero negociar.');
                        $waLink = $ownerPhoneDigits !== '' ? ('https://wa.me/' . $ownerPhoneDigits . '?text=' . $waMessage) : '';
                        ?>
                        <article class="sales-carousel-card">
                            <div class="sales-carousel-img">
                                <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                                <?php if (Src\classes\ClassAuth::check() && !(Src\classes\ClassAuth::user()['is_admin'] ?? false)): ?>
                                    <form method="POST" action="<?php echo DIRPAGE; ?>property/<?php echo $isFav ? 'unfavorite' : 'favorite'; ?>/<?php echo (int) $property['id']; ?>" class="favorite-form-inline favorite-overlay favorite-overlay-rank-shift">
                                        <?php echo Src\classes\ClassCsrf::field(); ?>
                                        <button type="submit" class="btn-favorite<?php echo $isFav ? ' is-active' : ''; ?>" title="<?php echo $isFav ? 'Remover dos favoritos' : 'Guardar nos favoritos'; ?>" aria-label="<?php echo $isFav ? 'Remover dos favoritos' : 'Guardar nos favoritos'; ?>">
                                            <i class="fa <?php echo $isFav ? 'fa-heart' : 'fa-heart-o'; ?>"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if (!empty($property['featured'])): ?>
                                <span class="sales-card-badge"><i class="fa fa-bullhorn"></i> Patrocinado</span>
                                <?php endif; ?>
                                <?php if ($position <= 3): ?>
                                    <span class="sales-rank-badge">TOP <?php echo $position; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="sales-carousel-card-body">
                                <div class="sales-carousel-tags">
                                    <span class="sales-urgency-tag"><i class="fa <?php echo $daysDiff <= 1 ? 'fa-clock-o' : ($daysDiff <= 7 ? 'fa-calendar' : 'fa-fire'); ?>"></i> <?php echo $urgencyText; ?></span>
                                </div>
                                <h3><a href="<?php echo DIRPAGE; ?>property/<?php echo (int) $property['id']; ?>" class="card-title-link"><?php echo htmlspecialchars($property['title']); ?></a></h3>
                                <p class="sales-location"><i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($property['location']); ?></p>
                                <p class="sales-price"><?php echo number_format((float) $property['price'], 0, ',', '.'); ?> Kz</p>
                                <div class="sales-meta-row">
                                    <span><i class="fa fa-bed"></i> <?php echo (int) $property['bedrooms']; ?> quartos</span>
                                    <span><i class="fa fa-bath"></i> <?php echo (int) $property['bathrooms']; ?> banhos</span>
                                </div>
                                <div class="sales-carousel-actions">
                                    <a href="<?php echo DIRPAGE; ?>property/<?php echo (int) $property['id']; ?>" class="btn-primary sales-card-cta">Ver este imóvel</a>
                                    <?php if ($waLink !== ''): ?>
                                        <a href="<?php echo htmlspecialchars($waLink); ?>" target="_blank" rel="noopener noreferrer" class="btn-secondary sales-card-cta sales-wa-cta">
                                            <i class="fa fa-whatsapp"></i> Contactar agora
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="sales-carousel-dots" id="homeSalesDots"></div>
        <?php else: ?>
            <div class="sales-empty-state">
                <i class="fa fa-home"></i>
                <h3>Ainda sem destaques</h3>
                <p>Novos imóveis entram todos os dias. Veja a listagem completa e encontre o seu.</p>
                <a href="<?php echo DIRPAGE; ?>properties" class="btn-primary">Ver imóveis disponíveis agora</a>
            </div>
        <?php endif; ?>
    </section>
</section>

</div>

