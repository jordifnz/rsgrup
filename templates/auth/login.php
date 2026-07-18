<?php
$metaTitle = 'Acceder';
$robots = 'noindex,nofollow';
include BASE_PATH . '/templates/layouts/base.php';
?>
<section class="auth-section">
  <div class="auth-card">
    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="RSGrup" width="200" height="100" loading="eager">
    </div>
    <h1 class="auth-title">Accede a tu cuenta</h1>
    <form action="<?= BASE_URL ?>/login" method="POST" class="form" novalidate>
      <?= Csrf::field() ?>
      <?php if (!empty($redirect)): ?>
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
      <?php endif; ?>
      <div class="form-group">
        <label for="email">Correo electrónico</label>
        <input
          type="email"
          id="email"
          name="email"
          value="<?= htmlspecialchars($email ?? '') ?>"
          required
          autocomplete="email"
          autofocus
          placeholder="tu@email.com"
        >
      </div>
      <div class="form-group">
        <label for="password">Contraseña</label>
        <div class="input-wrap">
          <input
            type="password"
            id="password"
            name="password"
            required
            autocomplete="current-password"
            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
          >
          <button type="button" class="input-eye" onclick="togglePass('password')" aria-label="Mostrar/ocultar contraseña">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <!-- Honeypot anti-bot -->
      <input type="text" name="website" class="sr-only" tabindex="-1" autocomplete="off">
      <button type="submit" class="btn btn-primary btn-full">Entrar</button>
    </form>
    <p class="auth-footer-text">¿No tienes cuenta? <a href="<?= BASE_URL ?>/registro">Regístrate</a></p>
  </div>
</section>
<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>