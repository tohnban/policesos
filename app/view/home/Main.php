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
?>

<section class="sales-hero">
    <div class="container sales-hero-shell">
        <div class="sales-hero-copy">
            <span class="sales-kicker">Mercado imobiliario premium</span>
            <h1>Transforme procura em venda com um funil imobiliario mais rapido.</h1>
            <p>Descubra imoveis prontos para fechar negocio, com proprietarios verificados e acompanhamento inteligente para compradores, investidores e afiliados.</p>
            <div class="sales-hero-proof">
                <span><i class="fa fa-check-circle"></i> <?php echo number_format($featuredCount, 0, ',', '.'); ?> destaques activos</span>
                <span><i class="fa fa-shield"></i> verificacao e moderacao</span>
                <span><i class="fa fa-bolt"></i> fluxo comercial mais rapido</span>
            </div>
            <div class="sales-hero-actions">
                <a href="<?php echo DIRPAGE; ?>properties" class="btn-primary">Explorar Imoveis</a>
                <a href="<?php echo DIRPAGE; ?>featured" class="btn-secondary">Ver Selecoes de Destaque</a>
            </div>
        </div>

        <aside class="sales-hero-panel">
            <h3>Pulso do mercado</h3>
            <div class="sales-hero-metrics">
                <div>
                    <strong><?php echo number_format($availableCount, 0, ',', '.'); ?></strong>
                    <span>ativos disponiveis</span>
                </div>
                <div>
                    <strong><?php echo number_format($soldCount + $rentedCount, 0, ',', '.'); ?></strong>
                    <span>negocios concluidos</span>
                </div>
                <div>
                    <strong><?php echo number_format((float) $approvalRate, 1, ',', '.'); ?>%</strong>
                    <span>taxa de aprovacao</span>
                </div>
            </div>
            <div class="sales-hero-panel-note">
                <strong>Janela comercial</strong>
                <span>Entre na carteira certa, priorize proprietarios confiaveis e reduza ruido no funil.</span>
            </div>
            <p>Concentre-se nas oportunidades com maior prontidao de fechamento.</p>
        </aside>
    </div>
</section>

<section class="sales-strip">
    <div class="container sales-strip-grid">
        <article>
            <i class="fa fa-shield"></i>
            <div>
                <strong>Confianca validada</strong>
                <span>Perfis e historico analisados para reduzir risco.</span>
            </div>
        </article>
        <article>
            <i class="fa fa-line-chart"></i>
            <div>
                <strong>Visibilidade comercial</strong>
                <span>Priorize imoveis premium e com maior potencial de conversao.</span>
            </div>
        </article>
        <article>
            <i class="fa fa-clock-o"></i>
            <div>
                <strong>Velocidade de decisao</strong>
                <span>Detalhes claros para acelerar contato e proposta.</span>
            </div>
        </article>
    </div>
</section>

<section class="container sales-section">
    <div class="section-header sales-section-header">
        <div>
            <h2>Destaques Patrocinados</h2>
            <p>Top oportunidades patrocinadas para gerar contacto rapido, proposta imediata e maior taxa de fecho.</p>
        </div>
        <a href="<?php echo DIRPAGE; ?>featured" class="btn-secondary">Ver carteira completa</a>
    </div>

    <section class="sales-carousel-section">
        <div class="sales-carousel-head">
            <div class="sales-carousel-title">
                <i class="fa fa-bullhorn"></i>
                <h2>Vitrine de Conversao</h2>
                <span class="sales-premium-badge">prioridade maxima</span>
            </div>
            <?php if (count($featuredProperties) > 1): ?>
            <div class="sales-carousel-nav-btns">
                <button type="button" id="homeSalesPrev" aria-label="Destaque anterior"><i class="fa fa-chevron-left"></i></button>
                <button type="button" id="homeSalesNext" aria-label="Proximo destaque"><i class="fa fa-chevron-right"></i></button>
            </div>
            <?php endif; ?>
        </div>
        <p class="sales-carousel-sub">Cada anuncio aqui foi patrocinado para aparecer primeiro e captar leads com alta intencao de compra.</p>

        <?php if (!empty($featuredProperties)): ?>
            <div class="sales-carousel-viewport" id="homeSalesViewport">
                <div class="sales-carousel-track" id="homeSalesTrack">
                    <?php foreach ($featuredProperties as $idx => $property): ?>
                        <?php
                        $position = (int) $idx + 1;
                        $urgencyTags = ['Alta procura', 'Lead quente', 'Preco competitivo'];
                        $urgencyText = $urgencyTags[$idx % count($urgencyTags)];
                        $imagesList = json_decode((string) ($property['images'] ?? '[]'), true);
                        $firstImage = (is_array($imagesList) && !empty($imagesList[0])) ? (string) $imagesList[0] : '';
                        if ($firstImage !== '' && !preg_match('#^https?://#i', $firstImage)) {
                            $firstImage = DIRPAGE . ltrim($firstImage, '/');
                        }
                        $coverImage = $firstImage !== '' ? $firstImage : (DIRIMG . 'placeholder.jpg');
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
                                <span class="sales-card-badge"><i class="fa fa-bullhorn"></i> Patrocinado</span>
                                <?php if ($position <= 3): ?>
                                    <span class="sales-rank-badge">TOP <?php echo $position; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="sales-carousel-card-body">
                                <div class="sales-carousel-tags">
                                    <span class="sales-urgency-tag"><i class="fa fa-fire"></i> <?php echo $urgencyText; ?></span>
                                </div>
                                <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                                <p class="sales-location"><i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($property['location']); ?></p>
                                <p class="sales-price"><?php echo number_format((float) $property['price'], 0, ',', '.'); ?> Kz</p>
                                <div class="sales-meta-row">
                                    <span><i class="fa fa-bed"></i> <?php echo (int) $property['bedrooms']; ?> quartos</span>
                                    <span><i class="fa fa-bath"></i> <?php echo (int) $property['bathrooms']; ?> banhos</span>
                                </div>
                                <div class="sales-carousel-actions">
                                    <a href="<?php echo DIRPAGE; ?>property/<?php echo (int) $property['id']; ?>" class="btn-primary sales-card-cta">Negociar agora</a>
                                    <?php if ($waLink !== ''): ?>
                                        <a href="<?php echo htmlspecialchars($waLink); ?>" target="_blank" rel="noopener noreferrer" class="btn-secondary sales-card-cta sales-wa-cta">
                                            <i class="fa fa-whatsapp"></i> WhatsApp do proprietario
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
                <h3>Sem destaques no momento</h3>
                <p>Novas oportunidades comerciais serao publicadas em breve.</p>
                <a href="<?php echo DIRPAGE; ?>properties" class="btn-primary">Ver todos os imoveis</a>
            </div>
        <?php endif; ?>
    </section>
</section>

<?php if (!empty($featuredProperties)): ?>
<script>
(function () {
    var viewport = document.getElementById('homeSalesViewport');
    var track = document.getElementById('homeSalesTrack');
    var dotsWrap = document.getElementById('homeSalesDots');
    var prevBtn = document.getElementById('homeSalesPrev');
    var nextBtn = document.getElementById('homeSalesNext');

    if (!viewport || !track || !dotsWrap) {
        return;
    }

    var cards = Array.prototype.slice.call(track.querySelectorAll('.sales-carousel-card'));
    var current = 0;
    var timer = null;
    var gap = 18;

    function getVisibleCount() {
        var w = viewport.clientWidth;
        if (w >= 1024) return 3;
        if (w >= 680) return 2;
        return 1;
    }

    function applyCardWidths() {
        var visible = getVisibleCount();
        var width = Math.floor((viewport.clientWidth - gap * (visible - 1)) / visible);
        cards.forEach(function (card) {
            card.style.width = width + 'px';
            card.style.flexBasis = width + 'px';
        });
    }

    function maxIndex() {
        return Math.max(0, cards.length - getVisibleCount());
    }

    function stepWidth() {
        return cards.length ? cards[0].offsetWidth + gap : 0;
    }

    function render() {
        current = Math.max(0, Math.min(current, maxIndex()));
        track.style.transform = 'translateX(-' + (current * stepWidth()) + 'px)';
        var dots = dotsWrap.querySelectorAll('.sales-carousel-dot');
        dots.forEach(function (dot, idx) {
            dot.classList.toggle('is-active', idx === current);
        });
    }

    function goTo(idx) {
        current = idx;
        render();
    }

    function next() {
        var limit = maxIndex();
        goTo(current >= limit ? 0 : current + 1);
    }

    function prev() {
        var limit = maxIndex();
        goTo(current <= 0 ? limit : current - 1);
    }

    function rebuildDots() {
        dotsWrap.innerHTML = '';
        var total = maxIndex() + 1;
        for (var i = 0; i < total; i++) {
            var dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'sales-carousel-dot' + (i === current ? ' is-active' : '');
            dot.setAttribute('aria-label', 'Ir para destaque ' + (i + 1));
            (function (idx) {
                dot.addEventListener('click', function () {
                    goTo(idx);
                    restartAuto();
                });
            })(i);
            dotsWrap.appendChild(dot);
        }
    }

    function stopAuto() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    function startAuto() {
        if (cards.length <= getVisibleCount()) {
            return;
        }
        stopAuto();
        timer = window.setInterval(next, 4500);
    }

    function restartAuto() {
        stopAuto();
        startAuto();
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            next();
            restartAuto();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            prev();
            restartAuto();
        });
    }

    viewport.addEventListener('mouseenter', stopAuto);
    viewport.addEventListener('mouseleave', startAuto);

    var touchStartX = null;
    viewport.addEventListener('touchstart', function (e) {
        if (e.touches && e.touches[0]) {
            touchStartX = e.touches[0].clientX;
        }
    }, { passive: true });

    viewport.addEventListener('touchend', function (e) {
        if (touchStartX === null || !e.changedTouches || !e.changedTouches[0]) {
            touchStartX = null;
            return;
        }
        var delta = touchStartX - e.changedTouches[0].clientX;
        touchStartX = null;
        if (Math.abs(delta) < 45) {
            return;
        }
        if (delta > 0) {
            next();
        } else {
            prev();
        }
        restartAuto();
    }, { passive: true });

    var resizeTimer;
    window.addEventListener('resize', function () {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(function () {
            applyCardWidths();
            rebuildDots();
            render();
            startAuto();
        }, 160);
    });

    applyCardWidths();
    rebuildDots();
    render();
    startAuto();
})();
</script>
<?php endif; ?>

