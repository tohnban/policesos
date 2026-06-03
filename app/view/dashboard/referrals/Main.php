<div class="container dashboard-view">
    <section class="dashboard-view-hero compact">
        <div>
            <span class="dashboard-hero-kicker">Indicação</span>
            <h1>Meus Links de Indicação</h1>
            <p>Compartilhe estes links para ganhar comissões por indicações.</p>
        </div>
    </section>

    <div class="dashboard-module-card dashboard-kpi-section">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Regra do programa</span>
                <h3>Como a indicação funciona</h3>
            </div>
        </div>
        <p class="dashboard-inline-note">Partilhe os seus links e ganhe <strong>3% de comissão</strong> em cada negócio fechado através das suas indicações — para imóveis onde a sua afiliação está aprovada.</p>
    </div>

    <div class="dashboard-module-card">
        <div class="dashboard-module-head compact">
            <div>
                <span class="dashboard-module-kicker">Afiliação</span>
                <h3>Imóveis onde você é afiliado</h3>
            </div>
        </div>

        <table class="commissions-table">
            <thead>
                <tr>
                    <th>Imóvel</th>
                    <th>Preço</th>
                    <th>Indicações</th>
                    <th>Link de Indicação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($affiliated_properties)): ?>
                    <?php foreach ($affiliated_properties as $property): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($property['title']); ?></td>
                            <td><?php echo number_format((float) $property['price'], 0, ',', '.'); ?> Kz</td>
                            <td><?php echo (int) ($property['referral_count'] ?? 0); ?></td>
                            <td>
                                <div class="referral-link-wrap">
                                    <input type="text" id="ref-aff-<?php echo (int) $property['property_id']; ?>" readonly value="<?php echo DIRPAGE; ?>property/<?php echo (int) $property['property_id']; ?>?ref=<?php echo htmlspecialchars($affiliate_code ?? ($user['affiliate_code'] ?? '')); ?>" class="referral-link-input">
                                    <button type="button" class="btn-secondary referral-copy-btn" data-copy-target="ref-aff-<?php echo (int) $property['property_id']; ?>" title="Copiar link">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Nenhum imóvel afiliado ativo encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>