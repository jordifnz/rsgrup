<?php
// templates/admin/layout_admin.php
if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin(); requireAdmin();
$metaTitle  = $metaTitle  ?? 'Admin';
$robots     = $robots     ?? 'noindex,nofollow';
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$accentColor = '#e87722';
try {
  $db = Database::getInstance();
  $accentRow = $db->fetchOne("SELECT setting_value FROM rsgrup_settings WHERE setting_key='brand_accent_color'");
  if ($accentRow && !empty($accentRow['setting_value'])) $accentColor = $accentRow['setting_value'];
} catch(\Throwable $e) {}
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
})();
</script>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
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
<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="sidebar-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="RSGrup" height="48">
    </div>
    <nav class="sidebar-nav">
      <?php
      $links = [
        ['href'=>'/admin',           'label'=>'Dashboard',  'icon'=>'layout-dashboard'],
        ['href'=>'/admin/cursos',    'label'=>'Cursos',     'icon'=>'book-open'],
        ['href'=>'/admin/entregas',  'label'=>'Entregas',   'icon'=>'file-text'],
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
        <?= $l['label'] ?>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <form method="POST" action="<?= BASE_URL ?>/logout">
        <?= \Csrf::field() ?>
        <button type="submit" class="sidebar-link w-full" style="border:none;cursor:pointer;background:transparent;">
          <i data-lucide="log-out" class="sidebar-icon"></i>
          Cerrar sesión
        </button>
      </form>
    </div>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar">
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
