<?php $featuredCount = is_array($properties ?? null) ? count($properties) : 0; ?>

<section class="container sales-page-head featured-page-head">
    <div class="sales-head-copy">
        <span class="sales-kicker">Selecao premium</span>
        <h1>Imoveis em Destaque</h1>
        <p>Inventario patrocinado por proprietarios para ganhar prioridade maxima de visibilidade.</p>
        <small class="sales-sponsorship-note">Todos os ativos desta pagina fazem parte de campanhas pagas de destaque.</small>
    </div>
    <div class="sales-head-summary">
        <strong><?php echo number_format($featuredCount, 0, ',', '.'); ?></strong>
        <span>ativos premium</span>
    </div>
</section>

<section class="container featured-intro-band">
    <article>
        <strong>Inventario premium</strong>
        <span>Activos patrocinados para ganhar mais visibilidade e gerar procura mais qualificada.</span>
    </article>
    <article>
        <strong>Maior urgencia</strong>
        <span>Imoveis pensados para liderar descoberta, contacto e proposta dentro do funil.</span>
    </article>
    <article>
        <strong>Mais contexto</strong>
        <span>Leitura rapida de preco, confianca do proprietario e atributos principais do activo.</span>
    </article>
</section>

<section class="container sales-listing-section">
    <div class="sales-property-grid">
        <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $property): ?>
                <?php
                $imagesList = json_decode((string) ($property['images'] ?? '[]'), true);
                $firstImage = (is_array($imagesList) && !empty($imagesList[0])) ? (string) $imagesList[0] : '';
                if ($firstImage !== '' && !preg_match('#^https?://#i', $firstImage)) {
                    $firstImage = DIRPAGE . ltrim($firstImage, '/');
                }
                $coverImage = $firstImage !== '' ? $firstImage : (DIRIMG . 'placeholder.jpg');
                ?>
                <article class="sales-property-card sales-card-featured">
                    <div class="sales-property-media">
                        <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                        <span class="sales-card-badge"><i class="fa fa-bullhorn"></i> Patrocinado</span>
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

                        <a href="<?php echo DIRPAGE; ?>property/<?php echo (int) $property['id']; ?>" class="btn-primary sales-card-cta">Quero esta oportunidade</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="sales-empty-state">
                <i class="fa fa-star-o"></i>
                <h3>Sem imoveis em destaque agora</h3>
                <p>Enquanto isso, explore todos os imoveis disponiveis e ative alertas.</p>
                <a href="<?php echo DIRPAGE; ?>properties" class="btn-primary">Ir para todos os imoveis</a>
            </div>
        <?php endif; ?>
    </div>
</section>