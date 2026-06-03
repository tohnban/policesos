<?php
/**
 * app/view/notification/archive.php
 * User's archived notifications with type filtering
 */

/** @var \App\model\Notification[] $notifications */
/** @var int $page */
/** @var int $limit */
/** @var int $total */
/** @var string|null $typeFilter */

$baseUrl = $_SERVER['APP_URL'] ?? '';
$cursorMode = !empty($cursorMode) || !empty($data['cursorMode']);
$nextCursor = (string) ($nextCursor ?? ($data['nextCursor'] ?? ''));
$totalPages = ceil($total / $limit);
?>

<div class="notification-archive-container" style="max-width: 800px; margin: 20px auto; padding: 0 20px;">
    <div style="margin-bottom: 24px;">
        <h1 style="margin: 0 0 8px 0; font-size: 28px; color: #333;">Arquivo de Notificações</h1>
        <p style="margin: 0; color: #999; font-size: 14px;">
            Total: <?php echo $total; ?> notificação(ões) arquivada(s)
        </p>
    </div>

    <!-- Type Filter -->
    <div style="margin-bottom: 20px; display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="?page=1" style="
            padding: 8px 12px;
            background: <?php echo $typeFilter === null ? '#3498db' : '#f5f5f5'; ?>;
            color: <?php echo $typeFilter === null ? 'white' : '#333'; ?>;
            border: 1px solid <?php echo $typeFilter === null ? '#3498db' : '#ddd'; ?>;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            cursor: pointer;
        ">Todas</a>

        <?php
        $types = ['property_update', 'request_status', 'payment', 'system', 'message'];
        foreach ($types as $type) {
            $isActive = $typeFilter === $type;
            $bg = $isActive ? '#3498db' : '#f5f5f5';
            $color = $isActive ? 'white' : '#333';
            $border = $isActive ? '#3498db' : '#ddd';
            $href = "?page=1&type=$type";
            $label = ucfirst(str_replace('_', ' ', $type));
            echo "<a href=\"$href\" style=\"padding: 8px 12px; background: $bg; color: $color; border: 1px solid $border; border-radius: 4px; text-decoration: none; font-size: 13px;\">$label</a>";
        }
        ?>
    </div>

    <!-- Notifications List -->
    <div id="notifications-list">
        <?php if (empty($notifications)): ?>
            <div style="text-align: center; padding: 40px 20px; color: #999;">
                <p style="font-size: 16px; margin: 0;">Nenhuma notificação arquivada</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item" data-id="<?php echo $notif['id']; ?>" style="
                    padding: 16px;
                    border: 1px solid #eee;
                    border-radius: 4px;
                    margin-bottom: 12px;
                    background: #fafafa;
                ">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
                        <div style="flex: 1;">
                            <h3 style="margin: 0 0 8px 0; font-size: 16px; color: #333; font-weight: 600;">
                                <?php echo htmlspecialchars($notif['title'] ?? 'Notificação'); ?>
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
                            <form action="<?php echo DIRPAGE; ?>notification/unarchive" method="POST" style="margin: 0;">
                                <?php echo Src\classes\ClassCsrf::field(); ?>
                                <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                <button type="submit" title="Restaurar" style="
                                    background: #e8f8e8;
                                    border: 1px solid #c8e6c9;
                                    border-radius: 4px;
                                    padding: 6px 10px;
                                    cursor: pointer;
                                    font-size: 12px;
                                    color: #388e3c;
                                ">↩ Restaurar</button>
                            </form>
                            <form action="<?php echo DIRPAGE; ?>notification/delete" method="POST" style="margin: 0;">
                                <?php echo Src\classes\ClassCsrf::field(); ?>
                                <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                <button type="submit" title="Apagar definitivamente" data-confirm="Apagar definitivamente?" style="
                                    background: #ffebee;
                                    border: 1px solid #ffcdd2;
                                    border-radius: 4px;
                                    padding: 6px 10px;
                                    cursor: pointer;
                                    font-size: 12px;
                                    color: #d32f2f;
                                ">🗑</button>
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
                <a href="<?php echo $typeFilter ? "?page=1&type=$typeFilter" : '?page=1'; ?>" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #0277bd;">«</a>
                <a href="<?php echo $typeFilter ? "?page=" . ($page - 1) . "&type=$typeFilter" : '?page=' . ($page - 1); ?>" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #0277bd;">‹ Anterior</a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++) {
                $isCurrent = $i === $page;
                $bg = $isCurrent ? '#3498db' : '#f5f5f5';
                $color = $isCurrent ? 'white' : '#333';
                $href = $typeFilter ? "?page=$i&type=$typeFilter" : "?page=$i";
                if (!$isCurrent) {
                    echo "<a href=\"$href\" style=\"padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; background: $bg; color: $color;\">$i</a>";
                } else {
                    echo "<div style=\"padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; background: $bg; color: $color;\">$i</div>";
                }
            }
            ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?php echo $typeFilter ? "?page=" . ($page + 1) . "&type=$typeFilter" : '?page=' . ($page + 1); ?>" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #0277bd;">Próxima ›</a>
                <a href="<?php echo $typeFilter ? "?page=$totalPages&type=$typeFilter" : "?page=$totalPages"; ?>" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #0277bd;">»</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($cursorMode && $nextCursor !== ''): ?>
        <div style="display: flex; justify-content: center; gap: 8px; margin-top: 32px; flex-wrap: wrap;">
            <a href="<?php echo htmlspecialchars('?cursor=' . urlencode($nextCursor) . ($typeFilter ? '&type=' . urlencode((string) $typeFilter) : ''), ENT_QUOTES, 'UTF-8'); ?>"
               style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #0277bd;">
                Próxima ›
            </a>
        </div>
    <?php endif; ?>
</div>
