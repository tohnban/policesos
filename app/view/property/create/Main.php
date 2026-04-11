<div class="container property-create-shell">
    <section class="property-create-hero">
        <div>
            <span class="sales-kicker">Publicação guiada</span>
            <h1>Cadastrar Imóvel</h1>
            <p>Estruture o anúncio com qualidade comercial para aumentar descoberta, confiança e conversão.</p>
        </div>
        <div class="property-create-hero-tags">
            <span><i class="fa fa-check-circle"></i> Revisão antes de publicar</span>
            <span><i class="fa fa-picture-o"></i> Capa definida por miniatura</span>
            <span><i class="fa fa-line-chart"></i> Pronto para indicação</span>
        </div>
    </section>

    <?php if (isset($_GET['error'])): ?>
        <div class="auth-message auth-message-error" style="margin: 0 0 20px;">
            <p><strong>Erro:</strong> <?php echo htmlspecialchars($_GET['error']); ?></p>
        </div>
    <?php endif; ?>

    <div class="property-create-grid">
        <form action="<?php echo DIRPAGE; ?>property/store" method="POST" enctype="multipart/form-data" class="auth-container property-create-form-card">
            <?php echo Src\classes\ClassCsrf::field(); ?>

            <div class="property-create-section-head">
                <span>Ficha principal</span>
                <h3>Dados do imóvel</h3>
            </div>

            <div class="form-group">
                <label for="title">Título</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="description">Descrição</label>
                <textarea id="description" name="description" required></textarea>
            </div>

            <div class="property-create-two-cols">
                <div class="form-group">
                    <label for="type">Tipo</label>
                    <select id="type" name="type" required>
                        <option value="">Selecione</option>
                        <option value="casa">Casa</option>
                        <option value="apartamento">Apartamento</option>
                        <option value="terreno">Terreno</option>
                        <option value="edificio">Edifício</option>
                        <option value="vivenda">Vivenda</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="purpose">Finalidade</label>
                    <select id="purpose" name="purpose" required>
                        <option value="">Selecione</option>
                        <option value="venda">Venda</option>
                        <option value="aluguer_curto">Aluguer Curto</option>
                        <option value="aluguer_longo">Aluguer Longo</option>
                    </select>
                </div>
            </div>

            <div class="property-create-two-cols">
                <div class="form-group">
                    <label for="price">Preço (Kz)</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="location">Localização</label>
                    <input type="text" id="location" name="location" required>
                </div>
            </div>

            <div class="property-create-three-cols">
                <div class="form-group">
                    <label for="bedrooms">Quartos</label>
                    <input type="number" id="bedrooms" name="bedrooms" min="0">
                </div>

                <div class="form-group">
                    <label for="bathrooms">Casas de Banho</label>
                    <input type="number" id="bathrooms" name="bathrooms" min="0">
                </div>

                <div class="form-group">
                    <label for="area">Área (m²)</label>
                    <input type="number" id="area" name="area" step="0.01">
                </div>
            </div>

            <div class="form-group">
                <label for="video_url">URL do Vídeo (opcional)</label>
                <input type="url" id="video_url" name="video_url" placeholder="https://www.youtube.com/watch?v=...">
            </div>

            <div class="form-group">
                <label for="owner_bonus_pct">Acréscimo voluntário da comissão (%)</label>
                <input type="number" id="owner_bonus_pct" name="owner_bonus_pct" min="0" step="0.10" value="0" placeholder="Ex.: 1.5">
                <small class="dashboard-inline-note">Exemplo: 1.5 adiciona +1.5% ao indicador, mantendo 2% para o sistema.</small>
            </div>

            <div class="form-group property-upload-panel">
                <label for="images">Imagens do Imóvel</label>
                <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                <small class="dashboard-inline-note">Até 8 imagens. Formatos: JPG, PNG, WEBP, GIF. Máximo 3MB por imagem.</small>
                <small class="dashboard-inline-note">A primeira imagem será usada como capa. Na pré-visualização pode remover ou definir outra como capa.</small>
                <div id="property-image-preview" class="property-image-preview" aria-live="polite"></div>
            </div>

            <button type="submit" class="btn-primary">Cadastrar Imóvel</button>
        </form>

        <aside class="property-create-side">
            <div class="table-card property-create-side-card">
                <span class="property-create-side-kicker">Comissão</span>
                <h4>Modelo padrão</h4>
                <p><strong>5%</strong> total por negócio: 2% sistema + 3% indicador.</p>
                <p>Pode adicionar acréscimo voluntário para tornar a oferta mais competitiva na rede de afiliados.</p>
            </div>

            <div class="table-card property-create-side-card">
                <span class="property-create-side-kicker">Visibilidade</span>
                <h4>Plano da conta</h4>
                <p>Conta gratuita: visibilidade base com até <strong>3 imóveis</strong>.</p>
                <p>Conta premium: prioridade de exibição na descoberta comercial.</p>
            </div>
        </aside>
    </div>
</div>