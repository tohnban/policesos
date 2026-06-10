<?php
/**
 * Pagination bar for user-facing inbox-style feeds.
 *
 * @var int $page
 * @var int $totalPages
 * @var string|null $prevUrl
 * @var string|null $nextUrl
 * @var string|null $pageCopy
 * @var string|null $loadMoreUrl  Cursor / load-more mode
 */

$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$prevUrl = isset($prevUrl) ? (string) $prevUrl : '';
$nextUrl = isset($nextUrl) ? (string) $nextUrl : '';
$pageCopy = isset($pageCopy) ? (string) $pageCopy : '';
$loadMoreUrl = isset($loadMoreUrl) ? (string) $loadMoreUrl : '';

$hasPagination = $loadMoreUrl !== '' || $prevUrl !== '' || $nextUrl !== '' || $pageCopy !== '';
if (!$hasPagination) {
    return;
}
?>

<div class="notification-inbox-pagination">
    <?php if ($prevUrl !== ''): ?>
        <a href="<?php echo htmlspecialchars($prevUrl, ENT_QUOTES, 'UTF-8'); ?>" class="notification-inbox-page-btn">&larr; Anterior</a>
    <?php endif; ?>
    <?php if ($pageCopy !== ''): ?>
        <span class="notification-inbox-page-copy"><?php echo htmlspecialchars($pageCopy); ?></span>
    <?php elseif ($totalPages > 1): ?>
        <span class="notification-inbox-page-copy">Página <?php echo $page; ?> de <?php echo $totalPages; ?></span>
    <?php endif; ?>
    <?php if ($nextUrl !== ''): ?>
        <a href="<?php echo htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8'); ?>" class="notification-inbox-page-btn">Próxima &rarr;</a>
    <?php endif; ?>
    <?php if ($loadMoreUrl !== ''): ?>
        <a href="<?php echo htmlspecialchars($loadMoreUrl, ENT_QUOTES, 'UTF-8'); ?>" class="notification-inbox-page-btn">Carregar mais</a>
    <?php endif; ?>
</div>
