<?php
$page = (int) ($page ?? 1);
$perPage = (int) ($perPage ?? 12);
$totalPages = (int) ($totalPages ?? 1);
$totalFeatured = (int) ($totalFeatured ?? (is_array($properties ?? null) ? count($properties) : 0));
$visibleCount = is_array($properties ?? null) ? count($properties) : 0;
$rangeStart = $totalFeatured > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd = $totalFeatured > 0 ? min($totalFeatured, $rangeStart + $visibleCount - 1) : 0;
$buildPageUrl = static function (int $targetPage): string {
    return DIRPAGE . 'featured?page=' . $targetPage;
};
$featuredCount = $totalFeatured;
$discoveryPersonalized = !empty($discoveryPersonalized) || !empty($data['discoveryPersonalized']);
$continueExploring = isset($continueExploring) && is_array($continueExploring)
    ? $continueExploring
    : (($data['continueExploring'] ?? []) ?: []);
$partialGridCard = DIRREQ . 'app/view/partials/property_grid_card.php';
$partialCarouselCard = DIRREQ . 'app/view/partials/property_carousel_card.php';
$typeLabels = Src\classes\PropertyTypeHelper::getTypeLabels();
$formatType = static function (?string $value) use ($typeLabels): string {
    return $typeLabels[$value ?? ''] ?? 'Tipo nao definido';
};
$purposeLabels = [
    'venda' => 'Venda',
    'aluguer_curto' => 'Aluguer curto',
    'aluguer_longo' => 'Aluguer longo',
];
$formatPurpose = static function (?string $value) use ($purposeLabels): string {
    return $purposeLabels[$value ?? ''] ?? 'Finalidade nao definida';
};
?>

<div class="featured-page-view">

<section class="container sales-page-head featured-page-head">
    <div class="sales-head-copy">
        <span class="sales-kicker">Imóveis em Destaque</span>
        <h1><?php echo $discoveryPersonalized ? 'Destaques Selecionados para Si' : 'Os Mais Procurados Agora'; ?></h1>
        <p><?php echo $discoveryPersonalized
            ? 'Patrocinados reordenados com base no que já explorou — a lista muda entre visitas.'
            : 'Os imóveis mais completos da plataforma — proprietários verificados, fotos reais e resposta garantida.'; ?></p>
        <small class="sales-sponsorship-note"><?php echo $discoveryPersonalized
            ? 'Todos com selo Patrocinado; a ordem é personalizada pelo seu comportamento na plataforma.'
            : 'Estes imóveis foram promovidos para que os encontre com mais facilidade.'; ?></small>
    </div>
    <div class="sales-head-summary">
        <strong><?php echo number_format($featuredCount, 0, ',', '.'); ?></strong>
        <span>imóveis em destaque</span>
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
            if (is_file($partialCarouselCard)) {
                $badgeLabel = 'Visto recentemente';
                $showSponsoredBadge = !empty($property['featured']);
                $position = 0;
                include $partialCarouselCard;
            }
            ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="container sales-results-toolbar">
    <div class="sales-results-copy">
        <strong><?php echo $totalFeatured > 0 ? ('Mostrando ' . number_format($rangeStart, 0, ',', '.') . ' a ' . number_format($rangeEnd, 0, ',', '.') . ' de ' . number_format($totalFeatured, 0, ',', '.')) : 'Sem imóveis em destaque'; ?></strong>
        <span><?php echo $discoveryPersonalized
            ? 'A grelha abaixo varia conforme o seu histórico — menos repetição, mais relevância.'
            : ($totalFeatured > 0 ? 'Os mais bem apresentados neste momento — prontos para visita e negociação.' : 'Nenhum imóvel com destaque neste momento.'); ?></span>
    </div>
</section>

<section class="container featured-intro-band">
    <article>
        <strong>Mais detalhes</strong>
        <span>Imóveis com fotos reais, preços definidos e proprietários prontos a responder.</span>
    </article>
    <article>
        <strong>Sai mais rápido</strong>
        <span>Os imóveis aqui anunciados têm maior procura e saem do mercado mais rapidamente.</span>
    </article>
    <article>
        <strong>Mais contexto</strong>
        <span>Veja preço, localização, quartos e selo de confiança do proprietário — tudo de uma vez.</span>
    </article>
</section>

<section class="container sales-listing-section">
    <?php if ($discoveryPersonalized): ?>
        <p class="sales-grid-section-title">Patrocinados para si — ordem dinâmica com base no seu comportamento</p>
    <?php endif; ?>
    <div class="sales-property-grid">
        <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $property): ?>
                <?php
                if (is_file($partialGridCard)) {
                    include $partialGridCard;
                }
                ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="sales-empty-state">
                <i class="fa fa-star-o"></i>
                <h3>Sem imóveis em destaque agora</h3>
                <p>Enquanto isso, explore todos os imóveis disponíveis e ative alertas.</p>
                <a href="<?php echo DIRPAGE; ?>properties" class="btn-primary">Ir para todos os imóveis</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($totalPages > 1): ?>
<section class="container sales-pagination-section">
    <div class="sales-pagination-inline sales-pagination-inline-bottom">
        <?php if ($page > 1): ?>
            <a href="<?php echo htmlspecialchars($buildPageUrl($page - 1)); ?>" class="btn-secondary">&larr; Anterior</a>
        <?php endif; ?>
        <span>Página <?php echo $page; ?> de <?php echo $totalPages; ?></span>
        <?php if ($page < $totalPages): ?>
            <a href="<?php echo htmlspecialchars($buildPageUrl($page + 1)); ?>" class="btn-secondary">Próxima &rarr;</a>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

</div>
