<?php
$metaTitle = 'Mi perfil';
include BASE_PATH . '/templates/partials/header.php';

$profile  = Database::fetchRow("SELECT * FROM rsgrup_users WHERE id=?", [$_SESSION['user_id']]);
$initials = strtoupper(mb_substr($profile['name']??'',0,1).mb_substr($profile['surnames']??'',0,1));
?>
<div class="profile-wrap">
  <h1>Mi perfil</h1>

  <?php if(!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>
  <?php if(!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <form method="POST" action="<?= BASE_URL ?>/perfil/guardar" enctype="multipart/form-data" class="auth-form">
    <?= \Csrf::field() ?>

    <div class="avatar-upload-wrap">
      <div class="avatar-lg" id="avatar-preview">
        <?php if(!empty($profile['avatar'])): ?>
          <img src="<?= BASE_URL . htmlspecialchars($profile['avatar']) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover">
        <?php else: ?>
          <?= htmlspecialchars($initials) ?>
        <?php endif; ?>
      </div>
      <label class="btn btn-secondary btn-sm" style="cursor:pointer">
        Cambiar foto
        <input type="file" name="avatar" accept="image/*" style="display:none"
               onchange="(function(f){if(f.files&&f.files[0]){var r=new FileReader();r.onload=function(e){var p=document.getElementById('avatar-preview');p.innerHTML='<img src=\''+e.target.result+'\' style=\'width:100%;height:100%;object-fit:cover\'>';};r.readAsDataURL(f.files[0]);}})(this)">
      </label>
    </div>

    <div class="profile-grid">
      <div class="form-group"><label>Nombre</label><input type="text" name="name" value="<?= htmlspecialchars($profile['name']??'') ?>"></div>
      <div class="form-group"><label>Apellidos</label><input type="text" name="surnames" value="<?= htmlspecialchars($profile['surnames']??'') ?>"></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($profile['email']??'') ?>"></div>
      <div class="form-group"><label>Tel&eacute;fono</label><input type="tel" name="phone" value="<?= htmlspecialchars($profile['phone']??'') ?>"></div>
      <div class="form-group"><label>Direcci&oacute;n</label><input type="text" name="address" value="<?= htmlspecialchars($profile['address']??'') ?>"></div>
      <div class="form-group"><label>C&oacute;digo postal</label><input type="text" name="postal_code" value="<?= htmlspecialchars($profile['postal_code']??'') ?>"></div>
      <div class="form-group"><label>Poblaci&oacute;n</label><input type="text" name="city" value="<?= htmlspecialchars($profile['city']??'') ?>"></div>
      <div class="form-group"><label>Provincia</label><input type="text" name="province" value="<?= htmlspecialchars($profile['province']??'') ?>"></div>
      <div class="form-group"><label>Instagram</label><input type="text" name="instagram" placeholder="@usuario" value="<?= htmlspecialchars($profile['instagram']??'') ?>"></div>
      <div class="form-group"><label>TikTok</label><input type="text" name="tiktok" placeholder="@usuario" value="<?= htmlspecialchars($profile['tiktok']??'') ?>"></div>
    </div>

    <hr style="border:none;border-top:1px solid var(--color-divider);margin:var(--space-6) 0">
    <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-4)">Deja en blanco para no cambiar la contrase&ntilde;a.</p>
    <div class="profile-grid">
      <div class="form-group">
        <label>Nueva contrase&ntilde;a</label>
        <div class="password-field">
          <input type="password" name="new_password" id="new-pass">
          <button type="button" class="toggle-password" aria-label="Mostrar"
                  onclick="(function(b){var i=document.getElementById('new-pass');i.type=i.type==='password'?'text':'password';})(this)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <div class="form-group"><label>Confirmar contrase&ntilde;a</label><input type="password" name="new_password_confirm"></div>
    </div>

    <button type="submit" class="btn btn-primary btn-block" style="margin-top:var(--space-6)">Guardar cambios</button>
  </form>
</div>
<?php include BASE_PATH . '/templates/partials/footer.php'; ?>
