<?php
/**
 * Social-style chat thread row for /dashboard/requestChats.
 *
 * @var array<string, mixed> $request
 * @var array<string, mixed> $summary
 * @var string $requestView
 * @var int $selectedRequestId
 */

use App\model\Notification;
use App\model\Request as RequestModel;

$request = is_array($request ?? null) ? $request : [];
$summary = is_array($summary ?? null) ? $summary : [];
$requestView = (string) ($requestView ?? '');
$selectedRequestId = (int) ($selectedRequestId ?? 0);

$requestId = (int) ($request['id'] ?? 0);
$unreadCount = (int) ($summary['unread_count'] ?? 0);
$commercialStatus = (string) ($request['commercial_status'] ?? ($request['status'] ?? ''));
$statusLabel = RequestModel::statusLabel($commercialStatus, (string) ($request['closing_confirmation_status'] ?? ''));
$propertyTitle = (string) ($request['title'] ?? 'Solicitação #' . $requestId);
$chatUrl = requestChatsPageUrl($requestId, $requestView);
$isActive = $selectedRequestId === $requestId;
if ($isActive) {
    $unreadCount = 0;
}

$preview = trim((string) ($summary['last_message_text'] ?? ''));
if ($preview !== '' && function_exists('mb_strimwidth')) {
    $preview = mb_strimwidth($preview, 0, 80, '...');
} elseif ($preview !== '') {
    $preview = substr($preview, 0, 80) . (strlen($preview) > 80 ? '...' : '');
}

$timestamp = (string) ($summary['last_message_at'] ?? ($request['created_at'] ?? ''));
$relativeTime = Notification::relativeTime($timestamp);
$absoluteTime = $timestamp !== '' ? date('d/m/Y H:i', strtotime($timestamp)) : '';

$visualMap = [
    'em_contacto' => ['fa-comments', 'tone-chat'],
    'fechado_ganho' => ['fa-check-circle', 'tone-payment'],
    'em_disputa' => ['fa-gavel', 'tone-alert'],
    'cancelado' => ['fa-ban', 'tone-document'],
    'expirado' => ['fa-clock-o', 'tone-document'],
];
$visual = $visualMap[strtolower($commercialStatus)] ?? ['fa-comments', 'tone-chat'];
[$feedIcon, $feedTone] = $visual;

$totalMessages = (int) ($summary['total_messages'] ?? 0);
$showMarkUnreadMenu = !$isActive && $unreadCount === 0 && $totalMessages > 0;
?>

<article class="request-chat-feed-item notification-feed-item request-chats-panel-item <?php echo $isActive ? 'is-active is-read' : ($unreadCount > 0 ? 'is-unread has-unread' : 'is-read'); ?>"
         data-request-id="<?php echo $requestId; ?>">
    <a href="<?php echo htmlspecialchars($chatUrl, ENT_QUOTES, 'UTF-8'); ?>"
       class="notification-feed-link request-chats-panel-item-link"
       aria-current="<?php echo $isActive ? 'true' : 'false'; ?>">
        <span class="notification-feed-icon <?php echo htmlspecialchars($feedTone, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
            <i class="fa <?php echo htmlspecialchars($feedIcon, ENT_QUOTES, 'UTF-8'); ?>"></i>
        </span>
        <span class="notification-feed-body">
            <span class="notification-feed-text">
                <strong class="request-chats-panel-item-title"><?php echo htmlspecialchars($propertyTitle); ?></strong>
                <?php if ($preview !== ''): ?>
                    <span class="notification-feed-message request-chats-panel-item-preview"><?php echo htmlspecialchars($preview); ?></span>
                <?php endif; ?>
            </span>
            <span class="notification-feed-meta request-chats-panel-item-meta">
                <span class="request-status-badge request-status-<?php echo htmlspecialchars($commercialStatus, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                <span class="notification-feed-dot" aria-hidden="true">·</span>
                <span>#<?php echo $requestId; ?></span>
                <?php if ($unreadCount > 0): ?>
                    <span class="notification-feed-dot" aria-hidden="true">·</span>
                    <span class="request-chat-unread-badge"><?php echo $unreadCount; ?> nova(s)</span>
                <?php endif; ?>
                <?php if ($relativeTime !== '' || $absoluteTime !== ''): ?>
                    <span class="notification-feed-dot" aria-hidden="true">·</span>
                    <time class="notification-feed-time" datetime="<?php echo htmlspecialchars($timestamp, ENT_QUOTES, 'UTF-8'); ?>"
                          title="<?php echo htmlspecialchars($absoluteTime, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($relativeTime !== '' ? $relativeTime : $absoluteTime); ?>
                    </time>
                <?php endif; ?>
            </span>
        </span>
        <?php if ($unreadCount > 0): ?>
            <span class="notification-feed-unread-dot" aria-label="<?php echo (int) $unreadCount; ?> mensagem(ns) nova(s)"></span>
        <?php endif; ?>
    </a>

    <?php if ($showMarkUnreadMenu): ?>
        <div class="notification-feed-menu-wrap">
            <button type="button"
                    class="notification-feed-menu-btn"
                    aria-label="Opções da conversa"
                    aria-expanded="false"
                    aria-haspopup="true">
                <i class="fa fa-ellipsis-h" aria-hidden="true"></i>
            </button>
            <div class="notification-feed-menu" hidden>
                <button type="button"
                        class="request-chat-mark-unread-btn"
                        data-request-id="<?php echo $requestId; ?>">
                    Marcar como não lida
                </button>
            </div>
        </div>
    <?php endif; ?>
</article>
