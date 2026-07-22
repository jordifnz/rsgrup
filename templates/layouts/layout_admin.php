<?php
// Admin layout - open. Requires admin role.
requireAdmin();
$metaTitle = $metaTitle ?? 'Admin – ' . APP_NAME;
$robots    = 'noindex,nofollow';
$user      = $_SESSION['user'];
$baseUrl   = BASE_URL;
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title><?= htmlspecialchars($metaTitle) ?> – <?= APP_NAME ?> Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <style>
  /*
   * CRITICAL: El body.admin-layout usa display:flex.
   * En Safari iOS, un ancestro con display:flex/grid puede actuar como
   * containing block para position:fixed, rompiendo el boton flotante.
   * Solucion: .wa-fab usa transform:translateZ(0) para crear su propio
   * stacking/compositing context y escapar del containing block del flex.
   */
  .wa-fab {
    position: fixed !important;
    bottom: 1.5rem !important;
    right: 1.5rem !important;
    z-index: 9999 !important;
    /* transform fuerza compositing layer propio — escapa del flex body en iOS Safari */
    transform: translateZ(0);
    -webkit-transform: translateZ(0);
    will-change: transform;
  }
  @media (max-width: 640px) {
    .wa-fab {
      bottom: 1.25rem !important;
      right: 1rem !important;
    }
  }
  </style>
</head>
<body class="admin-layout">
  <a class="sr-only" href="#admin-main">Saltar al contenido</a>

  <!-- Sidebar -->
  <aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>" width="160" height="80" loading="lazy">
    </div>
    <nav class="sidebar-nav" aria-label="Navegación admin">
      <a href="<?= BASE_URL ?>/admin" class="sidebar-link <?= str_ends_with($_SERVER['REQUEST_URI'],'/admin') ? 'active' : '' ?>">
        <i data-lucide="layout-dashboard"></i> <span>Dashboard</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/cursos" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/cursos') ? 'active' : '' ?>">
        <i data-lucide="book-open"></i> <span>Cursos</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/entregas" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/entregas') ? 'active' : '' ?>">
        <i data-lucide="layers"></i> <span>Entregas</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/examenes" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/examenes') ? 'active' : '' ?>">
        <i data-lucide="file-check"></i> <span>Exámenes</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/usuarios" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/usuarios') ? 'active' : '' ?>">
        <i data-lucide="users"></i> <span>Usuarios</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/actividad" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/actividad') ? 'active' : '' ?>">
        <i data-lucide="activity"></i> <span>Actividad</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/settings" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/settings') ? 'active' : '' ?>">
        <i data-lucide="settings"></i> <span>Settings</span>
      </a>
    </nav>
    <div class="sidebar-footer">
      <a href="<?= BASE_URL ?>/dashboard" class="sidebar-link"><i data-lucide="arrow-left"></i> <span>Ver sitio</span></a>
      <a href="<?= BASE_URL ?>/logout" class="sidebar-link text-error"><i data-lucide="log-out"></i> <span>Salir</span></a>
    </div>
  </aside>

  <!-- Main wrapper -->
  <div class="admin-wrapper">
    <header class="admin-topbar">
      <button class="sidebar-toggle" aria-label="Abrir menú" onclick="document.getElementById('admin-sidebar').classList.toggle('open')">
        <i data-lucide="menu"></i>
      </button>
      <span class="topbar-title"><?= htmlspecialchars($metaTitle) ?></span>
      <div class="topbar-user">
        <?php if ($user['avatar']): ?>
          <img src="<?= BASE_URL . htmlspecialchars($user['avatar']) ?>" class="avatar-img avatar-sm" alt="Avatar">
        <?php else: ?>
          <div class="avatar-initials avatar-sm"><?= htmlspecialchars(substr($user['name'],0,1) . substr($user['surnames'],0,1)) ?></div>
        <?php endif; ?>
        <span><?= htmlspecialchars($user['name']) ?></span>
        <button data-theme-toggle aria-label="Cambiar tema"><i data-lucide="sun"></i></button>
      </div>
    </header>
    <?php if ($flash = getFlash()): ?>
    <div class="flash flash--<?= $flash['type'] ?>" role="alert"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
    <main id="admin-main" class="admin-main">
