<?php
// templates/admin/layout_admin.php
if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin(); requireAdmin();
$metaTitle = $metaTitle ?? 'Admin';
$robots    = $robots    ?? 'noindex,nofollow';
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Leer color de acento desde settings
$accentColor = '#e87722'; // naranja RSGrup por defecto
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
<title><?= htmlspecialchars($metaTitle) ?> | RSGrup Admin</title>
<script>
(function(){
  var t = localStorage.getItem('rsgrup-theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
})();
</script>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<!-- Acento de marca dinámico -->
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
<!-- TinyMCE CDN -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
window._tinymceConfig = {
  selector: '.wysiwyg',
  height: 300,
  menubar: false,
  plugins: 'lists link image code',
  toolbar: 'bold italic underline | bullist numlist | link | code',
  promotion: false
};
</script>
</head>
<body>
<div class="admin-layout">

  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <div class="sidebar-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="RSGrup" height="48">
    </div>
    <nav class="sidebar-nav">
      <a href="<?= BASE_URL ?>/admin" class="sidebar-link <?= $currentUri==='/admin'||$currentUri==='/admin/'?'active':'' ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>
      <a href="<?= BASE_URL ?>/admin/cursos" class="sidebar-link <?= str_starts_with($currentUri,'/admin/cursos')||str_starts_with($currentUri,BASE_PATH.'/admin/cursos')?'active':'' ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        Cursos
      </a>
      <a href="<?= BASE_URL ?>/admin/entregas" class="sidebar-link <?= str_starts_with($currentUri,'/admin/entregas')?'active':'' ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Entregas
      </a>
      <a href="<?= BASE_URL ?>/admin/examenes" class="sidebar-link <?= str_starts_with($currentUri,'/admin/examenes')?'active':'' ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Exámenes
      </a>
      <a href="<?= BASE_URL ?>/admin/usuarios" class="sidebar-link <?= str_starts_with($currentUri,'/admin/usuarios')?'active':'' ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Usuarios
      </a>
      <a href="<?= BASE_URL ?>/admin/actividad" class="sidebar-link <?= str_starts_with($currentUri,'/admin/actividad')?'active':'' ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Actividad
      </a>
      <a href="<?= BASE_URL ?>/admin/settings" class="sidebar-link <?= str_starts_with($currentUri,'/admin/settings')?'active':'' ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Ajustes
      </a>
    </nav>
    <div class="sidebar-footer">
      <form method="POST" action="<?= BASE_URL ?>/logout">
        <?= \Csrf::field() ?>
        <button type="submit" class="sidebar-link w-full" style="border:none;cursor:pointer;background:transparent;">
          <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Cerrar sesión
        </button>
      </form>
    </div>
  </aside>

  <!-- Main -->
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
          <svg id="icon-sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
          <svg id="icon-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
          <span id="theme-label" class="sr-only">Tema oscuro</span>
        </button>
        <span class="topbar-user"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
      </div>
    </header>
    <div class="admin-content">
