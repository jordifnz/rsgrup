<?php
// templates/admin/layout_admin.php
if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin(); requireAdmin();
$metaTitle  = $metaTitle  ?? 'Admin';
$robots     = $robots     ?? 'noindex,nofollow';
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Usar Database::getSetting() que ya tiene try/catch interno
$accentColor = Database::getSetting('brand_accent_color', '#e87722');
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="<?= $robots ?>">
<title><?= htmlspecialchars($metaTitle) ?> | RSGrup Admin</title>
<script>
(function(){
  var t = '';
  try { t = localStorage.getItem('rsgrup-theme') || ''; } catch(e) {}
  document.documentElement.setAttribute('data-theme', t || 'dark');
  if (window.innerWidth >= 768) {
    var collapsed = false;
    try { collapsed = localStorage.getItem('rsgrup-sidebar-collapsed') === '1'; } catch(e) {}
    if (collapsed) document.documentElement.classList.add('sidebar-collapsed');
  }
})();
</script>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<style>
  :root {
    --color-brand: <?= htmlspecialchars($accentColor) ?>;
    --color-brand-hover: color-mix(in srgb, <?= htmlspecialchars($accentColor) ?> 80%, #000);
    --sidebar-w:           220px;
    --sidebar-w-collapsed: 56px;
  }

  .admin-layout {
    display: flex;
    min-height: 100dvh;
  }

  .admin-sidebar {
    width: var(--sidebar-w);
    flex-shrink: 0;
    transition: width 0.22s ease;
    overflow: hidden;
    position: relative;
    z-index: 200;
  }
  .sidebar-collapsed .admin-sidebar {
    width: var(--sidebar-w-collapsed);
  }

  @media (min-width: 768px) {
    .sidebar-collapsed .sidebar-link > span,
    .sidebar-collapsed .sidebar-footer .sidebar-link > span {
      display: none;
    }
    .sidebar-collapsed .sidebar-link {
      justify-content: center;
      padding-inline: 0;
    }
    .sidebar-collapsed .sidebar-icon {
      margin-inline: auto;
    }
    .sidebar-collapsed .sidebar-logo {
      justify-content: center;
      padding-inline: 0;
    }
    .sidebar-collapsed .sidebar-logo img {
      max-width: 36px;
    }
  }

  .sidebar-toggle {
    display: none;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    border: 1px solid var(--color-border, #333);
    background: var(--color-surface, #1c1b19);
    color: var(--color-text-muted, #aaa);
    cursor: pointer;
    position: absolute;
    top: 14px;
    right: -13px;
    z-index: 210;
    transition: background 0.18s, color 0.18s;
    flex-shrink: 0;
  }
  .sidebar-toggle:hover {
    background: var(--color-brand);
    color: #fff;
    border-color: var(--color-brand);
  }
  .sidebar-toggle svg { transition: transform 0.22s ease; }
  .sidebar-collapsed .sidebar-toggle svg { transform: rotate(180deg); }

  @media (min-width: 768px) {
    .sidebar-toggle { display: flex; }
  }

  @media (max-width: 767px) {
    .admin-sidebar {
      position: fixed !important;
      top: 0; left: 0;
      height: 100dvh;
      width: var(--sidebar-w) !important;
      transform: translateX(-100%);
      transition: transform 0.25s ease !important;
      z-index: 300;
      box-shadow: 4px 0 20px rgba(0,0,0,0.45);
      overflow-y: auto;
    }
    .admin-sidebar.mobile-open {
      transform: translateX(0) !important;
    }
    .admin-sidebar .sidebar-link > span {
      display: inline !important;
    }
    .admin-sidebar .sidebar-link {
      justify-content: flex-start !important;
      padding-inline: revert !important;
    }
    .admin-sidebar .sidebar-logo img {
      max-width: none !important;
    }
  }

  .sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    z-index: 299;
  }
  .sidebar-overlay.visible { display: block; }

  .admin-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    max-width: 100%;
  }

  .admin-topbar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
  }

  .btn-hamburger {
    display: none;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border: none;
    background: transparent;
    color: var(--color-text-muted, #aaa);
    cursor: pointer;
    border-radius: 6px;
    flex-shrink: 0;
  }
  .btn-hamburger:hover { background: var(--color-surface-offset, #2a2928); }
  @media (max-width: 767px) {
    .btn-hamburger { display: flex; }
  }

  .admin-content {
    flex: 1;
    overflow-y: auto;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .admin-content table {
    min-width: 480px;
  }
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="admin-layout">
  <aside class="admin-sidebar" id="admin-sidebar">
    <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Colapsar menú">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </button>

    <div class="sidebar-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="RSGrup" height="48">
    </div>
    <nav class="sidebar-nav">
      <?php
      $links = [
        ['href'=>'/admin',           'label'=>'Dashboard',  'icon'=>'layout-dashboard'],
        ['href'=>'/admin/cursos',    'label'=>'Cursos',     'icon'=>'book-open'],
        ['href'=>'/admin/entregas',  'label'=>'Entregas',   'icon'=>'file-text'],
        ['href'=>'/admin/temas',     'label'=>'Temas',      'icon'=>'layers'],
        ['href'=>'/admin/examenes',  'label'=>'Exámenes',   'icon'=>'check-square'],
        ['href'=>'/admin/usuarios',  'label'=>'Usuarios',   'icon'=>'users'],
        ['href'=>'/admin/actividad', 'label'=>'Actividad',  'icon'=>'activity'],
        ['href'=>'/admin/settings',  'label'=>'Ajustes',    'icon'=>'settings'],
      ];
      foreach ($links as $l):
        $active = ($currentUri === $l['href'] || str_starts_with($currentUri, $l['href'].'/')) ? 'active' : '';
      ?>
      <a href="<?= BASE_URL . $l['href'] ?>" class="sidebar-link <?= $active ?>">
        <i data-lucide="<?= $l['icon'] ?>" class="sidebar-icon"></i>
        <span><?= $l['label'] ?></span>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <form method="POST" action="<?= BASE_URL ?>/logout">
        <?= \Csrf::field() ?>
        <button type="submit" class="sidebar-link w-full" style="border:none;cursor:pointer;background:transparent;">
          <i data-lucide="log-out" class="sidebar-icon"></i>
          <span>Cerrar sesión</span>
        </button>
      </form>
    </div>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar">
      <button class="btn-hamburger" id="btn-hamburger" aria-label="Abrir menú">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>

      <?php if ($currentUri !== '/admin' && $currentUri !== '/admin/'): ?>
        <a href="javascript:history.back()" class="btn-back">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          Volver
        </a>
      <?php endif; ?>
      <span class="topbar-title"><?= htmlspecialchars($metaTitle) ?></span>
      <div class="topbar-actions">
        <button id="theme-toggle" aria-label="Cambiar tema" class="btn-icon">
          <svg id="icon-sun"  width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
          <svg id="icon-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
          <span id="theme-label" class="sr-only">Tema oscuro</span>
        </button>
        <span class="topbar-user"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
      </div>
    </header>
    <div class="admin-content">
