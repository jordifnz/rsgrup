<?php
// templates/partials/header.php
$currentUserId = $_SESSION['user_id'] ?? null;
if ($currentUserId) {
    $user = [
        'id'       => $currentUserId,
        'name'     => $_SESSION['user_name']     ?? '',
        'surnames' => $_SESSION['user_surnames'] ?? '',
        'email'    => $_SESSION['user_email']    ?? '',
        'role'     => $_SESSION['user_role']     ?? ROLE_ALUMNO,
        'avatar'   => $_SESSION['user_avatar']   ?? null,
    ];
} else {
    $user = null;
}
$isAdmin   = ($user['role'] ?? '') === ROLE_ADMIN;
$metaTitle = $metaTitle ?? APP_NAME;
$robots    = $robots    ?? 'noindex,nofollow';
$extraCss  = $extraCss  ?? [];

// Color de marca dinámico
$accentColor = '#e87722';
try {
    $accentRow = Database::fetchRow("SELECT `value` FROM rsgrup_settings WHERE `key`='brand_accent_color'");
    if ($accentRow && !empty($accentRow['value'])) $accentColor = $accentRow['value'];
} catch(\Throwable $e) {}

// Número WhatsApp para enlace Soporte en nav
$waNavNumber = '';
try {
    $waRow = Database::fetchRow("SELECT `value` FROM rsgrup_settings WHERE `key`='whatsapp_support_number'");
    if ($waRow && !empty($waRow['value'])) {
        $waNavNumber = preg_replace('/[^0-9]/', '', $waRow['value']);
    }
} catch(\Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="<?= $robots ?>">
<title><?= htmlspecialchars($metaTitle) ?> | <?= APP_NAME ?></title>
<link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/assets/img/favicon.ico">
<link rel="shortcut icon" type="image/x-icon" href="<?= BASE_URL ?>/assets/img/favicon.ico">
<script>
(function(){
  var t = localStorage.getItem('rsgrup-theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
})();
</script>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/public.css">
<?php foreach ($extraCss as $cssUrl): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($cssUrl) ?>">
<?php endforeach; ?>
<style>
  :root {
    --color-brand: <?= htmlspecialchars($accentColor) ?>;
    --color-brand-hover: color-mix(in srgb, <?= htmlspecialchars($accentColor) ?> 80%, #000);
  }
  /* Enlace Soporte WhatsApp en el nav */
  .nav-wa-link {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    color: #25d366 !important;
    font-weight: 600;
    font-size: var(--text-sm);
    text-decoration: none !important;
    transition: opacity 150ms;
  }
  .nav-wa-link:hover { opacity: .8; }
  .nav-wa-link svg { flex-shrink: 0; }
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body>
<a href="#main-content" class="sr-only">Saltar al contenido</a>
<header class="site-header" role="banner">
  <div class="container header-inner">
    <a href="<?= BASE_URL ?>/dashboard" class="header-logo" aria-label="<?= APP_NAME ?> – Inicio">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>" width="160" height="80" loading="eager">
    </a>
    <?php if ($user): ?>
    <nav class="header-nav" aria-label="Navegación principal">
      <a href="<?= BASE_URL ?>/dashboard">Dashboard</a>
      <a href="<?= BASE_URL ?>/perfil">Mi perfil</a>
      <?php if ($waNavNumber): ?>
      <a href="https://wa.me/<?= $waNavNumber ?>" target="_blank" rel="noopener noreferrer" class="nav-wa-link" aria-label="Soporte por WhatsApp">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm.01 1.67c2.2 0 4.27.86 5.82 2.41a8.22 8.22 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.24 8.23-1.48 0-2.93-.4-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.32a8.19 8.19 0 0 1-1.26-4.37c.01-4.54 3.7-8.23 8.25-8.23zm-2.9 4.36c-.18 0-.46.07-.7.34-.24.27-.91.89-.91 2.17s.93 2.52 1.06 2.69c.13.18 1.83 2.79 4.43 3.91.62.27 1.1.43 1.48.55.62.2 1.19.17 1.63.1.5-.07 1.53-.63 1.75-1.23.22-.6.22-1.12.15-1.23-.06-.1-.24-.17-.5-.3-.27-.13-1.54-.76-1.78-.85-.24-.09-.41-.13-.58.13-.17.27-.65.85-.8 1.02-.14.18-.29.2-.54.07-.25-.14-1.05-.39-2-1.23-.74-.66-1.24-1.47-1.39-1.72-.14-.25-.01-.38.11-.51.11-.11.25-.29.38-.43.12-.14.16-.24.24-.41.08-.17.04-.31-.02-.44-.06-.13-.57-1.38-.79-1.89-.2-.48-.41-.42-.57-.43l-.48-.01z"/>
        </svg>
        Soporte
      </a>
      <?php endif; ?>
      <?php if ($isAdmin): ?>
        <a href="<?= BASE_URL ?>/admin" class="badge-admin">Admin</a>
      <?php endif; ?>
    </nav>
    <div class="header-actions">
      <?php if ($waNavNumber): ?>
      <a href="https://wa.me/<?= $waNavNumber ?>" target="_blank" rel="noopener noreferrer"
         class="nav-wa-link" aria-label="Soporte por WhatsApp"
         style="display:none" id="wa-mobile-btn">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm.01 1.67c2.2 0 4.27.86 5.82 2.41a8.22 8.22 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.24 8.23-1.48 0-2.93-.4-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.32a8.19 8.19 0 0 1-1.26-4.37c.01-4.54 3.7-8.23 8.25-8.23zm-2.9 4.36c-.18 0-.46.07-.7.34-.24.27-.91.89-.91 2.17s.93 2.52 1.06 2.69c.13.18 1.83 2.79 4.43 3.91.62.27 1.1.43 1.48.55.62.2 1.19.17 1.63.1.5-.07 1.53-.63 1.75-1.23.22-.6.22-1.12.15-1.23-.06-.1-.24-.17-.5-.3-.27-.13-1.54-.76-1.78-.85-.24-.09-.41-.13-.58.13-.17.27-.65.85-.8 1.02-.14.18-.29.2-.54.07-.25-.14-1.05-.39-2-1.23-.74-.66-1.24-1.47-1.39-1.72-.14-.25-.01-.38.11-.51.11-.11.25-.29.38-.43.12-.14.16-.24.24-.41.08-.17.04-.31-.02-.44-.06-.13-.57-1.38-.79-1.89-.2-.48-.41-.42-.57-.43l-.48-.01z"/>
        </svg>
      </a>
      <style>@media(max-width:640px){#wa-mobile-btn{display:inline-flex!important}}</style>
      <?php endif; ?>
      <button id="theme-toggle" data-theme-toggle aria-label="Cambiar tema" class="btn-icon">
        <svg id="icon-sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        <svg id="icon-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      </button>
      <?php
        $initials = strtoupper(
            mb_substr($user['name']     ?? '', 0, 1) .
            mb_substr($user['surnames'] ?? '', 0, 1)
        );
      ?>
      <?php if (!empty($user['avatar'])): ?>
        <img src="<?= BASE_URL . htmlspecialchars($user['avatar']) ?>" class="avatar-img avatar-sm" alt="Avatar">
      <?php else: ?>
        <div class="avatar-initials avatar-sm"><?= htmlspecialchars($initials) ?></div>
      <?php endif; ?>
      <form method="POST" action="<?= BASE_URL ?>/logout" style="display:inline">
        <?= \Csrf::field() ?>
        <button type="submit" class="btn btn-sm">Salir</button>
      </form>
    </div>
    <button class="nav-toggle" aria-label="Abrir menú" aria-expanded="false"
      onclick="this.setAttribute('aria-expanded',this.getAttribute('aria-expanded')==='true'?'false':'true');document.querySelector('.header-nav').classList.toggle('open')">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <?php endif; ?>
  </div>
</header>
<main id="main-content">
