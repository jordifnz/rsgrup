<?php
// templates/admin/layout_admin.php
// Include ANTES del contenido de cada pantalla admin
if (!defined('BASE_PATH')) { header('Location: /'); exit; }
requireAdmin();
$robots = 'noindex,nofollow';
$metaTitle = ($metaTitle ?? 'Admin') . ' — RSGrup Admin';
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($metaTitle) ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700&f[]=cabinet-grotesk@700,800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
  <!-- Sidebar -->
  <aside class="admin-sidebar" aria-label="Navegación admin">
    <div class="sidebar-logo">
      <a href="<?= BASE_URL ?>/admin">
        <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="RSGrup" width="140" height="70" loading="eager">
      </a>
    </div>
    <nav class="sidebar-nav">
      <a href="<?= BASE_URL ?>/admin" class="sidebar-link <?= ($_SERVER['REQUEST_URI'] === BASE_URL.'/admin') ? 'active' : '' ?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard
      </a>
      <a href="<?= BASE_URL ?>/admin/cursos" class="sidebar-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg> Cursos</a>
      <a href="<?= BASE_URL ?>/admin/entregas" class="sidebar-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Entregas</a>
      <a href="<?= BASE_URL ?>/admin/examenes" class="sidebar-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg> Exámenes</a>
      <a href="<?= BASE_URL ?>/admin/usuarios" class="sidebar-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg> Usuarios</a>
      <a href="<?= BASE_URL ?>/admin/actividad" class="sidebar-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Actividad</a>
      <a href="<?= BASE_URL ?>/admin/settings" class="sidebar-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg> Settings</a>
    </nav>
    <div class="sidebar-footer">
      <form action="<?= BASE_URL ?>/logout" method="POST">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-ghost btn-sm btn-full">Salir</button>
      </form>
      <button data-theme-toggle class="btn btn-ghost btn-icon" aria-label="Cambiar tema">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
      </button>
    </div>
  </aside>
  <!-- Main -->
  <div class="admin-main">
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