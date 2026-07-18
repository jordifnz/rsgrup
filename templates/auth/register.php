<?php
$metaTitle = 'Registro';
$robots = 'noindex,nofollow';
include BASE_PATH . '/templates/layouts/base.php';
?>
<section class="auth-section">
  <div class="auth-card auth-card--wide">
    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="RSGrup" width="200" height="100" loading="eager">
    </div>
    <h1 class="auth-title">Crear cuenta</h1>
    <form action="<?= BASE_URL ?>/registro" method="POST" enctype="multipart/form-data" class="form" novalidate>
      <?= Csrf::field() ?>
      <?php if (!empty($redirect)): ?>
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
      <?php endif; ?>
      <div class="form-grid">
        <div class="form-group">
          <label for="reg-name">Nombre *</label>
          <input type="text" id="reg-name" name="name" required value="<?= htmlspecialchars($old['name'] ?? '') ?>" autocomplete="given-name">
        </div>
        <div class="form-group">
          <label for="reg-surnames">Apellidos *</label>
          <input type="text" id="reg-surnames" name="surnames" required value="<?= htmlspecialchars($old['surnames'] ?? '') ?>" autocomplete="family-name">
        </div>
        <div class="form-group form-group--full">
          <label for="reg-email">Correo electrónico *</label>
          <input type="email" id="reg-email" name="email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>" autocomplete="email">
        </div>
        <div class="form-group">
          <label for="reg-phone">Teléfono</label>
          <input type="tel" id="reg-phone" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '') ?>" autocomplete="tel">
        </div>
        <div class="form-group">
          <label for="reg-address">Dirección</label>
          <input type="text" id="reg-address" name="address" value="<?= htmlspecialchars($old['address'] ?? '') ?>" autocomplete="street-address">
        </div>
        <div class="form-group">
          <label for="reg-postal">Código postal</label>
          <input type="text" id="reg-postal" name="postal_code" value="<?= htmlspecialchars($old['postal_code'] ?? '') ?>" autocomplete="postal-code">
        </div>
        <div class="form-group">
          <label for="reg-city">Población</label>
          <input type="text" id="reg-city" name="city" value="<?= htmlspecialchars($old['city'] ?? '') ?>" autocomplete="address-level2">
        </div>
        <div class="form-group">
          <label for="reg-province">Provincia</label>
          <input type="text" id="reg-province" name="province" value="<?= htmlspecialchars($old['province'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="reg-instagram">Instagram</label>
          <input type="text" id="reg-instagram" name="instagram" value="<?= htmlspecialchars($old['instagram'] ?? '') ?>" placeholder="@usuario">
        </div>
        <div class="form-group">
          <label for="reg-tiktok">TikTok</label>
          <input type="text" id="reg-tiktok" name="tiktok" value="<?= htmlspecialchars($old['tiktok'] ?? '') ?>" placeholder="@usuario">
        </div>
        <div class="form-group form-group--full">
          <label for="reg-password">Contraseña * <span class="text-muted text-sm">(mínimo 8 caracteres)</span></label>
          <input type="password" id="reg-password" name="password" required minlength="8" autocomplete="new-password">
        </div>
        <div class="form-group form-group--full">
          <label for="reg-password2">Repetir contraseña *</label>
          <input type="password" id="reg-password2" name="password_confirm" required minlength="8" autocomplete="new-password">
        </div>
      </div>
      <!-- Honeypot anti-bot -->
      <input type="text" name="website" class="sr-only" tabindex="-1" autocomplete="off">
      <!-- Captcha simple matemático -->
      <div class="form-group captcha-group">
        <label for="captcha"><?= htmlspecialchars($captchaQ ?? '?') ?> = <span class="text-muted text-sm">(anti-bots)</span></label>
        <input type="number" id="captcha" name="captcha" required style="max-width:100px">
        <input type="hidden" name="captcha_hash" value="<?= htmlspecialchars($captchaHash ?? '') ?>">
      </div>
      <button type="submit" class="btn btn-primary btn-full">Crear cuenta</button>
    </form>
    <p class="auth-footer-text">¿Ya tienes cuenta? <a href="<?= BASE_URL ?>/login">Acceder</a></p>
  </div>
</section>
<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>