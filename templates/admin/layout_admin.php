<?php
// templates/admin/layout_admin.php
if (!defined('BASE_PATH')) { header('Location: /'); exit; }
requireAdmin();
$robots    = 'noindex,nofollow';
$metaTitle = ($metaTitle ?? 'Admin') . ' — RSGrup Admin';

// Leer nombre del usuario admin para el topbar
$adminName = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_surnames'] ?? ''));
$adminInitials = strtoupper(
    mb_substr($_SESSION['user_name']     ?? '', 0, 1) .
    mb_substr($_SESSION['user_surnames'] ?? '', 0, 1)
);
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$baseAdmin  = rtrim(BASE_URL, '/') . '/admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($metaTitle) ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700&f[]=cabinet-grotesk@700,800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <script>
    // Tema: leer preferencia guardada o usar sistema
    (function(){
      var saved = localStorage.getItem('rsgrup-theme');
      var preferred = saved || (matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light');
      document.documentElement.setAttribute('data-theme', preferred);
    })();
  </script>
</head>
<body class="admin-body">
<div class="admin-layout">

  <!-- ===== SIDEBAR ===== -->
  <aside class="admin-sidebar" aria-label="Navegación admin">

    <div class="sidebar-logo">
      <a href="<?= BASE_URL ?>/admin">
        <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="RSGrup" width="140" height="70" loading="eager">
      </a>
    </div>

    <nav class="sidebar-nav" aria-label="Menú admin">

      <?php
      $navItems = [
        ['href' => '/admin',            'label' => 'Dashboard', 'icon' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
        ['href' => '/admin/cursos',     'label' => 'Cursos',    'icon' => '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>'],
        ['href' => '/admin/entregas',   'label' => 'Entregas',  'icon' => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>'],
        ['href' => '/admin/examenes',   'label' => 'Exámenes',  'icon' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>'],
        ['href' => '/admin/usuarios',   'label' => 'Usuarios',  'icon' => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>'],
        ['href' => '/admin/actividad',  'label' => 'Actividad', 'icon' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>'],
        ['href' => '/admin/settings',   'label' => 'Settings',  'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>'],
      ];
      foreach ($navItems as $item):
        $fullHref = BASE_URL . $item['href'];
        // Marcar activo: exact match para /admin, prefix para el resto
        if ($item['href'] === '/admin') {
            $isActive = ($currentUri === parse_url($fullHref, PHP_URL_PATH));
        } else {
            $isActive = str_starts_with($currentUri, parse_url($fullHref, PHP_URL_PATH));
        }
      ?>
      <a href="<?= $fullHref ?>" class="sidebar-link <?= $isActive ? 'active' : '' ?>" aria-current="<?= $isActive ? 'page' : 'false' ?>">
        <svg class="sidebar-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $item['icon'] ?></svg>
        <span><?= $item['label'] ?></span>
      </a>
      <?php endforeach; ?>

    </nav>

    <div class="sidebar-footer">
      <!-- Toggle tema -->
      <button id="theme-toggle" class="sidebar-theme-btn" aria-label="Cambiar tema claro/oscuro">
        <svg id="icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
        <svg id="icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <span id="theme-label">Tema oscuro</span>
      </button>
      <!-- Salir -->
      <form action="<?= BASE_URL ?>/logout" method="POST" style="margin:0">
        <?= Csrf::field() ?>
        <button type="submit" class="sidebar-logout-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          <span>Salir</span>
        </button>
      </form>
    </div>

  </aside>

  <!-- ===== MAIN ===== -->
  <div class="admin-main">

    <!-- Topbar -->
    <header class="admin-topbar">
      <div class="admin-topbar__left">
        <?php if ($currentUri !== parse_url(BASE_URL . '/admin', PHP_URL_PATH)): ?>
        <a href="javascript:history.back()" class="btn-back" aria-label="Volver">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
          Volver
        </a>
        <?php endif; ?>
        <h1 class="admin-topbar__title"><?= htmlspecialchars($metaTitle) ?></h1>
      </div>
      <div class="admin-topbar__right">
        <?php if (!empty($_SESSION['user_avatar'])): ?>
          <img src="<?= BASE_URL . htmlspecialchars($_SESSION['user_avatar']) ?>" class="avatar-img avatar-sm" alt="Avatar">
        <?php else: ?>
          <div class="avatar-initials avatar-sm"><?= htmlspecialchars($adminInitials) ?></div>
        <?php endif; ?>
        <span class="admin-topbar__name"><?= htmlspecialchars($adminName) ?></span>
      </div>
    </header>

    <?php
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    if ($flash): ?>
    <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>" role="alert">
      <?= htmlspecialchars($flash['message']) ?>
      <button onclick="this.parentElement.remove()" class="flash-close" aria-label="Cerrar">&times;</button>
    </div>
    <?php endif; ?>

    <div class="admin-content">
