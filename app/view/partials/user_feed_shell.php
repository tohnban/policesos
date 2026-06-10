<?php
/**
 * User-facing feed panel: empty state or grouped list.
 * Not for admin desktop-only table views.
 *
 * @var bool $shellHasItems
 * @var string $shellEmptyIcon  Font Awesome class suffix (e.g. fa-credit-card)
 * @var string $shellEmptyTitle
 * @var string $shellEmptyMessage
 * @var array<int, array{label: string, items: array<int, mixed>}>|null $feedGroups
 * @var string $shellFeedItemPartial
 * @var string $shellFeedItemVarName
 * @var string $shellFeedExtraClass
 * @var array<string, mixed> $shellFeedItemContext
 */

$shellHasItems = !empty($shellHasItems);
$shellEmptyIcon = (string) ($shellEmptyIcon ?? 'fa-inbox');
$shellEmptyTitle = (string) ($shellEmptyTitle ?? 'Nada por aqui');
$shellEmptyMessage = (string) ($shellEmptyMessage ?? '');
$shellFeedItemPartial = (string) ($shellFeedItemPartial ?? '');
$shellFeedItemVarName = (string) ($shellFeedItemVarName ?? 'feedItem');
$shellFeedExtraClass = trim((string) ($shellFeedExtraClass ?? ''));
$shellFeedItemContext = is_array($shellFeedItemContext ?? null) ? $shellFeedItemContext : [];
$feedGroups = is_array($feedGroups ?? null) ? $feedGroups : [];

if (!$shellHasItems): ?>
    <div class="notification-inbox-empty">
        <span class="notification-inbox-empty-icon" aria-hidden="true"><i class="fa <?php echo htmlspecialchars($shellEmptyIcon, ENT_QUOTES, 'UTF-8'); ?>"></i></span>
        <strong><?php echo htmlspecialchars($shellEmptyTitle); ?></strong>
        <?php if ($shellEmptyMessage !== ''): ?>
            <p><?php echo htmlspecialchars($shellEmptyMessage); ?></p>
        <?php endif; ?>
    </div>
<?php elseif ($shellFeedItemPartial !== '' && !empty($feedGroups)): ?>
    <?php
        $feedItemPartial = $shellFeedItemPartial;
        $feedItemVarName = $shellFeedItemVarName;
        $feedExtraClass = $shellFeedExtraClass;
        $feedItemContext = $shellFeedItemContext;
        require __DIR__ . '/user_feed_groups.php';
    ?>
<?php endif; ?>
