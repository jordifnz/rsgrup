<?php
// Construir $user desde las SESSION keys sueltas que guarda loginUser()
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
$isAdmin = ($user['role'] ?? '') === ROLE_ADMIN;
?>
<header class="site-header" role="banner">
  <div class="container header-inner">
    <a href="<?= BASE_URL ?>/dashboard" class="header-logo" aria-label="<?= APP_NAME ?> – Inicio">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>" width="250" height="125" loading="eager">
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
      <button data-theme-toggle aria-label="Cambiar tema" class="btn-icon">
        <i data-lucide="sun"></i>
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
      <a href="<?= BASE_URL ?>/logout" class="btn btn-sm">Salir</a>
    </div>
    <button class="nav-toggle" aria-label="Abrir menú" aria-expanded="false"
      onclick="this.setAttribute('aria-expanded',this.getAttribute('aria-expanded')==='true'?'false':'true');document.querySelector('.header-nav').classList.toggle('open')">
      <i data-lucide="menu"></i>
    </button>
    <?php endif; ?>
  </div>
</header>
