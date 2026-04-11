<?php
$type = $_GET['type'] ?? '';
$purpose = $_GET['purpose'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$location = $_GET['location'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int) ($page ?? ($_GET['page'] ?? 1)));
$perPage = max(1, (int) ($perPage ?? 12));
$totalPages = max(1, (int) ($totalPages ?? 1));
$totalProperties = (int) ($totalProperties ?? (is_array($properties ?? null) ? count($properties) : 0));
$resultCount = $totalProperties;
$visibleCount = is_array($properties ?? null) ? count($properties) : 0;
$rangeStart = $resultCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd = $resultCount > 0 ? min($resultCount, $rangeStart + $visibleCount - 1) : 0;
$queryParams = $_GET;
$buildPageUrl = static function (int $targetPage) use ($queryParams): string {
    $params = $queryParams;
    $params['page'] = $targetPage;

    return DIRPAGE . 'properties?' . http_build_query($params);
};
$sponsored   = array_values(array_filter($properties ?? [], fn($p) => !empty($p['featured'])));
$regular     = array_values(array_filter($properties ?? [], fn($p) =>  empty($p['featured'])));
?>

<section class="container sales-page-head">
    <div class="sales-head-copy">
        <span class="sales-kicker">Pipeline comercial</span>
        <h1>Imoveis Disponiveis</h1>
        <p>Filtre oportunidades com maior potencial de venda e avance para proposta mais rapido.</p>
    </div>
    <div class="sales-head-summary">
        <strong><?php echo number_format($resultCount, 0, ',', '.'); ?></strong>
        <span>oportunidades encontradas</span>
    </div>
</section>

<section class="container sales-results-toolbar">
    <div class="sales-results-copy">
        <strong><?php echo $resultCount > 0 ? ('Mostrando ' . number_format($rangeStart, 0, ',', '.') . ' a ' . number_format($rangeEnd, 0, ',', '.') . ' de ' . number_format($resultCount, 0, ',', '.')) : 'Sem resultados'; ?></strong>
        <span><?php echo $resultCount > 0 ? 'Curadoria comercial com leitura rapida de inventario e confianca do proprietario.' : 'Ajuste os filtros e abra mais inventario disponivel.'; ?></span>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="sales-pagination-inline">
            <span>Pagina <?php echo $page; ?> de <?php echo $totalPages; ?></span>
            <?php if ($page > 1): ?>
                <a href="<?php echo htmlspecialchars($buildPageUrl($page - 1)); ?>" class="btn-secondary">&larr; Anterior</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a href="<?php echo htmlspecialchars($buildPageUrl($page + 1)); ?>" class="btn-secondary">Proxima &rarr;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<section class="container sales-filter-wrap">
    <form method="GET" action="<?php echo DIRPAGE; ?>properties" class="sales-filter-form">
        <div class="sales-filter-grid">
            <label>
                <span>Tipo</span>
                <select name="type">
                    <option value="">Todos</option>
                    <option value="casa"<?php echo $type === 'casa' ? ' selected' : ''; ?>>Casa</option>
                    <option value="apartamento"<?php echo $type === 'apartamento' ? ' selected' : ''; ?>>Apartamento</option>
                    <option value="terreno"<?php echo $type === 'terreno' ? ' selected' : ''; ?>>Terreno</option>
                </select>
            </label>
            <label>
                <span>Finalidade</span>
                <select name="purpose">
                    <option value="">Qualquer</option>
                    <option value="venda"<?php echo $purpose === 'venda' ? ' selected' : ''; ?>>Venda</option>
                    <option value="aluguer_curto"<?php echo $purpose === 'aluguer_curto' ? ' selected' : ''; ?>>Aluguer curto</option>
                    <option value="aluguer_longo"<?php echo $purpose === 'aluguer_longo' ? ' selected' : ''; ?>>Aluguer longo</option>
                </select>
            </label>
            <label>
                <span>Preco minimo</span>
                <input type="number" name="min_price" placeholder="Ex: 50000" value="<?php echo htmlspecialchars((string) $minPrice); ?>">
            </label>
            <label>
                <span>Preco maximo</span>
                <input type="number" name="max_price" placeholder="Ex: 300000" value="<?php echo htmlspecialchars((string) $maxPrice); ?>">
            </label>
            <label>
                <span>Ordenacao</span>
                <select name="sort">
                    <option value="newest"<?php echo $sort === 'newest' ? ' selected' : ''; ?>>Mais recentes</option>
                    <option value="price_asc"<?php echo $sort === 'price_asc' ? ' selected' : ''; ?>>Menor preco</option>
                    <option value="price_desc"<?php echo $sort === 'price_desc' ? ' selected' : ''; ?>>Maior preco</option>
                    <option value="oldest"<?php echo $sort === 'oldest' ? ' selected' : ''; ?>>Mais antigos</option>
                </select>
            </label>
            <label class="sales-filter-location">
                <span>Localizacao</span>
                <input type="text" name="location" placeholder="Bairro, cidade, zona" value="<?php echo htmlspecialchars((string) $location); ?>">
            </label>
        </div>
        <div class="sales-filter-actions">
            <button type="submit" class="btn-primary">Aplicar filtros</button>
            <a href="<?php echo DIRPAGE; ?>properties" class="btn-secondary">Limpar</a>
        </div>
    </form>
</section>

<?php if ($totalPages > 1): ?>
<section class="container sales-pagination-section">
    <div class="sales-pagination-inline sales-pagination-inline-bottom">
        <?php if ($page > 1): ?>
            <a href="<?php echo htmlspecialchars($buildPageUrl($page - 1)); ?>" class="btn-secondary">&larr; Anterior</a>
        <?php endif; ?>
        <span>Pagina <?php echo $page; ?> de <?php echo $totalPages; ?></span>
        <?php if ($page < $totalPages): ?>
            <a href="<?php echo htmlspecialchars($buildPageUrl($page + 1)); ?>" class="btn-secondary">Proxima &rarr;</a>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($sponsored)): ?>
<section class="sales-premium-strip-wrap">
    <div class="container">
        <div class="sales-premium-strip-head">
            <div class="sales-premium-strip-title">
                <i class="fa fa-bullhorn"></i>
                <span>Patrocinados em Destaque</span>
                <span class="sales-premium-badge">Prioridade Maxima</span>
            </div>
            <small class="sales-sponsorship-note">Anuncios pagos por proprietarios para garantir maxima visibilidade.</small>
        </div>
        <div class="sales-premium-rail">
            <?php foreach ($sponsored as $sp): ?>
                <?php
                $spImages = json_decode((string) ($sp['images'] ?? '[]'), true);
                $spFirstImage = (is_array($spImages) && !empty($spImages[0])) ? (string) $spImages[0] : '';
                if ($spFirstImage !== '' && !preg_match('#^https?://#i', $spFirstImage)) {
                    $spFirstImage = DIRPAGE . ltrim($spFirstImage, '/');
                }
                $spCoverImage = $spFirstImage !== '' ? $spFirstImage : (DIRIMG . 'placeholder.jpg');
                ?>
                <article class="sales-premium-card">
                    <div class="sales-premium-card-img">
                        <img src="<?php echo htmlspecialchars($spCoverImage); ?>" alt="<?php echo htmlspecialchars($sp['title']); ?>">
                    </div>
                    <div class="sales-premium-card-body">
                        <span class="sales-card-badge"><i class="fa fa-bullhorn"></i> Patrocinado</span>
                        <h3><?php echo htmlspecialchars($sp['title']); ?></h3>
                        <p class="sales-location"><i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($sp['location']); ?></p>
                        <p class="sales-price"><?php echo number_format((float) $sp['price'], 0, ',', '.'); ?> Kz</p>
                        <div class="sales-meta-row">
                            <span><i class="fa fa-bed"></i> <?php echo (int) $sp['bedrooms']; ?> quartos</span>
                            <span><i class="fa fa-bath"></i> <?php echo (int) $sp['bathrooms']; ?> banhos</span>
                        </div>
                        <a href="<?php echo DIRPAGE; ?>property/<?php echo (int) $sp['id']; ?>" class="btn-primary sales-card-cta">Ver detalhes</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="container sales-listing-section">
    <?php if (!empty($sponsored)): ?>
        <p class="sales-grid-section-title">Outros imoveis</p>
    <?php endif; ?>
    <div class="sales-property-grid">
        <?php if (!empty($regular)): ?>
            <?php foreach ($regular as $property): ?>
                <?php
                $regularImages = json_decode((string) ($property['images'] ?? '[]'), true);
                $regularFirstImage = (is_array($regularImages) && !empty($regularImages[0])) ? (string) $regularImages[0] : '';
                if ($regularFirstImage !== '' && !preg_match('#^https?://#i', $regularFirstImage)) {
                    $regularFirstImage = DIRPAGE . ltrim($regularFirstImage, '/');
                }
                $regularCoverImage = $regularFirstImage !== '' ? $regularFirstImage : (DIRIMG . 'placeholder.jpg');
                ?>
                <article class="sales-property-card">
                    <div class="sales-property-media">
                        <img src="<?php echo htmlspecialchars($regularCoverImage); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                        <?php if (!empty($property['featured'])): ?>
                            <span class="sales-card-badge"><i class="fa fa-bullhorn"></i> Patrocinado</span>
                        <?php elseif (!empty($property['video_url'])): ?>
                            <span class="sales-card-badge"><i class="fa fa-video-camera"></i> Video</span>
                        <?php endif; ?>
                    </div>

                    <div class="sales-property-body">
                        <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                        <p class="sales-location"><i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($property['location']); ?></p>
                        <p class="sales-price"><?php echo number_format((float) $property['price'], 0, ',', '.'); ?> Kz</p>

                        <div class="sales-meta-row">
                            <span><i class="fa fa-bed"></i> <?php echo (int) $property['bedrooms']; ?> quartos</span>
                            <span><i class="fa fa-bath"></i> <?php echo (int) $property['bathrooms']; ?> banhos</span>
                        </div>

                        <div class="sales-trust-row">
                            <span><i class="fa fa-user"></i> <?php echo !empty($property['owner_name']) ? htmlspecialchars($property['owner_name']) : 'Nao informado'; ?></span>
                            <?php if (!empty($property['owner_verified'])): ?>
                                <span class="sales-trust-chip verified"><i class="fa fa-shield"></i> Verificado</span>
                            <?php endif; ?>
                            <?php if (!empty($property['owner_trusted'])): ?>
                                <span class="sales-trust-chip trusted"><i class="fa fa-check-circle"></i> Confianca</span>
                            <?php endif; ?>
                        </div>

                        <a href="<?php echo DIRPAGE; ?>property/<?php echo (int) $property['id']; ?>" class="btn-primary sales-card-cta">Ver detalhes e negociar</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="sales-empty-state">
                <i class="fa fa-search"></i>
                <h3>Nenhum outro imovel encontrado</h3>
                <p><?php echo !empty($sponsored) ? 'Consulte os patrocinados em destaque acima, ou ajuste os filtros para ampliar a pesquisa.' : 'Ajuste os filtros para ampliar a pesquisa e desbloquear novas oportunidades.'; ?></p>
                <a href="<?php echo DIRPAGE; ?>properties" class="btn-primary">Reiniciar pesquisa</a>
            </div>
        <?php endif; ?>
    </div>
</section>
