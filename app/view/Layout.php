<!DOCTYPE html>
<?php
$cookieConsentValue = Src\classes\ClassCookieConsent::behavioralValue();
$hasCookiePreference = Src\classes\ClassCookieConsent::hasBehavioralPreference();
$currentViewDir = (string) $this->getDir();
$isPropertyModerationView = $currentViewDir === 'property/moderate';
$isDashboardLayout = Src\classes\ClassAuth::check() && (strpos($currentViewDir, 'dashboard/') === 0 || $isPropertyModerationView);
$isSubscriptionView = $currentViewDir === 'dashboard/subscription';
$dashboardSection = '';
if ($isDashboardLayout) {
    $dashboardSection = $isPropertyModerationView ? 'property_moderate' : basename($currentViewDir);
    if (in_array($dashboardSection, ['request_chats', 'request_chat'], true)) {
        $dashboardSection = 'conversations';
    }
    if ($dashboardSection === 'dispute_detail') {
        $dashboardSection = 'disputes';
    }
}
$dashboardView = isset($_GET['view']) ? trim((string) $_GET['view']) : '';
$dashboardMobileMenuHub = $isDashboardLayout && $dashboardSection === 'index' && $dashboardView !== 'overview';
$isDashboardOverviewView = $isDashboardLayout && $dashboardSection === 'index' && $dashboardView === 'overview';
$dashboardNotificationsUrl = DIRPAGE . 'notification/inbox';
$dashboardShellUser = Src\classes\ClassAuth::check() ? Src\classes\ClassAuth::user() : null;
$dashboardShellRole = $dashboardShellUser ? Src\classes\ClassAccess::getRole($dashboardShellUser) : 'utilizador';
$isLimitedPlatformUser = $dashboardShellUser && Src\classes\ClassAccess::hasLimitedPlatformAccess($dashboardShellUser);
$dashboardPanelHref = $isLimitedPlatformUser ? DIRPAGE . 'dashboard/accountStatus' : DIRPAGE . 'dashboard';
$dashboardMenuItems = [];
$isAuthLayout = strpos($currentViewDir, 'auth/') === 0;

$rawSuccess = isset($_GET['success']) ? trim((string) $_GET['success']) : '';
$rawError = isset($_GET['error']) ? trim((string) $_GET['error']) : '';

$flashSuccess = null;
$flashError = null;

if ($rawSuccess !== '') {
    $flashSuccess = ($rawSuccess === '1') ? 'Operação realizada com sucesso.' : $rawSuccess;
}

if ($rawError !== '') {
    $flashError = ($rawError === '1') ? 'Não foi possível concluir a operação.' : $rawError;
}

if ($isDashboardLayout && $dashboardShellUser) {
    if ($isLimitedPlatformUser) {
        $dashboardMenuItems[] = ['key' => 'account_status', 'label' => 'A minha conta', 'icon' => 'fa-hourglass-half', 'href' => DIRPAGE . 'dashboard/accountStatus'];
        $dashboardMenuItems[] = ['key' => 'properties', 'label' => 'Ver imóveis', 'icon' => 'fa-search', 'href' => DIRPAGE . 'properties'];
    } else {
    $dashboardMenuItems[] = ['key' => 'index', 'label' => 'Visão Geral', 'icon' => 'fa-th-large', 'href' => DIRPAGE . 'dashboard?view=overview'];
    $dashboardMenuItems[] = ['key' => 'profile', 'label' => 'Meu Perfil', 'icon' => 'fa-user-circle', 'href' => DIRPAGE . 'profile'];

    $hasRequestsMenu = false;
    if (!Src\classes\ClassAccess::isAdmin($dashboardShellUser)) {
        $dashboardMenuItems[] = ['key' => 'requests', 'label' => 'Solicitações', 'icon' => 'fa-inbox', 'href' => DIRPAGE . 'requests'];
        $dashboardMenuItems[] = ['key' => 'conversations', 'label' => 'Conversas', 'icon' => 'fa-comments', 'href' => DIRPAGE . 'dashboard/requestChats'];
        $hasRequestsMenu = true;
    }

    if (empty($dashboardShellUser['is_admin'])) {
        $dashboardMenuItems[] = ['key' => 'subscription', 'label' => 'Meu Plano', 'icon' => 'fa-diamond', 'href' => DIRPAGE . 'dashboard/subscription'];
        $dashboardMenuItems[] = ['key' => 'my_properties', 'label' => 'Meus Imóveis', 'icon' => 'fa-building', 'href' => DIRPAGE . 'dashboard/myProperties'];
        $dashboardMenuItems[] = ['key' => 'commission_payments', 'label' => 'Pagar Comissões', 'icon' => 'fa-money', 'href' => DIRPAGE . 'dashboard/commissionPayments'];
        $dashboardMenuItems[] = ['key' => 'favorites', 'label' => 'Favoritos', 'icon' => 'fa-heart', 'href' => DIRPAGE . 'dashboard/myFavorites'];
    }

    if (empty($dashboardShellUser['is_admin'])) {
        $dashboardMenuItems[] = ['key' => 'afiliados', 'label' => 'Afiliados', 'icon' => 'fa-handshake-o', 'href' => DIRPAGE . 'dashboard/afiliados'];
    }

    if (Src\classes\ClassAccess::can('users.review', $dashboardShellUser)) {
        $dashboardMenuItems[] = ['key' => 'moderate_users', 'label' => 'Perfis', 'icon' => 'fa-users', 'href' => DIRPAGE . 'dashboard/moderate_users'];
    }

    if (Src\classes\ClassAccess::can('properties.moderate', $dashboardShellUser)) {
        $dashboardMenuItems[] = ['key' => 'property_moderate', 'label' => 'Imóveis', 'icon' => 'fa-building-o', 'href' => DIRPAGE . 'property/moderate'];
    }

    if (Src\classes\ClassAccess::can('documents.review', $dashboardShellUser)) {
        $dashboardMenuItems[] = ['key' => 'review_documents', 'label' => 'Documentos', 'icon' => 'fa-file-text-o', 'href' => DIRPAGE . 'dashboard/reviewDocuments'];
    }

    if (Src\classes\ClassAccess::isSuperAdmin($dashboardShellUser)) {
        $dashboardMenuItems[] = ['key' => 'kpi', 'label' => 'KPIs', 'icon' => 'fa-line-chart', 'href' => DIRPAGE . 'dashboard/kpi'];
    }

    if (Src\classes\ClassAccess::can('requests.manage', $dashboardShellUser)) {
        if (!$hasRequestsMenu) {
            $dashboardMenuItems[] = ['key' => 'requests', 'label' => 'Solicitações', 'icon' => 'fa-inbox', 'href' => DIRPAGE . 'requests'];
            $dashboardMenuItems[] = ['key' => 'conversations', 'label' => 'Conversas', 'icon' => 'fa-comments', 'href' => DIRPAGE . 'dashboard/requestChats'];
        }
        $dashboardMenuItems[] = ['key' => 'disputes', 'label' => 'Disputas', 'icon' => 'fa-balance-scale', 'href' => DIRPAGE . 'dashboard/disputes'];
    }

    if (Src\classes\ClassAccess::can('payments.manage', $dashboardShellUser)) {
        $dashboardMenuItems[] = ['key' => 'payments', 'label' => 'Pagamentos', 'icon' => 'fa-credit-card', 'href' => DIRPAGE . 'dashboard/payments'];
        $dashboardMenuItems[] = ['key' => 'payment_transactions', 'label' => 'Transações', 'icon' => 'fa-exchange', 'href' => DIRPAGE . 'payment_transactions'];
    }

    if (Src\classes\ClassAccess::isSuperAdmin($dashboardShellUser)) {
        $dashboardMenuItems[] = ['key' => 'payment_methods', 'label' => 'Métodos', 'icon' => 'fa-list', 'href' => DIRPAGE . 'payment_methods'];
        $dashboardMenuItems[] = ['key' => 'payment_channels', 'label' => 'Canais', 'icon' => 'fa-bank', 'href' => DIRPAGE . 'payment_channels'];
        $dashboardMenuItems[] = ['key' => 'admin_subscriptions', 'label' => 'Subscrições', 'icon' => 'fa-id-card', 'href' => DIRPAGE . 'dashboard/adminSubscriptions'];
    }

    if (!Src\classes\ClassAccess::can('payments.manage', $dashboardShellUser) && Src\classes\ClassAuth::check()) {
        $dashboardMenuItems[] = ['key' => 'payment_accounts', 'label' => 'Dados de Pagamento', 'icon' => 'fa-university', 'href' => DIRPAGE . 'dashboard/paymentAccounts'];
        $dashboardMenuItems[] = ['key' => 'payment_history', 'label' => 'Histórico', 'icon' => 'fa-history', 'href' => DIRPAGE . 'dashboard/paymentHistory'];
        $dashboardMenuItems[] = ['key' => 'property_reports', 'label' => 'Relatórios', 'icon' => 'fa-bar-chart', 'href' => DIRPAGE . 'dashboard/propertyReports'];
    }

    if (Src\classes\ClassAccess::can('audit.view', $dashboardShellUser)) {
        $dashboardMenuItems[] = ['key' => 'audit_log', 'label' => 'Auditoria', 'icon' => 'fa-shield', 'href' => DIRPAGE . 'dashboard/auditLog'];
    }
    }
}
?>
<html lang="pt-br">
<head>
    <meta charset="utf-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- Basic Meta Tags -->
    <meta name="author" content="Antonio Nzage Banduenga">
    <meta name="description" content="<?php echo Src\classes\ClassSEO::sanitizeDescription($this->getDescription());?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($this->getKeywords(), ENT_QUOTES, 'UTF-8');?>">
    <meta name="language" content="<?php echo Src\classes\ClassSEO::SITE_LANGUAGE; ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <?php if (Src\classes\ClassAuth::check()): ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(Src\classes\ClassCsrf::get(), ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <title><?php echo Src\classes\ClassSEO::sanitizeTitle($this->getTitle()); ?></title>
    
    <!-- Canonical URL -->
    <?php if ($this->getCanonical()): ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($this->getCanonical(), ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    
    <!-- Open Graph Tags -->
    <meta property="og:type" content="<?php echo htmlspecialchars($this->getOgType(), ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:title" content="<?php echo Src\classes\ClassSEO::sanitizeTitle($this->getOgTitle()); ?>">
    <meta property="og:description" content="<?php echo Src\classes\ClassSEO::sanitizeDescription($this->getOgDescription()); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars(Src\classes\ClassSEO::getCanonicalUrl(), ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:site_name" content="<?php echo Src\classes\ClassSEO::SITE_NAME; ?>">
    <?php if ($this->getOgImage()): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($this->getOgImage(), ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <?php endif; ?>
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo Src\classes\ClassSEO::sanitizeTitle($this->getOgTitle()); ?>">
    <meta name="twitter:description" content="<?php echo Src\classes\ClassSEO::sanitizeDescription($this->getOgDescription()); ?>">
    <?php if ($this->getOgImage()): ?>
    <meta name="twitter:image" content="<?php echo htmlspecialchars($this->getOgImage(), ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    
    <!-- Structured Data (JSON-LD) -->
    <?php if ($this->getStructuredData()): ?>
    <script type="application/ld+json">
    <?php echo json_encode($this->getStructuredData(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
    </script>
    <?php endif; ?>

    <link rel="preconnect" href="https://i.ytimg.com" crossorigin>
    <link rel="preconnect" href="https://www.youtube-nocookie.com">
    <link rel="stylesheet" href="<?php echo DIRCSS.'Style.css?v=20260601w'?>">
    <link rel="stylesheet" href="<?php echo DIRPAGE; ?>public/vendor/font-awesome/css/font-awesome.min.css?v=4.7.0">
</head>
<body class="<?php
    $bodyClasses = $isDashboardLayout ? ['dashboard-body', 'dashboard-section-' . htmlspecialchars($dashboardSection)] : ['public-body'];
    if ($isAuthLayout) {
        $bodyClasses[] = 'auth-body';
    }
    if ($dashboardMobileMenuHub) {
        $bodyClasses[] = 'dashboard-mobile-menu-hub';
    }
    if ($isDashboardOverviewView) {
        $bodyClasses[] = 'dashboard-overview-view';
    }
    echo implode(' ', $bodyClasses);
?>" data-cookie-behavioral="<?php echo htmlspecialchars($cookieConsentValue !== '' ? $cookieConsentValue : 'unknown'); ?>">
<?php if ($isAuthLayout): ?>
<header class="auth-minimal-header">
    <div class="auth-minimal-header-inner">
        <a class="logo-name" href="<?php echo DIRPAGE; ?>"><span class="brand-imobil">Imobil</span><span class="brand-facil"> Fácil</span></a>
        <nav class="auth-minimal-nav" aria-label="Atalhos de autenticação">
            <a href="<?php echo DIRPAGE; ?>">Início</a>
            <?php if ($currentViewDir === 'auth/login'): ?>
                <a href="<?php echo DIRPAGE; ?>register">Criar conta</a>
            <?php elseif ($currentViewDir === 'auth/register'): ?>
                <a href="<?php echo DIRPAGE; ?>login">Entrar</a>
            <?php else: ?>
                <a href="<?php echo DIRPAGE; ?>login">Entrar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php else: ?>
<header class="site-header">
  <div class="header-eyebrow">
      <div class="container header-eyebrow-grid">
          <span>Encontre, anuncie e negocie imóveis de forma simples e segura</span>
          <div class="header-eyebrow-tags">
              <span>Imóveis verificados</span>
              <span>Negociação segura</span>
              <span>Pague Fácil</span>
          </div>
      </div>
  </div>
  <div class="container header-grid">
      <div class="brand">
          <a class="logo-name" href="<?php echo DIRPAGE; ?>"><span class="brand-imobil">Imobil</span><span class="brand-facil"> Fácil</span></a>
          <p class="brand-subtitle">simples para anunciar, seguro para negociar</p>
      </div>

      <button class="menu-button" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="menu">
          <i class="fa fa-bars"></i>
      </button>

      <nav class="nav-links" id="menu">
          <a href="<?php echo DIRPAGE; ?>">Home</a>
          <a href="<?php echo DIRPAGE; ?>properties">Imóveis</a>
          <a href="<?php echo DIRPAGE; ?>featured">Destaques</a>
          <?php if (Src\classes\ClassAuth::check()): ?>
            <a href="<?php echo $dashboardPanelHref; ?>">Painel</a>
          <?php endif; ?>
      </nav>

      <div class="header-actions">
          <?php if (Src\classes\ClassAuth::check()): ?>
              <?php $user = Src\classes\ClassAuth::user(); ?>
              <?php $unreadNotifications = App\model\Notification::countUnreadByUser((int) ($user['id'] ?? 0)); ?>
              <?php $unreadChatMessages = !empty($user['is_admin']) ? App\model\RequestChatMessage::countUnreadByUser((int) ($user['id'] ?? 0)) : App\model\RequestChatMessage::countUnreadForVisibleRequests((int) ($user['id'] ?? 0)); ?>
              <?php $headerNotifications = App\model\Notification::getLatestByUser((int) ($user['id'] ?? 0), 5); ?>
              <?php
                  $displayName = Src\classes\UserDisplay::publicLabel($user);
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
              <?php if (empty($user['is_admin']) && !$isLimitedPlatformUser): ?>
              <?php $favoriteCount = App\model\Favorite::countByUser((int) ($user['id'] ?? 0)); ?>
              <a href="<?php echo DIRPAGE; ?>dashboard/myFavorites"
                 class="header-icon-link header-icon-link--favorites"
                 title="<?php echo $favoriteCount > 0 ? 'Favoritos (' . (int) $favoriteCount . ')' : 'Favoritos'; ?>"
                 aria-label="<?php echo $favoriteCount > 0 ? 'Favoritos, ' . (int) $favoriteCount . ' imóveis guardados' : 'Favoritos'; ?>">
                  <i class="fa fa-heart"></i>
                  <?php if ($favoriteCount > 0): ?>
                      <span class="notification-badge header-favorites-badge"><?php echo (int) min($favoriteCount, 99); ?><?php echo $favoriteCount > 99 ? '+' : ''; ?></span>
                  <?php endif; ?>
              </a>
              <?php endif; ?>
              <?php if (!$isLimitedPlatformUser): ?>
              <a href="<?php echo DIRPAGE; ?>dashboard/requestChats" class="header-icon-link header-icon-link--chat" title="Conversas" aria-label="Conversas">
                  <i class="fa fa-comments"></i>
                  <?php if ($unreadChatMessages > 0): ?>
                      <span class="notification-badge"><?php echo (int) min($unreadChatMessages, 99); ?><?php echo $unreadChatMessages > 99 ? '+' : ''; ?></span>
                  <?php endif; ?>
              </a>
              <div class="notification-menu" id="notificationMenu" data-feed-url="<?php echo DIRPAGE; ?>dashboard/notificationsFeed" data-dashboard-url="<?php echo $dashboardNotificationsUrl; ?>" data-archive-url="<?php echo DIRPAGE . 'notification/archiveItem'; ?>" data-unread-initial="<?php echo (int) $unreadNotifications; ?>">
                  <button type="button" class="notification-link notification-trigger" title="Notificações" aria-label="Notificações" aria-expanded="false" aria-controls="notificationDropdown">
                      <i class="fa fa-bell"></i>
                      <?php if ($unreadNotifications > 0): ?>
                          <span class="notification-badge" id="notificationBadge"><?php echo (int) min($unreadNotifications, 99); ?><?php echo $unreadNotifications > 99 ? '+' : ''; ?></span>
                      <?php endif; ?>
                  </button>

                  <div class="notification-dropdown-backdrop" id="notificationDropdownBackdrop" hidden aria-hidden="true"></div>

                  <div class="notification-dropdown" id="notificationDropdown" role="dialog" aria-label="Notificações recentes">
                      <div class="notification-dropdown-head">
                          <strong>Notificações</strong>
                          <div class="notification-dropdown-head-actions">
                              <span class="notification-dropdown-unread" id="notificationUnreadLabel"<?php echo $unreadNotifications > 0 ? '' : ' hidden'; ?>><?php echo (int) $unreadNotifications; ?> não lidas</span>
                              <button type="button" class="notification-dropdown-close" id="notificationDropdownClose" aria-label="Fechar notificações">
                                  <i class="fa fa-times" aria-hidden="true"></i>
                              </button>
                          </div>
                      </div>

                      <form action="<?php echo DIRPAGE; ?>dashboard/markNotificationsRead" method="POST" class="notification-read-form" id="notificationReadForm"<?php echo $unreadNotifications > 0 ? '' : ' hidden'; ?>>
                          <?php echo Src\classes\ClassCsrf::field(); ?>
                          <button type="submit" class="notification-read-btn">Marcar todas como lidas</button>
                      </form>

                      <?php if (!empty($headerNotifications)): ?>
                          <div class="notification-list" id="notificationList">
                              <?php foreach ($headerNotifications as $notification): ?>
                                  <article class="notification-item <?php echo !empty($notification['is_read']) ? 'is-read' : 'is-unread'; ?>" data-notification-id="<?php echo (int) ($notification['id'] ?? 0); ?>">
                                      <a href="<?php echo htmlspecialchars((string) ($notification['target_url'] ?? $dashboardNotificationsUrl)); ?>"
                                         class="notification-item-main"
                                         data-notification-id="<?php echo (int) ($notification['id'] ?? 0); ?>"
                                         data-notification-read-url="<?php echo DIRPAGE; ?>dashboard/markNotificationRead/<?php echo (int) ($notification['id'] ?? 0); ?>">
                                          <span class="notification-item-meta">
                                              <span class="notification-type-badge"><?php echo htmlspecialchars((string) ($notification['type_label'] ?? 'Notificação')); ?></span>
                                          </span>
                                          <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                          <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                          <small><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?> · <?php echo htmlspecialchars((string) ($notification['action_label'] ?? 'Abrir')); ?></small>
                                      </a>
                                      <button type="button"
                                              class="notification-toggle-read-btn"
                                              data-notification-id="<?php echo (int) ($notification['id'] ?? 0); ?>"
                                              data-notification-unread-url="<?php echo DIRPAGE; ?>dashboard/markNotificationUnread/<?php echo (int) ($notification['id'] ?? 0); ?>"
                                              <?php echo !empty($notification['is_read']) ? '' : 'hidden'; ?>>Marcar não lido</button>
                                  </article>
                              <?php endforeach; ?>
                          </div>
                      <?php else: ?>
                          <div class="notification-list" id="notificationList" hidden></div>
                          <p class="notification-empty" id="notificationEmpty">Sem notificações no momento.</p>
                      <?php endif; ?>

                      <?php if (!empty($headerNotifications)): ?>
                          <p class="notification-empty" id="notificationEmpty" hidden>Sem notificações no momento.</p>
                      <?php endif; ?>

                      <a href="<?php echo $dashboardNotificationsUrl; ?>" class="notification-see-all" id="notificationSeeAll">Ver todas</a>
                  </div>
              </div>
              <?php endif; ?>
              <a href="<?php echo $isLimitedPlatformUser ? DIRPAGE . 'dashboard/accountStatus' : DIRPAGE . 'profile'; ?>" class="profile" title="<?php echo $isLimitedPlatformUser ? 'A minha conta' : 'Meu Perfil'; ?>">
                  <div class="profile-img">
                      <?php if (!empty($user['profile_photo'])): ?>
                          <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>">
                      <?php else: ?>
                          <span class="profile-fallback"><?php echo strtoupper(substr(trim((string) ($user['name'] ?? 'U')), 0, 1)); ?></span>
                      <?php endif; ?>
                  </div>
              </a>
          <?php else: ?>
              <a class="btn-secondary" href="<?php echo DIRPAGE; ?>login">Login</a>
              <a class="btn-primary" href="<?php echo DIRPAGE; ?>register">Registrar</a>
          <?php endif; ?>
      </div>
  </div>
</header>
<?php endif; ?>

<?php
    $showOverdueCommissionBanner = false;
    $overdueCommissionBannerMessage = '';
    $overdueCommissionBannerAction = 'Pagar comissões';
    if (Src\classes\ClassAuth::check()) {
        $layoutAlertUser = is_array($dashboardShellUser ?? null) ? $dashboardShellUser : Src\classes\ClassAuth::user();
        if (is_array($layoutAlertUser) && Src\classes\ClassAccess::canSubmitPropertyRequest($layoutAlertUser)) {
            $overdueReason = App\model\Commission::getOverdueBlockReason((int) ($layoutAlertUser['id'] ?? 0));
            $showOverdueCommissionBanner = $overdueReason !== null;
            if ($showOverdueCommissionBanner) {
                $overdueCommissionBannerMessage = App\model\Commission::overdueBlockMessage($overdueReason);
                if ($overdueReason === App\model\Commission::OVERDUE_BLOCK_AGUARDANDO_VALIDACAO) {
                    $overdueCommissionBannerAction = 'Ver pagamentos';
                }
            }
        }
    }
?>
<?php if (!$isAuthLayout && $showOverdueCommissionBanner): ?>
    <div class="site-global-alert site-global-alert--commission-overdue" role="alert" aria-live="polite">
        <div class="container site-global-alert-inner">
            <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
            <p class="site-global-alert-text">
                <?php echo htmlspecialchars($overdueCommissionBannerMessage); ?>
            </p>
            <a href="<?php echo DIRPAGE; ?>dashboard/commissionPayments" class="site-global-alert-action"><?php echo htmlspecialchars($overdueCommissionBannerAction); ?></a>
        </div>
    </div>
<?php endif; ?>

<main class="<?php echo $isDashboardLayout ? 'dashboard-main-shell' : ''; ?>">
<?php if (!$isAuthLayout && !$isSubscriptionView && ($flashSuccess !== null || $flashError !== null)): ?>
    <div class="<?php echo $isDashboardLayout ? 'dashboard-shell-alerts' : 'container'; ?>" style="margin-top:16px;">
        <?php if ($flashSuccess !== null): ?>
            <div class="auth-message auth-message-success"><?php echo htmlspecialchars($flashSuccess); ?></div>
        <?php endif; ?>
        <?php if ($flashError !== null): ?>
            <div class="auth-message auth-message-error"><?php echo htmlspecialchars($flashError); ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($isDashboardLayout && $dashboardShellUser): ?>
    <?php
        $sidebarName = trim((string) ($dashboardShellUser['name'] ?? 'Utilizador'));
        $sidebarNameParts = preg_split('/\s+/', $sidebarName, -1, PREG_SPLIT_NO_EMPTY);
        $sidebarDisplayName = !empty($sidebarNameParts) ? implode(' ', array_slice($sidebarNameParts, 0, 2)) : $sidebarName;
        $sidebarRoleLabel = Src\classes\ClassAccess::roleLabel($dashboardShellUser);
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
                        <?php
                            $isDashboardItemActive = $dashboardSection === $dashboardItem['key'];
                            if ($dashboardItem['key'] === 'index') {
                                $isDashboardItemActive = $dashboardSection === 'index' && $dashboardView === 'overview';
                            }
                        ?>
                        <a href="<?php echo $dashboardItem['href']; ?>" class="dashboard-side-link <?php echo $isDashboardItemActive ? 'is-active' : ''; ?>">
                            <i class="fa <?php echo htmlspecialchars($dashboardItem['icon']); ?>"></i>
                            <span><?php echo htmlspecialchars($dashboardItem['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <?php if (empty($dashboardShellUser['is_admin']) && !$isLimitedPlatformUser): ?>
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
            <?php if (!$dashboardMobileMenuHub): ?>
                <nav class="dashboard-mobile-nav" aria-label="Navegação do painel">
                    <a href="<?php echo DIRPAGE; ?>dashboard" class="dashboard-mobile-nav-back">
                        <i class="fa fa-arrow-left" aria-hidden="true"></i>
                        <span>Menu do painel</span>
                    </a>
                </nav>
            <?php endif; ?>
            <?php $this->addMain();?>
        </section>
    </div>
<?php else: ?>
<?php $this->addMain();?>
<?php endif; ?>
</main>  


<?php if ($isAuthLayout): ?>
<footer class="auth-minimal-footer">
    <div class="auth-minimal-footer-inner">
        <a href="<?php echo DIRPAGE; ?>">Voltar ao início</a>
        <span>© <?php echo date('Y'); ?> Imobil Fácil</span>
    </div>
</footer>
<?php else: ?>
 <footer class="site-footer">
    <div class="container-footer">
        <div class="footer">
            <div class="footer-brand-block">
                <span class="footer-kicker">Plataforma imobiliária</span>
                <strong><span class="brand-imobil">Imobil</span><span class="brand-facil"> Fácil</span></strong>
                <p>Encontre imóveis reais de forma simples e segura. Com a confiança da Pague Fácil.</p>
            </div>

            <div class="footer-nav-area">
                <div class="footer-column" data-footer-accordion>
                    <button type="button" class="footer-column-toggle" aria-expanded="false" aria-controls="footer-panel-explorar">
                        <span class="footer-column-title">Explorar</span>
                        <i class="fa fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="footer-links footer-column-panel is-collapsed" id="footer-panel-explorar">
                        <a href="<?php echo DIRPAGE; ?>">Home</a>
                        <a href="<?php echo DIRPAGE; ?>properties">Imoveis</a>
                        <a href="<?php echo DIRPAGE; ?>featured">Destaques</a>
                    </div>
                </div>

                <div class="footer-column" data-footer-accordion>
                    <button type="button" class="footer-column-toggle" aria-expanded="false" aria-controls="footer-panel-conta">
                        <span class="footer-column-title">Conta</span>
                        <i class="fa fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="footer-links footer-column-panel is-collapsed" id="footer-panel-conta">
                        <?php if (Src\classes\ClassAuth::check()): ?>
                            <a href="<?php echo $dashboardPanelHref; ?>">Painel</a>
                            <a href="<?php echo $isLimitedPlatformUser ? DIRPAGE . 'dashboard/accountStatus' : DIRPAGE . 'profile'; ?>"><?php echo $isLimitedPlatformUser ? 'A minha conta' : 'Perfil'; ?></a>
                            <a href="<?php echo DIRPAGE; ?>logout">Sair</a>
                        <?php else: ?>
                            <a href="<?php echo DIRPAGE; ?>login">Login</a>
                            <a href="<?php echo DIRPAGE; ?>register">Registrar</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="footer-column" data-footer-accordion>
                    <button type="button" class="footer-column-toggle" aria-expanded="false" aria-controls="footer-panel-legal">
                        <span class="footer-column-title">Base legal</span>
                        <i class="fa fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="footer-links footer-column-panel is-collapsed" id="footer-panel-legal">
                        <a href="<?php echo DIRPAGE; ?>cookies">Politica de Cookies</a>
                        <a href="#" data-open-cookie-consent="1">Gerir Cookies</a>
                        <?php if (Src\classes\ClassAuth::check()): ?>
                            <a href="<?php echo DIRPAGE; ?>profile">Configurações</a>
                        <?php else: ?>
                            <a href="<?php echo DIRPAGE; ?>login">Configurações</a>
                        <?php endif; ?>
                        <span>Politica de Privacidade em breve</span>
                        <span>Termos e Condições em breve</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom-bar">
            <div class="copyright">© 2026 Imobil Fácil — Plataforma imobiliária operada pela Pague Fácil, Comércio e Serviços, LDA</div>
            <div class="footer-bottom-note">Simplicidade · Confiança · Transparência</div>
        </div>
    </div>
</footer>
<?php endif; ?>

<section id="cookieConsentBanner" class="cookie-consent<?php echo $hasCookiePreference ? ' is-hidden' : ''; ?>" role="dialog" aria-live="polite" aria-label="Consentimento de cookies">
    <div class="cookie-consent-card">
        <div class="cookie-consent-copy">
            <strong>Preferencias de cookies</strong>
            <p>
                Utilizamos cookies essenciais para funcionamento da plataforma e, com o seu consentimento,
                usamos informacoes de navegacao para melhorar a sua experiencia no sistema.
                Saiba mais em <a href="<?php echo DIRPAGE; ?>cookies">Politica de Cookies</a>.
            </p>
        </div>
        <div class="cookie-consent-actions">
            <button type="button" class="btn-secondary" id="cookieRejectBtn">Rejeitar personalizacao</button>
            <button type="button" class="btn-primary" id="cookieAcceptBtn">Aceitar personalizacao</button>
        </div>
    </div>
</section>
<script src="<?php echo DIRJS.'script.js?v=20260601s'?>"></script>
</body>
</html>             