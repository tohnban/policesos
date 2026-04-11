<?php
$property = $property ?? null;
$status = (string) ($property['status'] ?? 'disponivel');
$statusLabel = [
    'pendente' => 'pendente de aprovacao',
    'rejeitado' => 'rejeitado',
    'suspenso' => 'suspenso',
    'disponivel' => 'disponivel',
][$status] ?? 'em revisao';
$typeLabel = !empty($property['type']) ? ucfirst((string) $property['type']) : 'Nao informado';
$purposeLabel = !empty($property['purpose']) ? ucfirst(str_replace('_', ' ', (string) $property['purpose'])) : 'Nao informado';
$ownerName = !empty($property['owner_name']) ? htmlspecialchars((string) $property['owner_name']) : 'Nao informado';
$hasProperty = !empty($property);
$rawImages = json_decode((string) ($property['images'] ?? '[]'), true);
$galleryImages = [];

if (is_array($rawImages)) {
    foreach ($rawImages as $rawImage) {
        $imagePath = trim((string) $rawImage);
        if ($imagePath === '') {
            continue;
        }

        if (!preg_match('#^https?://#i', $imagePath)) {
            $imagePath = DIRPAGE . ltrim($imagePath, '/');
        }

        $galleryImages[] = $imagePath;
    }
}

if (empty($galleryImages)) {
    $galleryImages[] = DIRIMG . 'placeholder.jpg';
}
$coverImage = $galleryImages[0];
?>

<div class="container property-page-shell">
    <?php if (!$hasProperty): ?>
        <div class="property-state-card property-state-card-error">
            <strong>Imovel nao encontrado</strong>
            <p>Nenhum dado valido foi recebido para montar esta pagina.</p>
            <a href="<?php echo DIRPAGE; ?>properties" class="btn-primary">Voltar para a listagem</a>
        </div>
    <?php else: ?>
        <section class="property-show-hero">
            <div class="property-show-copy">
                <div class="property-show-topline">
                    <span class="sales-kicker">Ficha comercial</span>
                    <a href="javascript:history.back()" class="btn-secondary">&larr; Voltar</a>
                </div>

                <h1><?php echo htmlspecialchars((string) ($property['title'] ?? 'Sem titulo')); ?></h1>
                <p>Detalhes completos para acelerar avaliacao, contacto e decisao sobre este activo.</p>

                <div class="property-show-tags">
                    <span><i class="fa fa-map-marker"></i> <?php echo htmlspecialchars((string) ($property['location'] ?? 'Localizacao nao informada')); ?></span>
                    <span><i class="fa fa-tag"></i> <?php echo htmlspecialchars($typeLabel); ?></span>
                    <span><i class="fa fa-exchange"></i> <?php echo htmlspecialchars($purposeLabel); ?></span>
                    <span><i class="fa fa-circle"></i> Estado: <?php echo htmlspecialchars($statusLabel); ?></span>
                </div>

                <div class="property-show-proof">
                    <div>
                        <strong><?php echo number_format((float) ($property['price'] ?? 0), 0, ',', '.'); ?> Kz</strong>
                        <span>valor anunciado</span>
                    </div>
                    <div>
                        <strong><?php echo (int) ($property['area'] ?? 0); ?> m2</strong>
                        <span>area declarada</span>
                    </div>
                    <div>
                        <strong><?php echo (int) ($property['bedrooms'] ?? 0); ?>/<?php echo (int) ($property['bathrooms'] ?? 0); ?></strong>
                        <span>quartos e banhos</span>
                    </div>
                </div>
            </div>

            <aside class="property-show-summary-card">
                <span class="property-summary-kicker">Leitura rapida</span>
                <div class="property-summary-price"><?php echo number_format((float) ($property['price'] ?? 0), 0, ',', '.'); ?> Kz</div>
                <div class="property-summary-owner"><?php echo $ownerName; ?></div>
                <div class="property-summary-badges">
                    <?php if (!empty($property['owner_verified'])): ?>
                        <span class="owner-verified-badge"><i class="fa fa-shield"></i> Perfil verificado</span>
                    <?php endif; ?>
                    <?php if (!empty($property['owner_trusted'])): ?>
                        <span class="owner-trust-badge"><i class="fa fa-check-circle"></i> Utilizador de confianca</span>
                    <?php endif; ?>
                    <?php if (empty($property['owner_verified']) && empty($property['owner_trusted'])): ?>
                        <span class="property-summary-muted">Conta sem selos adicionais no momento.</span>
                    <?php endif; ?>
                </div>
                <p>Ideal para uma decisao rapida com foco em contexto, seguranca e qualidade do contacto.</p>
            </aside>
        </section>

        <?php if ($status !== 'disponivel'): ?>
            <div class="property-state-card property-state-card-warning">
                <strong>Aviso operacional</strong>
                <p>Este imovel esta <?php echo htmlspecialchars($statusLabel); ?> e ainda nao esta visivel para publicacao geral.</p>
            </div>
        <?php endif; ?>

        <section class="property-detail">
            <div class="property-main-column">
                <div class="property-images property-show-gallery-card">
                    <img id="property-main-image" src="<?php echo htmlspecialchars($coverImage); ?>" alt="<?php echo htmlspecialchars((string) ($property['title'] ?? 'Imovel')); ?>">
                    <div class="property-gallery-overlay">
                        <span class="sales-card-badge"><i class="fa fa-home"></i> Activo em analise</span>
                        <?php if (count($galleryImages) > 1): ?>
                            <span class="sales-card-badge"><i class="fa fa-picture-o"></i> <?php echo count($galleryImages); ?> fotos</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (count($galleryImages) > 1): ?>
                    <div class="property-gallery-thumbs" id="property-gallery-thumbs">
                        <?php foreach ($galleryImages as $index => $imageUrl): ?>
                            <button
                                type="button"
                                class="property-gallery-thumb<?php echo $index === 0 ? ' is-active' : ''; ?>"
                                data-gallery-image="<?php echo htmlspecialchars($imageUrl); ?>"
                                aria-label="Ver imagem <?php echo (int) ($index + 1); ?>"
                            >
                                <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Miniatura <?php echo (int) ($index + 1); ?> de <?php echo htmlspecialchars((string) ($property['title'] ?? 'Imovel')); ?>">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($property['video_url'])): ?>
                    <div class="property-video property-show-video-card">
                        <?php
                            $videoUrl = $property['video_url'];
                            if (preg_match('/(youtube\.com|youtu\.be)/i', $videoUrl)) {
                                $youtubeId = '';
                                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]+)/', $videoUrl, $match)) {
                                    $youtubeId = $match[1];
                                }
                                if (!$youtubeId && preg_match('/embed\/([A-Za-z0-9_-]+)/', $videoUrl, $match)) {
                                    $youtubeId = $match[1];
                                }
                                if ($youtubeId) {
                                    echo '<iframe src="https://www.youtube.com/embed/' . $youtubeId . '" frameborder="0" allowfullscreen loading="lazy"></iframe>';
                                } else {
                                    echo '<iframe src="' . $videoUrl . '" frameborder="0" allowfullscreen loading="lazy"></iframe>';
                                }
                            } elseif (preg_match('/\.(mp4|webm|ogg)(\?|$)/i', $videoUrl)) {
                                echo '<video controls><source src="' . $videoUrl . '">Seu browser nao suporta video.</video>';
                            } else {
                                echo '<p><a href="' . $videoUrl . '" target="_blank" rel="noopener">Ver video</a></p>';
                            }
                        ?>
                    </div>
                <?php endif; ?>

                <div class="property-description property-story-card">
                    <div class="property-section-head">
                        <span class="sales-kicker">Narrativa do imovel</span>
                        <h3>Descricao</h3>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars((string) ($property['description'] ?? 'Sem descricao'))); ?></p>
                </div>

                <div class="property-description property-highlights-card">
                    <div class="property-section-head">
                        <span class="sales-kicker">Ficha tecnica</span>
                        <h3>Pontos-chave</h3>
                    </div>
                    <div class="property-highlight-grid">
                        <article>
                            <strong><?php echo htmlspecialchars($typeLabel); ?></strong>
                            <span>Categoria principal do activo</span>
                        </article>
                        <article>
                            <strong><?php echo htmlspecialchars($purposeLabel); ?></strong>
                            <span>Modelo de negociacao previsto</span>
                        </article>
                        <article>
                            <strong><?php echo (int) ($property['bedrooms'] ?? 0); ?> quartos</strong>
                            <span>Capacidade residencial declarada</span>
                        </article>
                        <article>
                            <strong><?php echo (int) ($property['bathrooms'] ?? 0); ?> banhos</strong>
                            <span>Infraestrutura sanitaria indicada</span>
                        </article>
                    </div>
                </div>
            </div>

            <aside class="property-sidebar">
                <div class="property-info property-info-card">
                    <div class="property-info-price-block">
                        <span>Preco anunciado</span>
                        <strong><?php echo number_format((float) ($property['price'] ?? 0), 0, ',', '.'); ?> Kz</strong>
                    </div>

                    <div class="property-info-list">
                        <div><span>Proprietario</span><strong><?php echo $ownerName; ?></strong></div>
                        <div><span>Localizacao</span><strong><?php echo htmlspecialchars((string) ($property['location'] ?? 'Nao informada')); ?></strong></div>
                        <div><span>Tipo</span><strong><?php echo htmlspecialchars($typeLabel); ?></strong></div>
                        <div><span>Finalidade</span><strong><?php echo htmlspecialchars($purposeLabel); ?></strong></div>
                        <div><span>Area</span><strong><?php echo htmlspecialchars((string) ($property['area'] ?? '0')); ?> m2</strong></div>
                    </div>
                </div>

                <?php if (Src\classes\ClassAuth::check()): ?>
                    <div class="property-actions property-actions-card">
                        <div class="property-section-head compact">
                            <span class="sales-kicker">Conversao</span>
                            <h3>Solicitar este imovel</h3>
                        </div>

                        <?php if ($status === 'disponivel'): ?>
                            <form action="<?php echo DIRPAGE; ?>request" method="POST">
                                <?php echo Src\classes\ClassCsrf::field(); ?>
                                <input type="hidden" name="property_id" value="<?php echo (int) ($property['id'] ?? 0); ?>">
                                <div class="form-group">
                                    <label for="request-type">Tipo de solicitacao</label>
                                    <select name="type" id="request-type" required>
                                        <option value="compra">Comprar</option>
                                        <option value="aluguer_curto">Aluguer Curto</option>
                                        <option value="aluguer_longo">Aluguer Longo</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="message">Mensagem</label>
                                    <textarea id="message" name="message" placeholder="Descreva interesse, prazo e objectivo da solicitacao"></textarea>
                                </div>
                                <button type="submit" class="btn-primary">Enviar solicitacao</button>
                            </form>
                        <?php else: ?>
                            <div class="property-state-card property-state-card-muted">
                                <strong>Nao e possivel solicitar</strong>
                                <p>Este activo esta em revisao e nao aceita pedidos neste momento.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="property-actions property-actions-card property-login-card">
                        <div class="property-section-head compact">
                            <span class="sales-kicker">Acesso</span>
                            <h3>Entre para negociar</h3>
                        </div>
                        <p>Faca login para enviar solicitacoes, acompanhar respostas e gerir o fluxo comercial deste imovel.</p>
                        <a href="<?php echo DIRPAGE; ?>login" class="btn-primary">Entrar na conta</a>
                    </div>
                <?php endif; ?>
            </aside>
        </section>
    <?php endif; ?>
</div>