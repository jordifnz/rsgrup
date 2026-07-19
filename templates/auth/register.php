<?php
$metaTitle = 'Registro';
$robots    = 'noindex,nofollow';
include BASE_PATH . '/templates/partials/header.php';
?>
<div class="auth-wrap" style="align-items:flex-start;padding-top:var(--space-8)">
  <div class="register-card" style="max-width:680px;width:100%">
    <div class="auth-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="RSGrup" loading="eager" style="max-width:180px;margin:0 auto">
    </div>
    <h1 class="auth-title">Crear cuenta</h1>

    <?php if(!empty($_SESSION['flash_error'])): ?>
      <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/registro" class="auth-form" enctype="multipart/form-data">
      <?= \Csrf::field() ?>
      <?php if(!empty($_GET['redirect'])): ?>
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect']) ?>">
      <?php endif; ?>

      <div class="register-grid">
        <div class="form-group"><label>Nombre *</label><input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"></div>
        <div class="form-group"><label>Apellidos *</label><input type="text" name="surnames" required value="<?= htmlspecialchars($_POST['surnames'] ?? '') ?>"></div>
        <div class="form-group"><label>Email *</label><input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"></div>
        <div class="form-group"><label>Tel&eacute;fono</label><input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"></div>
        <div class="form-group"><label>Direcci&oacute;n</label><input type="text" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"></div>
        <div class="form-group"><label>C&oacute;digo postal</label><input type="text" name="postal_code" value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>"></div>
        <div class="form-group"><label>Poblaci&oacute;n</label><input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>"></div>
        <div class="form-group"><label>Provincia</label><input type="text" name="province" value="<?= htmlspecialchars($_POST['province'] ?? '') ?>"></div>
        <div class="form-group"><label>Instagram</label><input type="text" name="instagram" placeholder="@usuario" value="<?= htmlspecialchars($_POST['instagram'] ?? '') ?>"></div>
        <div class="form-group"><label>TikTok</label><input type="text" name="tiktok" placeholder="@usuario" value="<?= htmlspecialchars($_POST['tiktok'] ?? '') ?>"></div>
        <div class="form-group"><label>Contrase&ntilde;a *</label>
          <div class="password-field">
            <input type="password" name="password" required id="reg-password">
            <button type="button" class="toggle-password" aria-label="Mostrar"
                    onclick="(function(b){var i=document.getElementById('reg-password');var o=b.querySelector('.eye-open');var c=b.querySelector('.eye-close');if(i.type==='password'){i.type='text';o.style.display='none';c.style.display='block';}else{i.type='password';o.style.display='block';c.style.display='none';}})(this)">
              <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-close" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>
        <div class="form-group"><label>Confirmar contrase&ntilde;a *</label><input type="password" name="password_confirm" required></div>
      </div>

      <div class="form-group" style="margin-top:var(--space-4)">
        <label>Foto de perfil (opcional)</label>
        <input type="file" name="avatar" accept="image/*">
      </div>

      <?php
        $q1 = rand(2,9); $q2 = rand(1,8);
        $_SESSION['captcha_answer'] = $q1 + $q2;
      ?>
      <div class="form-group" style="margin-top:var(--space-2)">
        <label>Verifica que eres humano: &iquest;Cu&aacute;nto es <?= $q1 ?> + <?= $q2 ?>?</label>
        <input type="number" name="captcha" required placeholder="Resultado" style="max-width:120px">
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="margin-top:var(--space-4)">Crear cuenta</button>
      <p class="auth-footer">&iquest;Ya tienes cuenta? <a href="<?= BASE_URL ?>/login">Inicia sesi&oacute;n</a></p>
    </form>
  </div>
</div>
<?php include BASE_PATH . '/templates/partials/footer.php'; ?>
