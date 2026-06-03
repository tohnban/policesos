<?php
/**
 * app/view/notification/inbox.php
 * User's notification inbox with unread items and bulk actions
 */

/** @var \App\model\Notification[] $notifications */
/** @var int $page */
/** @var int $limit */
/** @var int $total */
/** @var int $unread */

$baseUrl = $_SERVER['APP_URL'] ?? '';
$cursorMode = !empty($cursorMode) || !empty($data['cursorMode']);
$nextCursor = (string) ($nextCursor ?? ($data['nextCursor'] ?? ''));
$totalPages = ceil($total / $limit);
?>

<div class="notification-inbox-container" style="max-width: 800px; margin: 20px auto; padding: 0 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="margin: 0 0 8px 0; font-size: 28px; color: #333;">Notificações</h1>
            <p style="margin: 0; color: #999; font-size: 14px;">
                <?php echo $total; ?> notificação(ões), <?php echo $unread; ?> não lida(s)
            </p>
        </div>
        <?php if ($unread > 0): ?>
            <form action="<?php echo DIRPAGE; ?>notification/mark_all_as_read" method="POST" style="margin: 0;">
                <?php echo Src\classes\ClassCsrf::field(); ?>
                <button type="submit" style="
                    padding: 10px 16px;
                    background: #3498db;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 14px;
                ">Marcar tudo como lido</button>
            </form>
        <?php endif; ?>
    </div>

    <div id="notifications-list">
        <?php if (empty($notifications)): ?>
            <div style="text-align: center; padding: 40px 20px; color: #999;">
                <p style="font-size: 16px; margin: 0;">Sem notificações</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item" data-id="<?php echo $notif['id']; ?>" style="
                    padding: 16px;
                    border: 1px solid #eee;
                    border-radius: 4px;
                    margin-bottom: 12px;
                    background: <?php echo $notif['is_read'] ? '#fff' : '#f9f9f9'; ?>;
                    opacity: <?php echo $notif['is_read'] ? '0.8' : '1'; ?>;
                ">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
                        <div style="flex: 1;">
                            <h3 style="margin: 0 0 8px 0; font-size: 16px; color: #333; font-weight: 600;">
                                <?php echo htmlspecialchars($notif['title'] ?? 'Notificação'); ?>
                                <?php if (!$notif['is_read']): ?>
                                    <span style="display: inline-block; width: 8px; height: 8px; background: #3498db; border-radius: 50%; margin-left: 8px;"></span>
                                <?php endif; ?>
                            </h3>
                            <p style="margin: 0 0 12px 0; font-size: 14px; color: #666; line-height: 1.5;">
                                <?php echo htmlspecialchars($notif['message'] ?? ''); ?>
                            </p>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <span style="font-size: 12px; color: #999;">
                                    <?php echo date('d \d\e M \à\s H:i', strtotime($notif['created_at'])); ?>
                                </span>
                                <span style="font-size: 12px; background: #f0f0f0; padding: 2px 8px; border-radius: 12px; color: #666;">
                                    <?php echo htmlspecialchars($notif['type'] ?? 'Geral'); ?>
                                </span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <?php if (!$notif['is_read']): ?>
                                <form action="<?php echo DIRPAGE; ?>notification/mark_as_read" method="POST" style="margin: 0;">
                                    <?php echo Src\classes\ClassCsrf::field(); ?>
                                    <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                    <button type="submit" title="Marcar como lido" style="
                                        background: #e8f4f8;
                                        border: 1px solid #b3e5fc;
                                        border-radius: 4px;
                                        padding: 6px 10px;
                                        cursor: pointer;
                                        font-size: 12px;
                                        color: #0277bd;
                                    ">✓ Lido</button>
                                </form>
                            <?php endif; ?>
                            <form action="<?php echo DIRPAGE; ?>notification/archiveItem" method="POST" style="margin: 0;">
                                <?php echo Src\classes\ClassCsrf::field(); ?>
                                <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                <button type="submit" title="Arquivar" style="
                                    background: #f5f5f5;
                                    border: 1px solid #ddd;
                                    border-radius: 4px;
                                    padding: 6px 10px;
                                    cursor: pointer;
                                    font-size: 12px;
                                    color: #999;
                                ">📦</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if (!$cursorMode && $totalPages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 8px; margin-top: 32px; flex-wrap: wrap;">
            <?php if ($page > 1): ?>
                <a href="?page=1" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #0277bd;">«</a>
                <a href="?page=<?php echo $page - 1; ?>" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #0277bd;">‹ Anterior</a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++) {
                $isCurrent = $i === $page;
                $bg = $isCurrent ? '#3498db' : '#f5f5f5';
                $color = $isCurrent ? 'white' : '#333';
                $href = $isCurrent ? '#' : "?page=$i";
                echo "<a href=\"$href\" style=\"padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; background: $bg; color: $color;\">$i</a>";
            }
            ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #0277bd;">Próxima ›</a>
                <a href="?page=<?php echo $totalPages; ?>" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #0277bd;">»</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($cursorMode && $nextCursor !== ''): ?>
        <div style="display: flex; justify-content: center; gap: 8px; margin-top: 32px; flex-wrap: wrap;">
            <a href="<?php echo htmlspecialchars('?cursor=' . urlencode($nextCursor), ENT_QUOTES, 'UTF-8'); ?>"
               style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #0277bd;">
                Próxima ›
            </a>
        </div>
    <?php endif; ?>
</div>
