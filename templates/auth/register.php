<?php
$metaTitle = 'Crear cuenta';
$robots    = 'noindex,nofollow';
include BASE_PATH . '/templates/layouts/base.php';
?>
<section class="auth-section">
  <div class="auth-card auth-card--wide">
    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>" width="250" height="125" loading="eager">
    </div>
    <h1 class="auth-title">Crear cuenta</h1>
    <?php if ($flash = getFlash()): ?>
      <div class="flash flash--<?= $flash['type'] ?>" role="alert"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
    <form action="<?= BASE_URL ?>/registro" method="POST" class="form" novalidate>
      <?= Csrf::field() ?>
      <?php if (!empty($redirect)): ?>
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
      <?php endif; ?>
      <div class="form-grid">
        <div class="form-group">
          <label for="name">Nombre *</label>
          <input type="text" id="name" name="name" required autocomplete="given-name"
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="surnames">Apellidos *</label>
          <input type="text" id="surnames" name="surnames" required autocomplete="family-name"
                 value="<?= htmlspecialchars($_POST['surnames'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="reg-email">Email *</label>
          <input type="email" id="reg-email" name="email" required autocomplete="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="phone">Teléfono</label>
          <input type="tel" id="phone" name="phone" autocomplete="tel"
                 value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="address">Dirección</label>
          <input type="text" id="address" name="address" autocomplete="street-address"
                 value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="postal_code">Código postal</label>
          <input type="text" id="postal_code" name="postal_code" autocomplete="postal-code"
                 value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="city">Población</label>
          <input type="text" id="city" name="city" autocomplete="address-level2"
                 value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="province">Provincia</label>
          <input type="text" id="province" name="province"
                 value="<?= htmlspecialchars($_POST['province'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="instagram">Instagram</label>
          <input type="text" id="instagram" name="instagram" placeholder="@usuario"
                 value="<?= htmlspecialchars($_POST['instagram'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="tiktok">TikTok</label>
          <input type="text" id="tiktok" name="tiktok" placeholder="@usuario"
                 value="<?= htmlspecialchars($_POST['tiktok'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="reg-password">Contraseña *</label>
          <div class="input-with-toggle">
            <input type="password" id="reg-password" name="password" required
                   autocomplete="new-password" minlength="8">
            <button type="button" class="btn-icon" onclick="togglePassword('reg-password')" aria-label="Mostrar/ocultar">
              <i data-lucide="eye"></i>
            </button>
          </div>
        </div>
      </div>
      <!-- Honeypot anti-bot -->
      <div style="display:none" aria-hidden="true">
        <label>No rellenar <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      </div>
      <button type="submit" class="btn btn-primary btn--full" style="margin-top:var(--space-4)">Crear cuenta</button>
    </form>
    <p class="auth-footer">¿Ya tienes cuenta? <a href="<?= BASE_URL ?>/login">Acceder</a></p>
  </div>
</section>
<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>
