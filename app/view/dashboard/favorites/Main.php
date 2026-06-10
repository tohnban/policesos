<?php
$properties = is_array($properties ?? null) ? $properties : [];
$propertyCount = count($properties);
$feedGroups = $propertyCount > 0
    ? [['key' => 'saved', 'label' => 'Guardados', 'items' => $properties]]
    : [];
?>

<div class="container dashboard-view notification-inbox-view favorites-inbox-view">
    <section class="notification-inbox-hero">
        <div class="notification-inbox-hero-main">
            <h1>Meus Favoritos</h1>
            <p class="notification-inbox-hero-meta">
                <span><?php echo (int) $propertyCount; ?> guardado<?php echo $propertyCount === 1 ? '' : 's'; ?></span>
            </p>
        </div>
        <div class="notification-inbox-hero-actions">
            <a href="<?php echo DIRPAGE; ?>properties" class="notification-inbox-text-btn">Explorar imóveis</a>
        </div>
    </section>

    <div class="notification-inbox-panel favorites-inbox-panel">
        <?php
            $shellHasItems = $propertyCount > 0;
            $shellEmptyIcon = 'fa-heart-o';
            $shellEmptyTitle = 'Nenhum imóvel favorito ainda';
            $shellEmptyMessage = 'Navegue pelos imóveis disponíveis e toque no coração para guardar os que lhe interessam.';
            $feedGroups = $feedGroups;
            $shellFeedItemPartial = 'favorite_property_feed_item.php';
            $shellFeedItemVarName = 'property';
            $shellFeedExtraClass = 'favorite-property-feed-list';
            $shellFeedItemContext = [];
            require DIRREQ . 'app/view/partials/user_feed_shell.php';
        ?>

        <?php if ($propertyCount === 0): ?>
            <div class="notification-inbox-panel-foot">
                <a href="<?php echo DIRPAGE; ?>properties" class="btn-primary">Ver imóveis</a>
            </div>
        <?php endif; ?>
    </div>
</div>
