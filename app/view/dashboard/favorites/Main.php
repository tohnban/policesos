<?php
$propertyCount = is_array($properties ?? null) ? count($properties) : 0;
?>

<div class="container dashboard-view my-favorites-dashboard-view">
    <section class="dashboard-view-hero compact">
        <div>
            <span class="dashboard-hero-kicker">Coleção</span>
            <h1>Meus Favoritos</h1>
            <p>Imóveis que você guardou para consultar mais tarde.</p>
        </div>
    </section>

    <section class="dashboard-overview-grid dashboard-overview-grid-tight dashboard-overview-section-gap my-favorites-kpis">
        <div class="kpi-card">
            <div class="kpi-label">Imóveis guardados</div>
            <div class="kpi-value"><?php echo $propertyCount; ?></div>
        </div>
    </section>

    <?php if (empty($properties)): ?>
        <div class="dashboard-module-card">
            <div class="dashboard-module-head compact">
                <div>
                    <span class="dashboard-module-kicker">Vazio</span>
                    <h3>Nenhum imóvel favorito ainda</h3>
                </div>
            </div>
            <p class="dashboard-inline-note">Navegue pelos imóveis disponíveis e clique no ícone de coração para guardar os que lhe interessam.</p>
            <div class="dashboard-inline-actions dashboard-empty-actions">
                <a href="<?php echo DIRPAGE; ?>properties" class="btn-primary">Ver imóveis</a>
            </div>
        </div>
    <?php else: ?>
        <div class="dashboard-properties-grid">
            <?php foreach ($properties as $property): ?>
                <?php
                $propertyId = (int) ($property['id'] ?? 0);
                $propertyStatus = (string) ($property['status'] ?? 'pendente');
                $statusClassMap = [
                    'disponivel' => 'dashboard-chip-success',
                    'pendente'   => 'dashboard-chip-warning',
                    'rejeitado'  => 'dashboard-chip-danger',
                    'suspenso'   => 'dashboard-chip-danger',
                    'vendido'    => 'dashboard-chip',
                    'alugado'    => 'dashboard-chip',
                ];
                $statusClass = $statusClassMap[$propertyStatus] ?? 'dashboard-chip';
                $propertyImages = json_decode((string) ($property['images'] ?? '[]'), true);
                $propertyFirstImage = (is_array($propertyImages) && !empty($propertyImages[0])) ? (string) $propertyImages[0] : '';
                if ($propertyFirstImage !== '' && !preg_match('#^https?://#i', $propertyFirstImage)) {
                    $propertyFirstImage = DIRPAGE . ltrim($propertyFirstImage, '/');
                }
                $propertyCover = $propertyFirstImage !== '' ? $propertyFirstImage : (DIRIMG . 'apt20.avif');
                $purposeLabel = ucfirst(str_replace('_', ' ', (string) ($property['purpose'] ?? 'nao informado')));
                $typeLabel = Src\classes\PropertyTypeHelper::getLabel($property['type'] ?? null);
                ?>
                <article class="dashboard-property-card">
                    <div class="dashboard-property-media">
                        <img src="<?php echo htmlspecialchars($propertyCover); ?>" alt="<?php echo htmlspecialchars((string) ($property['title'] ?? 'Imóvel')); ?>">
                    </div>

                    <div class="dashboard-property-head">
                        <div>
                            <h5 class="card-title"><?php echo htmlspecialchars((string) ($property['title'] ?? 'Sem título')); ?></h5>
                            <p class="dashboard-inline-note"><?php echo htmlspecialchars((string) ($property['location'] ?? 'Localização não informada')); ?></p>
                        </div>
                        <span class="dashboard-chip <?php echo $statusClass; ?>"><?php echo ucfirst($propertyStatus); ?></span>
                    </div>

                    <div class="dashboard-property-price"><?php echo number_format((float) ($property['price'] ?? 0), 0, ',', '.'); ?> Kz</div>

                    <div class="dashboard-property-meta-grid">
                        <div>
                            <span>Tipo</span>
                            <strong><?php echo htmlspecialchars($typeLabel); ?></strong>
                        </div>
                        <div>
                            <span>Finalidade</span>
                            <strong><?php echo htmlspecialchars($purposeLabel); ?></strong>
                        </div>
                        <div>
                            <span>Quartos</span>
                            <strong><?php echo (int) ($property['bedrooms'] ?? 0); ?></strong>
                        </div>
                        <div>
                            <span>Banhos</span>
                            <strong><?php echo (int) ($property['bathrooms'] ?? 0); ?></strong>
                        </div>
                    </div>

                    <div class="dashboard-inline-actions dashboard-property-actions">
                        <a href="<?php echo DIRPAGE; ?>property/<?php echo $propertyId; ?>" class="btn-primary">Solicitar agora</a>
                        <form method="POST" action="<?php echo DIRPAGE; ?>property/unfavorite/<?php echo $propertyId; ?>" class="request-actions">
                            <?php echo Src\classes\ClassCsrf::field(); ?>
                            <button type="submit" class="btn-secondary" data-confirm="Remover este imóvel dos favoritos?">
                                <i class="fa fa-heart-o"></i> Remover
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
