<?php include BASE_PATH . '/templates/partials/header.php'; ?>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="RSGrup" style="max-width:220px;margin:0 auto;display:block">
    </div>
    <h1 class="auth-title">Acceder</h1>

    <?php if(!empty($_SESSION['flash_error'])): ?>
      <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/login" class="auth-form">
      <?= \Csrf::field() ?>
      <?php if(!empty($_GET['redirect'])): ?>
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect']) ?>">
      <?php endif; ?>

      <div class="form-group">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" required autocomplete="username"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="password">Contraseña</label>
        <div class="password-field">
          <input id="password" type="password" name="password" required autocomplete="current-password">
          <button type="button" class="toggle-password" data-target="#password" aria-label="Mostrar contraseña">
            <!-- Ojo abierto -->
            <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
            <!-- Ojo cerrado -->
            <svg class="eye-close" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
              <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Entrar</button>
      <p class="auth-footer">¿No tienes cuenta? <a href="<?= BASE_URL ?>/registro">Regístrate</a></p>
    </form>
  </div>
</div>

<?php include BASE_PATH . '/templates/partials/footer.php'; ?>
