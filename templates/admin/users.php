<?php
$metaTitle = 'Usuarios';
include BASE_PATH . '/templates/admin/layout_admin.php';
?>

<div class="section-header">
  <h1>Usuarios</h1>
  <button class="btn btn-primary" onclick="openModal('modal-user-new')">+ Nuevo usuario</button>
</div>

<form method="GET" class="search-bar">
  <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Nombre, email...">
  <select name="role">
    <option value="">Todos los roles</option>
    <option value="alumno" <?= ($_GET['role'] ?? '') === 'alumno' ? 'selected' : '' ?>>Alumno</option>
    <option value="admin"  <?= ($_GET['role'] ?? '') === 'admin'  ? 'selected' : '' ?>>Admin</option>
  </select>
  <button type="submit" class="btn">Buscar</button>
</form>

<table class="data-table">
  <thead>
    <tr>
      <th>ID</th><th>Nombre</th><th>Email</th><th>Tel&eacute;fono</th>
      <th>Rol</th><th>Registrado</th><th>Matriculado</th><th>Acciones</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($users as $u): ?>
  <tr>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['name'] . ' ' . $u['surnames']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><?= htmlspecialchars($u['phone'] ?? '') ?></td>
    <td><span class="badge"><?= $u['role'] ?></span></td>
    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
    <td><?= $u['has_matricula']
          ? '<span class="badge badge-success">✅ Sí</span>'
          : '<span class="badge badge-muted">❌ No</span>' ?></td>
    <td class="actions">
      <a href="<?= BASE_URL ?>/admin/usuarios/<?= $u['id'] ?>" class="btn btn-sm">Ver detalle</a>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (empty($users)): ?>
  <tr><td colspan="8" class="empty-row">No se encontraron usuarios</td></tr>
  <?php endif; ?>
  </tbody>
</table>

<!-- Modal: Nuevo usuario (en el DOM, nunca dentro de <template>) -->
<div id="modal-user-new" class="modal" hidden>
  <div class="modal-backdrop" onclick="closeModal('modal-user-new')"></div>
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('modal-user-new')" aria-label="Cerrar">&times;</button>
    <h2>Nuevo usuario</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/usuarios/guardar" class="form">
      <?= \Csrf::field() ?>
      <input type="hidden" name="id" value="0">
      <div class="form-grid">
        <div class="form-group"><label>Nombre *</label><input type="text" name="name" required></div>
        <div class="form-group"><label>Apellidos *</label><input type="text" name="surnames" required></div>
        <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Tel&eacute;fono</label><input type="text" name="phone"></div>
        <div class="form-group"><label>Direcci&oacute;n</label><input type="text" name="address"></div>
        <div class="form-group"><label>C&oacute;digo postal</label><input type="text" name="postal_code"></div>
        <div class="form-group"><label>Poblaci&oacute;n</label><input type="text" name="city"></div>
        <div class="form-group"><label>Provincia</label><input type="text" name="province"></div>
        <div class="form-group"><label>Instagram</label><input type="text" name="instagram"></div>
        <div class="form-group"><label>TikTok</label><input type="text" name="tiktok"></div>
        <div class="form-group"><label>Contrase&ntilde;a *</label><input type="password" name="password" required></div>
        <div class="form-group">
          <label>Rol</label>
          <select name="role">
            <option value="alumno">Alumno</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeModal('modal-user-new')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Crear usuario</button>
      </div>
    </form>
  </div>
</div>

<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>
