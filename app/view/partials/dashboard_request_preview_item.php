<?php
/**
 * Compact request row for dashboard activity preview.
 *
 * @var array<string, mixed> $request
 */

use App\model\Notification;
use App\model\Request as RequestModel;

$request = is_array($request ?? null) ? $request : [];
$requestId = (int) ($request['id'] ?? 0);
$propertyTitle = (string) ($request['title'] ?? 'Imóvel');
$commercialStatus = (string) ($request['commercial_status'] ?? $request['status'] ?? 'em_contacto');
$statusLabel = RequestModel::statusLabel(
    $commercialStatus,
    is_string($request['closing_confirmation_status'] ?? null) ? (string) $request['closing_confirmation_status'] : null
);
$createdAt = (string) ($request['created_at'] ?? '');
$relativeTime = Notification::relativeTime($createdAt);
$absoluteTime = $createdAt !== '' ? date('d/m/Y H:i', strtotime($createdAt)) : '';
$chatUrl = DIRPAGE . 'dashboard/requestChat/' . $requestId;

$visualMap = [
    'em_contacto' => ['fa-comments', 'tone-request'],
    'fechado_ganho' => ['fa-check-circle', 'tone-payment'],
    'em_disputa' => ['fa-gavel', 'tone-alert'],
    'cancelado' => ['fa-ban', 'tone-document'],
    'expirado' => ['fa-clock-o', 'tone-document'],
];
[$feedIcon, $feedTone] = $visualMap[strtolower($commercialStatus)] ?? ['fa-home', 'tone-default'];
?>

<article class="notification-feed-item dashboard-request-preview-item is-read">
    <a href="<?php echo htmlspecialchars($chatUrl, ENT_QUOTES, 'UTF-8'); ?>"
       class="notification-feed-link dashboard-request-preview-link">
        <span class="notification-feed-icon <?php echo htmlspecialchars($feedTone, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
            <i class="fa <?php echo htmlspecialchars($feedIcon, ENT_QUOTES, 'UTF-8'); ?>"></i>
        </span>
        <span class="notification-feed-body">
            <span class="notification-feed-text">
                <strong><?php echo htmlspecialchars($propertyTitle); ?></strong>
                <span class="notification-feed-message">Pedido de contacto</span>
            </span>
            <span class="notification-feed-meta">
                <span class="request-status-badge request-status-<?php echo htmlspecialchars(strtolower($commercialStatus), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($statusLabel); ?>
                </span>
                <?php if ($relativeTime !== '' || $absoluteTime !== ''): ?>
                    <span class="notification-feed-dot" aria-hidden="true">·</span>
                    <time class="notification-feed-time" datetime="<?php echo htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?>"
                          title="<?php echo htmlspecialchars($absoluteTime, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($relativeTime !== '' ? $relativeTime : $absoluteTime); ?>
                    </time>
                <?php endif; ?>
            </span>
        </span>
    </a>
</article>
