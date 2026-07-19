<?php
$metaTitle = 'Mi perfil';
include BASE_PATH . '/templates/partials/header.php';

$profile  = Database::fetchRow("SELECT * FROM rsgrup_users WHERE id=?", [$_SESSION['user_id']]);
$initials = strtoupper(mb_substr($profile['name']??'',0,1).mb_substr($profile['surnames']??'',0,1));
$hasAvatar = !empty($profile['avatar']);
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
    <!-- Campo oculto: si se marca 1, el controller borra el avatar -->
    <input type="hidden" name="delete_avatar" id="delete_avatar_flag" value="0">

    <div class="avatar-upload-wrap">
      <!-- Preview del avatar -->
      <div class="avatar-lg" id="avatar-preview">
        <?php if($hasAvatar): ?>
          <img src="<?= BASE_URL . htmlspecialchars($profile['avatar']) ?>" alt="Avatar"
               style="width:100%;height:100%;object-fit:cover;border-radius:50%">
        <?php else: ?>
          <span id="avatar-initials"><?= htmlspecialchars($initials) ?></span>
        <?php endif; ?>
      </div>

      <!-- Acciones de avatar -->
      <div style="display:flex;gap:var(--space-2);align-items:center;flex-wrap:wrap">
        <label class="btn btn-secondary btn-sm" style="cursor:pointer" title="Subir foto">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          Cambiar foto
          <input type="file" name="avatar" accept="image/*" style="display:none" id="avatar-file-input"
                 onchange="rsAvatarPreview(this)">
        </label>

        <!-- Botón eliminar: solo visible si hay avatar O si se ha seleccionado uno nuevo -->
        <button type="button" id="btn-delete-avatar"
                class="btn btn-sm"
                style="color:var(--color-error);border-color:var(--color-error);background:transparent;<?= $hasAvatar ? '' : 'display:none' ?>"
                title="Eliminar foto y volver a iniciales"
                onclick="rsDeleteAvatar('<?= htmlspecialchars($initials) ?>')"
                aria-label="Eliminar foto de perfil">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
            <path d="M10 11v6"/><path d="M14 11v6"/>
            <path d="M9 6V4h6v2"/>
          </svg>
          Eliminar foto
        </button>
      </div>
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

<script>
// Preview al seleccionar nueva foto
function rsAvatarPreview(input) {
  if (!input.files || !input.files[0]) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    var p = document.getElementById('avatar-preview');
    p.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%" alt="Avatar">';
    document.getElementById('delete_avatar_flag').value = '0';
    document.getElementById('btn-delete-avatar').style.display = 'inline-flex';
  };
  reader.readAsDataURL(input.files[0]);
}

// Eliminar foto: resetea preview a iniciales y marca el flag
function rsDeleteAvatar(initials) {
  var p   = document.getElementById('avatar-preview');
  p.innerHTML = '<span id="avatar-initials" style="font-size:2rem;font-weight:700;color:var(--color-text-inverse)">' + initials + '</span>';
  // Limpiar el file input para que no se suba nada
  var fileInput = document.getElementById('avatar-file-input');
  fileInput.value = '';
  // Marcar para que el controller borre el avatar
  document.getElementById('delete_avatar_flag').value = '1';
  // Ocultar el botón de borrar (ya no hay foto)
  document.getElementById('btn-delete-avatar').style.display = 'none';
}
</script>
<?php include BASE_PATH . '/templates/partials/footer.php'; ?>
