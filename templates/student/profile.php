<?php
$metaTitle = 'Mi Perfil';
$robots = 'noindex,nofollow';
include BASE_PATH . '/templates/layouts/base.php';
?>
<section class="profile-section">
  <div class="container container--narrow">
    <h1>Mi Perfil</h1>
    <form action="<?= BASE_URL ?>/perfil/guardar" method="POST" enctype="multipart/form-data" class="form">
      <?= Csrf::field() ?>
      <div class="avatar-editor">
        <?php if ($user['avatar']): ?>
          <img src="<?= BASE_URL . htmlspecialchars($user['avatar']) ?>" alt="Avatar" width="80" height="80" class="avatar-img avatar-lg">
        <?php else: ?>
          <div class="avatar-initials avatar-lg"><?= htmlspecialchars(UserModel::getInitials($user)) ?></div>
        <?php endif; ?>
        <label class="btn btn-sm" for="avatar-file">Cambiar foto</label>
        <input type="file" id="avatar-file" name="avatar" accept="image/*" class="sr-only">
      </div>
      <div class="form-grid">
        <div class="form-group"><label>Nombre</label><input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required></div>
        <div class="form-group"><label>Apellidos</label><input type="text" name="surnames" value="<?= htmlspecialchars($user['surnames']) ?>" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required></div>
        <div class="form-group"><label>Teléfono</label><input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></div>
        <div class="form-group"><label>Dirección</label><input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>"></div>
        <div class="form-group"><label>Código postal</label><input type="text" name="postal_code" value="<?= htmlspecialchars($user['postal_code'] ?? '') ?>"></div>
        <div class="form-group"><label>Población</label><input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>"></div>
        <div class="form-group"><label>Provincia</label><input type="text" name="province" value="<?= htmlspecialchars($user['province'] ?? '') ?>"></div>
        <div class="form-group"><label>Instagram</label><input type="text" name="instagram" value="<?= htmlspecialchars($user['instagram'] ?? '') ?>" placeholder="@usuario"></div>
        <div class="form-group"><label>TikTok</label><input type="text" name="tiktok" value="<?= htmlspecialchars($user['tiktok'] ?? '') ?>" placeholder="@usuario"></div>
      </div>
      <hr class="divider">
      <h2 class="h3">Cambiar contraseña</h2>
      <div class="form-grid">
        <div class="form-group"><label>Contraseña actual</label><input type="password" name="current_password" autocomplete="current-password"></div>
        <div class="form-group"><label>Nueva contraseña</label><input type="password" name="new_password" minlength="8" autocomplete="new-password"></div>
      </div>
      <button type="submit" class="btn btn-primary">Guardar cambios</button>
    </form>
  </div>
</section>
<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>