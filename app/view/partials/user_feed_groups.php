<?php
/**
 * Renders grouped user-facing feed lists. For admin desktop-only pages, use tables instead.
 *
 * @var array<int, array{key?: string, label: string, items: array<int, mixed>}> $feedGroups
 * @var string $feedItemPartial  Filename under app/view/partials/
 * @var string $feedItemVarName  Variable name exposed to the item partial (e.g. notification, request)
 * @var string $feedExtraClass    Optional extra class on .notification-feed wrapper
 * @var array<string, mixed> $feedItemContext Extra variables for each item partial
 */

$feedGroups = is_array($feedGroups ?? null) ? $feedGroups : [];
$feedItemPartial = (string) ($feedItemPartial ?? '');
$feedItemVarName = (string) ($feedItemVarName ?? 'feedItem');
$feedExtraClass = trim((string) ($feedExtraClass ?? ''));
$feedItemContext = is_array($feedItemContext ?? null) ? $feedItemContext : [];

if ($feedItemPartial === '' || empty($feedGroups)) {
    return;
}

$feedClass = 'notification-feed notification-feed--inbox' . ($feedExtraClass !== '' ? ' ' . $feedExtraClass : '');
$partialPath = __DIR__ . '/' . ltrim($feedItemPartial, '/');
?>

<div class="<?php echo htmlspecialchars($feedClass, ENT_QUOTES, 'UTF-8'); ?>">
    <?php foreach ($feedGroups as $group): ?>
        <section class="notification-feed-group">
            <h2 class="notification-feed-group-title"><?php echo htmlspecialchars((string) ($group['label'] ?? '')); ?></h2>
            <div class="notification-feed-group-list">
                <?php foreach ($group['items'] as $feedItem): ?>
                    <?php
                        foreach ($feedItemContext as $contextKey => $contextValue) {
                            ${$contextKey} = $contextValue;
                        }
                        ${$feedItemVarName} = $feedItem;
                        require $partialPath;
                    ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
