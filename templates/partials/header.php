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
// $extraCss puede definirse ANTES del include para inyectar hojas adicionales
$extraCss  = $extraCss  ?? [];

// Color de marca dinámico
$accentColor = '#e87722';
try {
    $accentRow = Database::fetchRow("SELECT `value` FROM rsgrup_settings WHERE `key`='brand_accent_color'");
    if ($accentRow && !empty($accentRow['value'])) $accentColor = $accentRow['value'];
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
      <?php if ($isAdmin): ?>
        <a href="<?= BASE_URL ?>/admin" class="badge-admin">Admin</a>
      <?php endif; ?>
    </nav>
    <div class="header-actions">
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
