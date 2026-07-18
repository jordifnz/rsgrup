<?php
$metaTitle = 'Acceder';
$robots    = 'noindex,nofollow';
include BASE_PATH . '/templates/layouts/base.php';
?>
<section class="auth-section">
  <div class="auth-card">
    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>" width="250" height="125" loading="eager">
    </div>
    <h1 class="auth-title">Acceder</h1>
    <?php if ($flash = getFlash()): ?>
      <div class="flash flash--<?= $flash['type'] ?>" role="alert"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
    <form action="<?= BASE_URL ?>/login" method="POST" class="form" novalidate>
      <?= Csrf::field() ?>
      <?php if (!empty($redirect)): ?>
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
      <?php endif; ?>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" autocomplete="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required
               placeholder="tu@email.com">
      </div>
      <div class="form-group">
        <label for="password">Contraseña</label>
        <div class="input-with-toggle">
          <input type="password" id="password" name="password" autocomplete="current-password" required>
          <button type="button" class="btn-icon" onclick="togglePassword('password')" aria-label="Mostrar/ocultar contraseña">
            <i data-lucide="eye"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn--full">Entrar</button>
    </form>
    <p class="auth-footer">¿No tienes cuenta? <a href="<?= BASE_URL ?>/registro">Regístrate</a></p>
  </div>
</section>
<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>
