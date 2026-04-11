<!DOCTYPE html>
<?php
$currentViewDir = (string) $this->getDir();
$isDashboardLayout = Src\classes\ClassAuth::check() && strpos($currentViewDir, 'dashboard/') === 0;
$dashboardSection = $isDashboardLayout ? basename($currentViewDir) : '';
$dashboardShellUser = Src\classes\ClassAuth::check() ? Src\classes\ClassAuth::user() : null;
$dashboardShellRole = $dashboardShellUser ? Src\classes\ClassAccess::getRole($dashboardShellUser) : 'utilizador';
$dashboardMenuItems = [];

if ($isDashboardLayout && $dashboardShellUser) {
    $dashboardMenuItems[] = ['key' => 'index', 'label' => 'Visão Geral', 'icon' => 'fa-th-large', 'href' => DIRPAGE . 'dashboard'];
    $dashboardMenuItems[] = ['key' => 'profile', 'label' => 'Meu Perfil', 'icon' => 'fa-user-circle', 'href' => DIRPAGE . 'profile'];
    $dashboardMenuItems[] = ['key' => 'requests', 'label' => 'Solicitações', 'icon' => 'fa-inbox', 'href' => DIRPAGE . 'requests'];

    if (empty($dashboardShellUser['is_admin'])) {
        $dashboardMenuItems[] = ['key' => 'my_properties', 'label' => 'Meus Imóveis', 'icon' => 'fa-building', 'href' => DIRPAGE . 'dashboard/myProperties'];
    }

    if (!empty($dashboardShellUser['is_affiliate'])) {
        $dashboardMenuItems[] = ['key' => 'commissions', 'label' => 'Comissões', 'icon' => 'fa-money', 'href' => DIRPAGE . 'commissions'];
        $dashboardMenuItems[] = ['key' => 'referrals', 'label' => 'Indicações', 'icon' => 'fa-link', 'href' => DIRPAGE . 'referrals'];
    }

    if (Src\classes\ClassAccess::can('users.review', $dashboardShellUser)) {
        $dashboardMenuItems[] = ['key' => 'moderate_users', 'label' => 'Perfis', 'icon' => 'fa-users', 'href' => DIRPAGE . 'dashboard/moderate_users'];
    }

    if (Src\classes\ClassAccess::can('documents.review', $dashboardShellUser)) {
        $dashboardMenuItems[] = ['key' => 'review_documents', 'label' => 'Documentos', 'icon' => 'fa-file-text-o', 'href' => DIRPAGE . 'dashboard/reviewDocuments'];
    }

    if (Src\classes\ClassAccess::can('kpi.view', $dashboardShellUser)) {
        $dashboardMenuItems[] = ['key' => 'kpi', 'label' => 'KPIs', 'icon' => 'fa-line-chart', 'href' => DIRPAGE . 'dashboard/kpi'];
    }

    if (Src\classes\ClassAccess::can('payments.manage', $dashboardShellUser)) {
        $dashboardMenuItems[] = ['key' => 'payments', 'label' => 'Pagamentos', 'icon' => 'fa-credit-card', 'href' => DIRPAGE . 'dashboard/payments'];
    }

    if (Src\classes\ClassAccess::can('audit.view', $dashboardShellUser)) {
        $dashboardMenuItems[] = ['key' => 'audit_log', 'label' => 'Auditoria', 'icon' => 'fa-shield', 'href' => DIRPAGE . 'dashboard/auditLog'];
    }
}
?>
<html lang="pt-br">
<head>
    <meta charset="utf-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <meta name="author" content="Antonio Nzage Banduenga">
    <meta name="description" content="<?php echo $this->getDescription();?>">
    <meta name="keywords" content="<?php echo $this->getKeywords();?>">
    <title> <?php echo $this->getTitle();?> </title>

    <link rel="stylesheet" href="<?php echo DIRCSS.'Style.css?v=20260420'?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="<?php echo $isDashboardLayout ? 'dashboard-body' : 'public-body'; ?>">
<header class="site-header">
  <div class="header-eyebrow">
      <div class="container header-eyebrow-grid">
          <span>Curadoria imobiliaria para compra, aluguer e afiliacao</span>
          <div class="header-eyebrow-tags">
              <span>Leads qualificados</span>
              <span>Imoveis verificados</span>
              <span>Gestao comercial rapida</span>
          </div>
      </div>
  </div>
  <div class="container header-grid">
      <div class="brand">
          <a class="logo-name" href="<?php echo DIRPAGE; ?>">Imobil</a>
          <p class="brand-subtitle">Plataforma imobiliária moderna</p>
      </div>

      <button class="menu-button" type="button" onclick="toggleMenu()" aria-label="Abrir menu" aria-expanded="false" aria-controls="menu">
          <i class="fa fa-bars"></i>
      </button>

      <nav class="nav-links" id="menu">
          <a href="<?php echo DIRPAGE; ?>">Home</a>
          <a href="<?php echo DIRPAGE; ?>properties">Imóveis</a>
          <a href="<?php echo DIRPAGE; ?>featured">Destaques</a>
          <?php if (Src\classes\ClassAuth::check()): ?>
            <a href="<?php echo DIRPAGE; ?>dashboard">Painel</a>
          <?php endif; ?>
      </nav>

      <div class="header-actions">
          <?php if (Src\classes\ClassAuth::check()): ?>
              <?php $user = Src\classes\ClassAuth::user(); ?>
              <?php $unreadNotifications = App\model\Notification::countUnreadByUser((int) ($user['id'] ?? 0)); ?>
              <?php $headerNotifications = App\model\Notification::getLatestByUser((int) ($user['id'] ?? 0), 5); ?>
              <?php
                  $rawName = trim((string) ($user['name'] ?? 'Utilizador'));
                  $nameParts = preg_split('/\s+/', $rawName, -1, PREG_SPLIT_NO_EMPTY);
                  $displayName = $rawName;
                  if (!empty($nameParts)) {
                      $displayName = implode(' ', array_slice($nameParts, 0, 2));
                  }
                  $roleLabel = 'Utilizador';
                  $roleValue = Src\classes\ClassAccess::getRole($user);
                  if ($roleValue === 'super_admin') {
                      $roleLabel = 'Super Admin';
                  } elseif ($roleValue === 'moderador') {
                      $roleLabel = 'Moderador';
                  } elseif ($roleValue === 'financeiro') {
                      $roleLabel = 'Financeiro';
                  } elseif ($roleValue === 'suporte') {
                      $roleLabel = 'Suporte';
                  } elseif (!empty($user['is_affiliate'])) {
                      $roleLabel = 'Afiliado';
                  }
              ?>
              <div class="notification-menu" id="notificationMenu" data-feed-url="<?php echo DIRPAGE; ?>dashboard/notificationsFeed" data-dashboard-url="<?php echo DIRPAGE; ?>dashboard#notifications" data-unread-initial="<?php echo (int) $unreadNotifications; ?>">
                  <button type="button" class="notification-link notification-trigger" title="Notificações" aria-label="Notificações" aria-expanded="false" onclick="toggleNotificationMenu(event)">
                      <i class="fa fa-bell"></i>
                      <span class="notification-label">Notificações</span>
                      <?php if ($unreadNotifications > 0): ?>
                          <span class="notification-badge" id="notificationBadge"><?php echo (int) min($unreadNotifications, 99); ?><?php echo $unreadNotifications > 99 ? '+' : ''; ?></span>
                      <?php endif; ?>
                  </button>

                  <div class="notification-dropdown" id="notificationDropdown">
                      <div class="notification-dropdown-head">
                          <strong>Notificações</strong>
                          <span class="notification-dropdown-unread" id="notificationUnreadLabel"<?php echo $unreadNotifications > 0 ? '' : ' hidden'; ?>><?php echo (int) $unreadNotifications; ?> não lidas</span>
                      </div>

                      <form action="<?php echo DIRPAGE; ?>dashboard/markNotificationsRead" method="POST" class="notification-read-form" id="notificationReadForm"<?php echo $unreadNotifications > 0 ? '' : ' hidden'; ?>>
                          <?php echo Src\classes\ClassCsrf::field(); ?>
                          <button type="submit" class="notification-read-btn">Marcar todas como lidas</button>
                      </form>

                      <?php if (!empty($headerNotifications)): ?>
                          <div class="notification-list" id="notificationList">
                              <?php foreach ($headerNotifications as $notification): ?>
                                  <a href="<?php echo DIRPAGE; ?>dashboard#notifications" class="notification-item <?php echo !empty($notification['is_read']) ? 'is-read' : 'is-unread'; ?>">
                                      <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                      <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                      <small><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></small>
                                  </a>
                              <?php endforeach; ?>
                          </div>
                      <?php else: ?>
                          <div class="notification-list" id="notificationList" hidden></div>
                          <p class="notification-empty" id="notificationEmpty">Sem notificações no momento.</p>
                      <?php endif; ?>

                      <?php if (!empty($headerNotifications)): ?>
                          <p class="notification-empty" id="notificationEmpty" hidden>Sem notificações no momento.</p>
                      <?php endif; ?>

                      <a href="<?php echo DIRPAGE; ?>dashboard#notifications" class="notification-see-all" id="notificationSeeAll">Ver todas</a>
                  </div>
              </div>
              <a href="<?php echo DIRPAGE; ?>dashboard/profile" class="profile" title="Meu Perfil">
                  <div class="profile-img">
                      <?php if (!empty($user['profile_photo'])): ?>
                          <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>">
                      <?php else: ?>
                          <span class="profile-fallback"><?php echo strtoupper(substr(trim((string) ($user['name'] ?? 'U')), 0, 1)); ?></span>
                      <?php endif; ?>
                  </div>
                  <span class="profile-meta">
                      <span class="name"><?php echo htmlspecialchars($displayName); ?></span>
                      <span class="role-badge role-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $roleLabel))); ?>"><?php echo htmlspecialchars($roleLabel); ?></span>
                  </span>
              </a>
          <?php else: ?>
              <a class="btn-secondary" href="<?php echo DIRPAGE; ?>login">Login</a>
              <a class="btn-primary" href="<?php echo DIRPAGE; ?>register">Registrar</a>
          <?php endif; ?>
      </div>
  </div>
</header>


<main class="<?php echo $isDashboardLayout ? 'dashboard-main-shell' : ''; ?>">
<?php if ($isDashboardLayout && $dashboardShellUser): ?>
    <?php
        $sidebarName = trim((string) ($dashboardShellUser['name'] ?? 'Utilizador'));
        $sidebarNameParts = preg_split('/\s+/', $sidebarName, -1, PREG_SPLIT_NO_EMPTY);
        $sidebarDisplayName = !empty($sidebarNameParts) ? implode(' ', array_slice($sidebarNameParts, 0, 2)) : $sidebarName;
        $sidebarRoleLabel = 'Utilizador';
        if ($dashboardShellRole === 'super_admin') {
            $sidebarRoleLabel = 'Super Admin';
        } elseif ($dashboardShellRole === 'moderador') {
            $sidebarRoleLabel = 'Moderador';
        } elseif ($dashboardShellRole === 'financeiro') {
            $sidebarRoleLabel = 'Financeiro';
        } elseif ($dashboardShellRole === 'suporte') {
            $sidebarRoleLabel = 'Suporte';
        } elseif (!empty($dashboardShellUser['is_affiliate'])) {
            $sidebarRoleLabel = 'Afiliado';
        }
    ?>
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="dashboard-sidebar-card">
                <div class="dashboard-sidebar-avatar">
                    <?php echo strtoupper(substr(trim((string) ($dashboardShellUser['name'] ?? 'U')), 0, 1)); ?>
                </div>
                <div class="dashboard-sidebar-meta">
                    <strong><?php echo htmlspecialchars($sidebarDisplayName); ?></strong>
                    <span><?php echo htmlspecialchars($sidebarRoleLabel); ?></span>
                </div>
            </div>

            <div class="dashboard-sidebar-panel">
                <div class="dashboard-sidebar-kicker">Painel</div>
                <nav class="dashboard-side-nav">
                    <?php foreach ($dashboardMenuItems as $dashboardItem): ?>
                        <a href="<?php echo $dashboardItem['href']; ?>" class="dashboard-side-link <?php echo $dashboardSection === $dashboardItem['key'] ? 'is-active' : ''; ?>">
                            <i class="fa <?php echo htmlspecialchars($dashboardItem['icon']); ?>"></i>
                            <span><?php echo htmlspecialchars($dashboardItem['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <?php if (empty($dashboardShellUser['is_admin'])): ?>
                <a href="<?php echo DIRPAGE; ?>property/create" class="dashboard-sidebar-cta">
                    <i class="fa fa-plus-circle"></i>
                    <span>Novo imóvel</span>
                </a>
            <?php endif; ?>

            <a href="<?php echo DIRPAGE; ?>logout" class="dashboard-side-logout">
                <i class="fa fa-sign-out"></i>
                <span>Sair</span>
            </a>
        </aside>

        <section class="dashboard-shell-content">
            <?php $this->addMain();?>
        </section>
    </div>
<?php else: ?>
<?php $this->addMain();?>
<?php endif; ?>
</main>  


 <footer class="site-footer">
    <div class="container-footer">
        <div class="footer">
            <div class="footer-brand-block">
                <span class="footer-kicker">Mercado imobiliario</span>
                <strong>Imobil</strong>
                <p>Uma frente comercial pensada para descoberta, confianca e conversao em cada etapa da jornada imobiliaria.</p>
            </div>

            <div class="footer-columns">
                <div class="footer-column">
                    <span class="footer-column-title">Explorar</span>
                    <div class="footer-links">
                        <a href="<?php echo DIRPAGE; ?>">Home</a>
                        <a href="<?php echo DIRPAGE; ?>properties">Imoveis</a>
                        <a href="<?php echo DIRPAGE; ?>featured">Destaques</a>
                    </div>
                </div>

                <div class="footer-column">
                    <span class="footer-column-title">Conta</span>
                    <div class="footer-links">
                        <?php if (Src\classes\ClassAuth::check()): ?>
                            <a href="<?php echo DIRPAGE; ?>dashboard">Painel</a>
                            <a href="<?php echo DIRPAGE; ?>dashboard/profile">Perfil</a>
                            <a href="<?php echo DIRPAGE; ?>logout">Sair</a>
                        <?php else: ?>
                            <a href="<?php echo DIRPAGE; ?>login">Login</a>
                            <a href="<?php echo DIRPAGE; ?>register">Registrar</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="footer-column">
                    <span class="footer-column-title">Base legal</span>
                    <div class="footer-links">
                        <a href="<?php echo DIRPAGE.'#'?>">Configurações</a>
                        <a href="">Política de Privacidade</a>
                        <a href="">Termos e Condições</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom-bar">
            <div class="copyright">© 2026 Imobil - Todos os direitos reservados</div>
            <div class="footer-bottom-note">Design orientado para conversao, moderacao e operacao comercial.</div>
        </div>
    </div>
</footer>
<script>
    function toggleMenu() {
        var menu = document.getElementById('menu');
        var button = document.querySelector('.menu-button');
        if (!menu) {
            return;
        }

        var isOpen = menu.classList.toggle('menu-open');
        if (button) {
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    }

    function toggleNotificationMenu(event) {
        event.preventDefault();
        event.stopPropagation();

        var menu = document.getElementById('notificationMenu');
        var trigger = menu ? menu.querySelector('.notification-trigger') : null;
        if (!menu || !trigger) {
            return;
        }

        var isOpen = menu.classList.toggle('is-open');
        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    document.addEventListener('click', function(event) {
        var menu = document.getElementById('notificationMenu');
        var trigger = menu ? menu.querySelector('.notification-trigger') : null;
        if (!menu || !trigger) {
            return;
        }

        if (!menu.contains(event.target)) {
            menu.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key !== 'Escape') {
            return;
        }

        var menu = document.getElementById('notificationMenu');
        var trigger = menu ? menu.querySelector('.notification-trigger') : null;
        if (!menu || !trigger) {
            return;
        }

        menu.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
    });

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderNotificationItems(items, dashboardUrl) {
        return items.map(function(item) {
            var stateClass = item.is_read ? 'is-read' : 'is-unread';
            return '<a href="' + dashboardUrl + '" class="notification-item ' + stateClass + '">' +
                '<strong>' + escapeHtml(item.title || '') + '</strong>' +
                '<p>' + escapeHtml(item.message || '') + '</p>' +
                '<small>' + escapeHtml(item.created_at_label || '') + '</small>' +
            '</a>';
        }).join('');
    }

    function updateNotificationTitle(unreadCount) {
        var baseTitle = document.body.getAttribute('data-base-title');
        if (!baseTitle) {
            baseTitle = document.title;
            document.body.setAttribute('data-base-title', baseTitle);
        }

        document.title = unreadCount > 0 ? '(' + unreadCount + ') ' + baseTitle : baseTitle;
    }

    function pulseNotificationTrigger() {
        var menu = document.getElementById('notificationMenu');
        var trigger = menu ? menu.querySelector('.notification-trigger') : null;
        if (!trigger) {
            return;
        }

        trigger.classList.remove('notification-pulse');
        void trigger.offsetWidth;
        trigger.classList.add('notification-pulse');

        window.setTimeout(function() {
            trigger.classList.remove('notification-pulse');
        }, 1500);
    }

    function updateNotificationUi(payload) {
        var menu = document.getElementById('notificationMenu');
        if (!menu || !payload) {
            return;
        }

        var unreadCount = Number(payload.unread_count || 0);
        var previousUnreadCount = Number(menu.getAttribute('data-unread-initial') || 0);
        var notifications = Array.isArray(payload.notifications) ? payload.notifications : [];
        var badge = document.getElementById('notificationBadge');
        var unreadLabel = document.getElementById('notificationUnreadLabel');
        var list = document.getElementById('notificationList');
        var empty = document.getElementById('notificationEmpty');
        var readForm = document.getElementById('notificationReadForm');
        var dashboardUrl = menu.getAttribute('data-dashboard-url') || '#';

        if (unreadCount > previousUnreadCount) {
            pulseNotificationTrigger();
        }

        menu.setAttribute('data-unread-initial', String(unreadCount));
        updateNotificationTitle(unreadCount);

        if (unreadCount > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.id = 'notificationBadge';
                badge.className = 'notification-badge';
                var trigger = menu.querySelector('.notification-trigger');
                if (trigger) {
                    trigger.appendChild(badge);
                }
            }
            badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);

            if (!unreadLabel) {
                unreadLabel = document.createElement('span');
                unreadLabel.id = 'notificationUnreadLabel';
                unreadLabel.className = 'notification-dropdown-unread';
                var head = menu.querySelector('.notification-dropdown-head');
                if (head) {
                    head.appendChild(unreadLabel);
                }
            }
            unreadLabel.textContent = unreadCount + ' não lidas';
            unreadLabel.hidden = false;

            if (readForm) {
                readForm.hidden = false;
            }
        } else {
            if (badge) {
                badge.remove();
            }
            if (unreadLabel) {
                unreadLabel.hidden = true;
            }
            if (readForm) {
                readForm.hidden = true;
            }
        }

        if (list) {
            if (notifications.length > 0) {
                list.innerHTML = renderNotificationItems(notifications, dashboardUrl);
                list.hidden = false;
                if (empty) {
                    empty.hidden = true;
                }
            } else {
                list.innerHTML = '';
                list.hidden = true;
                if (empty) {
                    empty.hidden = false;
                }
            }
        }
    }

    function startNotificationPolling() {
        var menu = document.getElementById('notificationMenu');
        if (!menu || typeof fetch !== 'function') {
            return;
        }

        var feedUrl = menu.getAttribute('data-feed-url');
        if (!feedUrl) {
            return;
        }

        var isPolling = false;
        var poll = function() {
            if (isPolling) {
                return;
            }

            isPolling = true;
            fetch(feedUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Falha ao buscar notificações');
                    }
                    return response.json();
                })
                .then(function(payload) {
                    updateNotificationUi(payload);
                })
                .catch(function() {
                    // Mantém a UI atual em caso de falha transitória.
                })
                .finally(function() {
                    isPolling = false;
                });
        };

        updateNotificationTitle(Number(menu.getAttribute('data-unread-initial') || 0));
        poll();
        window.setInterval(poll, 30000);
    }

    startNotificationPolling();
</script>
<script src="<?php echo DIRJS.'script.js?v=20260421'?>"></script>
</body>
</html>             