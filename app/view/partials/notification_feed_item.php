<?php
/**
 * Social-style notification row (dropdown + inbox).
 *
 * @var array<string, mixed> $notification
 * @var bool $compact
 * @var bool $showMenu
 * @var bool $archiveMode
 * @var string $markReadUrl
 * @var string $markUnreadUrl
 * @var string $archiveUrl
 * @var bool $useDashboardReadActions
 */

use App\model\Notification;

$notification = is_array($notification ?? null) ? $notification : [];
$compact = !empty($compact);
$showMenu = !empty($showMenu);
$archiveMode = !empty($archiveMode);

$id = (int) ($notification['id'] ?? 0);
$isRead = !empty($notification['is_read']);
$type = (string) ($notification['type'] ?? '');
$typeIcon = (string) ($notification['type_icon'] ?? Notification::typeIcon($type));
$typeTone = (string) ($notification['type_tone'] ?? Notification::typeTone($type));
$typeLabel = (string) ($notification['type_label'] ?? Notification::typeLabel($type));
$title = (string) ($notification['title'] ?? 'Notificação');
$message = (string) ($notification['message'] ?? '');
$targetUrl = (string) ($notification['target_url'] ?? (DIRPAGE . 'notification/inbox'));
$relativeTime = (string) ($notification['relative_time'] ?? Notification::relativeTime((string) ($notification['created_at'] ?? '')));
$absoluteTime = (string) ($notification['created_at_label'] ?? '');
if ($absoluteTime === '' && !empty($notification['created_at'])) {
    $absoluteTime = date('d/m/Y H:i', strtotime((string) $notification['created_at']));
}

$markReadUrl = (string) ($markReadUrl ?? (DIRPAGE . 'dashboard/markNotificationRead/' . $id));
$markUnreadUrl = (string) ($markUnreadUrl ?? (DIRPAGE . 'dashboard/markNotificationUnread/' . $id));
$archiveUrl = (string) ($archiveUrl ?? (DIRPAGE . 'notification/archiveItem'));
$useDashboardReadActions = !empty($useDashboardReadActions);
?>

<article class="notification-feed-item notification-item <?php echo $archiveMode ? 'is-archived is-read' : ($isRead ? 'is-read' : 'is-unread'); ?><?php echo $compact ? ' is-compact' : ''; ?>"
         data-notification-id="<?php echo $id; ?>">
    <a href="<?php echo htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8'); ?>"
       class="notification-feed-link notification-item-main"
       data-notification-id="<?php echo $id; ?>"
       data-notification-read-url="<?php echo htmlspecialchars($markReadUrl, ENT_QUOTES, 'UTF-8'); ?>">
        <span class="notification-feed-icon <?php echo htmlspecialchars($typeTone, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
            <i class="fa <?php echo htmlspecialchars($typeIcon, ENT_QUOTES, 'UTF-8'); ?>"></i>
        </span>
        <span class="notification-feed-body">
            <span class="notification-feed-text">
                <strong><?php echo htmlspecialchars($title); ?></strong>
                <?php if ($message !== ''): ?>
                    <span class="notification-feed-message"><?php echo htmlspecialchars($message); ?></span>
                <?php endif; ?>
            </span>
            <span class="notification-feed-meta">
                <span class="notification-feed-type"><?php echo htmlspecialchars($typeLabel); ?></span>
                <span class="notification-feed-dot" aria-hidden="true">·</span>
                <?php if ($archiveMode): ?>
                    <span class="notification-feed-status">Arquivada</span>
                    <span class="notification-feed-dot" aria-hidden="true">·</span>
                <?php endif; ?>
                <time class="notification-feed-time" datetime="<?php echo htmlspecialchars((string) ($notification['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                      title="<?php echo htmlspecialchars($absoluteTime, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($relativeTime !== '' ? $relativeTime : $absoluteTime); ?>
                </time>
            </span>
        </span>
        <?php if (!$archiveMode && !$isRead): ?>
            <span class="notification-feed-unread-dot" aria-label="Não lida"></span>
        <?php endif; ?>
    </a>

    <?php if ($showMenu && $archiveMode): ?>
        <div class="notification-feed-menu-wrap">
            <button type="button"
                    class="notification-feed-menu-btn"
                    aria-label="Opções da notificação arquivada"
                    aria-expanded="false"
                    aria-haspopup="true">
                <i class="fa fa-ellipsis-h" aria-hidden="true"></i>
            </button>
            <div class="notification-feed-menu" hidden>
                <form action="<?php echo DIRPAGE; ?>notification/unarchive" method="POST">
                    <?php echo Src\classes\ClassCsrf::field(); ?>
                    <input type="hidden" name="notification_id" value="<?php echo $id; ?>">
                    <button type="submit">Restaurar para inbox</button>
                </form>
                <form action="<?php echo DIRPAGE; ?>notification/delete" method="POST">
                    <?php echo Src\classes\ClassCsrf::field(); ?>
                    <input type="hidden" name="notification_id" value="<?php echo $id; ?>">
                    <button type="submit" data-confirm="Apagar definitivamente esta notificação?">Apagar</button>
                </form>
            </div>
        </div>
    <?php elseif ($showMenu): ?>
        <div class="notification-feed-menu-wrap">
            <button type="button"
                    class="notification-feed-menu-btn"
                    aria-label="Opções da notificação"
                    aria-expanded="false"
                    aria-haspopup="true">
                <i class="fa fa-ellipsis-h" aria-hidden="true"></i>
            </button>
            <div class="notification-feed-menu" hidden>
                <?php if (!$isRead): ?>
                    <?php if ($useDashboardReadActions): ?>
                        <button type="button"
                                class="notification-mark-read-btn"
                                data-notification-id="<?php echo $id; ?>"
                                data-notification-read-url="<?php echo htmlspecialchars($markReadUrl, ENT_QUOTES, 'UTF-8'); ?>">
                            Marcar como lida
                        </button>
                    <?php else: ?>
                        <form action="<?php echo DIRPAGE; ?>notification/mark_as_read" method="POST">
                            <?php echo Src\classes\ClassCsrf::field(); ?>
                            <input type="hidden" name="notification_id" value="<?php echo $id; ?>">
                            <button type="submit">Marcar como lida</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <button type="button"
                            class="notification-toggle-read-btn"
                            data-notification-id="<?php echo $id; ?>"
                            data-notification-unread-url="<?php echo htmlspecialchars($markUnreadUrl, ENT_QUOTES, 'UTF-8'); ?>">
                        Marcar como não lida
                    </button>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($archiveUrl, ENT_QUOTES, 'UTF-8'); ?>" method="POST">
                    <?php echo Src\classes\ClassCsrf::field(); ?>
                    <input type="hidden" name="notification_id" value="<?php echo $id; ?>">
                    <button type="submit">Arquivar</button>
                </form>
            </div>
        </div>
    <?php elseif (!$isRead): ?>
        <button type="button"
                class="notification-toggle-read-btn notification-feed-unread-btn"
                data-notification-id="<?php echo $id; ?>"
                data-notification-unread-url="<?php echo htmlspecialchars($markUnreadUrl, ENT_QUOTES, 'UTF-8'); ?>"
                hidden>Marcar não lida</button>
    <?php else: ?>
        <button type="button"
                class="notification-toggle-read-btn"
                data-notification-id="<?php echo $id; ?>"
                data-notification-unread-url="<?php echo htmlspecialchars($markUnreadUrl, ENT_QUOTES, 'UTF-8'); ?>"
                hidden>Marcar não lida</button>
    <?php endif; ?>
</article>
