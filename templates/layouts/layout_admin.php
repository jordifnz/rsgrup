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
  <style>
    .admin-sidebar { width:220px; transition:width 200ms ease; }
    .admin-sidebar.collapsed { width:54px; }
    .sidebar-link svg, .sidebar-link i { width:18px;height:18px;min-width:18px;flex-shrink:0; }
    .admin-sidebar.collapsed .sidebar-link span { opacity:0;width:0;overflow:hidden;display:inline-block; }
    .admin-sidebar:not(.collapsed) .sidebar-link span { opacity:1;width:auto; }
    .admin-sidebar.collapsed .sidebar-link { justify-content:center;padding:.625rem;gap:0; }
    .admin-sidebar.collapsed .sidebar-logo img { opacity:0;pointer-events:none; }
    .admin-sidebar.collapsed .sidebar-link { position:relative; }
    .admin-sidebar.collapsed .sidebar-link:hover::after {
      content:attr(data-tooltip);position:absolute;left:calc(100% + 8px);top:50%;
      transform:translateY(-50%);background:rgba(0,0,0,.85);color:#fff;
      font-size:.7rem;font-weight:500;padding:.25rem .6rem;border-radius:.35rem;
      white-space:nowrap;pointer-events:none;z-index:1000;
    }
    .sidebar-collapse-btn {
      display:flex;align-items:center;justify-content:center;width:100%;padding:.5rem;
      background:none;border:none;border-top:1px solid rgba(255,255,255,.06);
      color:var(--color-sidebar-text,#cdccca);cursor:pointer;transition:background 150ms;
    }
    .sidebar-collapse-btn:hover { background:rgba(255,255,255,.06); }
    @media(max-width:768px){
      .admin-sidebar{width:54px;}
      .admin-sidebar .sidebar-link span{opacity:0;width:0;overflow:hidden;display:inline-block;}
      .admin-sidebar .sidebar-link{justify-content:center;padding:.625rem;gap:0;}
      .admin-sidebar .sidebar-logo img{opacity:0;pointer-events:none;}
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
      <a href="<?= BASE_URL ?>/admin"
         class="sidebar-link <?= str_ends_with($_SERVER['REQUEST_URI'],'/admin') ? 'active' : '' ?>"
         data-tooltip="Dashboard">
        <i data-lucide="layout-dashboard"></i> <span>Dashboard</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/cursos"
         class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/cursos') ? 'active' : '' ?>"
         data-tooltip="Cursos">
        <i data-lucide="book-open"></i> <span>Cursos</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/entregas"
         class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/entregas') ? 'active' : '' ?>"
         data-tooltip="Entregas">
        <i data-lucide="layers"></i> <span>Entregas</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/examenes"
         class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/examenes') ? 'active' : '' ?>"
         data-tooltip="Exámenes">
        <i data-lucide="file-check"></i> <span>Exámenes</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/usuarios"
         class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/usuarios') ? 'active' : '' ?>"
         data-tooltip="Usuarios">
        <i data-lucide="users"></i> <span>Usuarios</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/actividad"
         class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/actividad') ? 'active' : '' ?>"
         data-tooltip="Actividad">
        <i data-lucide="activity"></i> <span>Actividad</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/settings"
         class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/settings') ? 'active' : '' ?>"
         data-tooltip="Ajustes">
        <i data-lucide="settings"></i> <span>Ajustes</span>
      </a>
    </nav>
    <div class="sidebar-footer">
      <a href="<?= BASE_URL ?>/dashboard" class="sidebar-link" data-tooltip="Ver sitio">
        <i data-lucide="arrow-left"></i> <span>Ver sitio</span>
      </a>
      <a href="<?= BASE_URL ?>/logout" class="sidebar-link" data-tooltip="Salir">
        <i data-lucide="log-out"></i> <span>Salir</span>
      </a>
      <button id="sidebar-collapse-btn" class="sidebar-collapse-btn"
              aria-label="Colapsar menú" onclick="toggleSidebar()">
        <i data-lucide="chevrons-left"></i>
      </button>
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

  <!-- Lucide al final del <head> → sin defer, se ejecuta bloqueante pero el DOM ya existe -->
  <script src="https://unpkg.com/lucide@0.441.0/dist/umd/lucide.min.js"></script>
  <script>
    lucide.createIcons();
    function toggleSidebar(){
      var sb=document.getElementById('admin-sidebar');
      if(!sb)return;
      sb.classList.toggle('collapsed');
    }
  </script>
