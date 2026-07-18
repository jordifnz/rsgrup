<?php
// templates/partials/header.php
$currentUser = $_SESSION['user'] ?? null;
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
?>
<header class="site-header" role="banner">
  <div class="header-inner">
    <a href="<?= BASE_URL ?>/dashboard" class="header-logo" aria-label="RSGrup inicio">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="RSGrup" width="250" height="125" loading="eager">
    </a>
    <nav class="header-nav" aria-label="Navegación principal">
      <?php if ($currentUser): ?>
        <a href="<?= BASE_URL ?>/dashboard" class="nav-link">Dashboard</a>
        <?php if ($isAdmin): ?>
          <a href="<?= BASE_URL ?>/admin" class="nav-link">Admin</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/perfil" class="nav-link">Mi perfil</a>
        <form action="<?= BASE_URL ?>/logout" method="POST" class="nav-form">
          <?= Csrf::field() ?>
          <button type="submit" class="btn btn-ghost btn-sm">Salir</button>
        </form>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/login" class="btn btn-primary btn-sm">Acceder</a>
      <?php endif; ?>
      <button data-theme-toggle aria-label="Cambiar tema" class="btn btn-ghost btn-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
      </button>
    </nav>
    <button class="nav-hamburger" aria-label="Menú" aria-expanded="false" onclick="toggleMobileMenu()">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
  </div>
  <!-- Flash messages -->
  <?php
  $flash = $_SESSION['flash'] ?? null;
  unset($_SESSION['flash']);
  if ($flash): ?>
  <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>" role="alert">
    <?= htmlspecialchars($flash['message']) ?>
    <button onclick="this.parentElement.remove()" class="flash-close" aria-label="Cerrar">&times;</button>
  </div>
  <?php endif; ?>
</header>
<!-- Mobile nav drawer -->
<div id="mobile-nav" class="mobile-nav" hidden>
  <?php if ($currentUser): ?>
    <a href="<?= BASE_URL ?>/dashboard">Dashboard</a>
    <?php if ($isAdmin): ?><a href="<?= BASE_URL ?>/admin">Admin</a><?php endif; ?>
    <a href="<?= BASE_URL ?>/perfil">Mi perfil</a>
    <form action="<?= BASE_URL ?>/logout" method="POST"><?= Csrf::field() ?><button type="submit">Salir</button></form>
  <?php else: ?>
    <a href="<?= BASE_URL ?>/login">Acceder</a>
    <a href="<?= BASE_URL ?>/registro">Registrarse</a>
  <?php endif; ?>
</div>