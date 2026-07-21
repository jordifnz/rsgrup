<?php
// Variables disponibles: $user, $enrollments, $logs
include BASE_PATH . '/templates/admin/layout_admin.php';
?>

<div class="section-header">
  <h1><?= htmlspecialchars($user['name'] . ' ' . $user['surnames']) ?></h1>
  <div style="display:flex;gap:var(--space-3)">
    <button class="btn btn-primary" onclick="openModal('modal-edit-user')">Editar datos</button>
    <a href="<?= BASE_URL ?>/admin/usuarios" class="btn">&larr; Volver</a>
  </div>
</div>

<!-- Tarjetas resumen -->
<div class="form-grid" style="margin-bottom:var(--space-6)">

  <div class="card">
    <div class="card-header"><strong>Datos personales</strong></div>
    <div class="card-body">
      <table class="detail-table">
        <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
        <tr><th>Tel&eacute;fono</th><td><?= htmlspecialchars($user['phone'] ?? '—') ?></td></tr>
        <tr><th>Direcci&oacute;n</th><td><?= htmlspecialchars($user['address'] ?? '—') ?></td></tr>
        <tr><th>C.P.</th><td><?= htmlspecialchars($user['postal_code'] ?? '—') ?></td></tr>
        <tr><th>Poblaci&oacute;n</th><td><?= htmlspecialchars($user['city'] ?? '—') ?></td></tr>
        <tr><th>Provincia</th><td><?= htmlspecialchars($user['province'] ?? '—') ?></td></tr>
        <tr><th>Instagram</th><td><?= htmlspecialchars($user['instagram'] ?? '—') ?></td></tr>
        <tr><th>TikTok</th><td><?= htmlspecialchars($user['tiktok'] ?? '—') ?></td></tr>
        <tr><th>Rol</th><td><span class="badge"><?= htmlspecialchars($user['role']) ?></span></td></tr>
        <tr><th>Registrado</th><td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td></tr>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><strong>Matr&iacute;culas y entregas</strong></div>
    <div class="card-body">
      <?php if (empty($enrollments)): ?>
        <p class="text-muted">Sin entregas registradas.</p>
      <?php else: ?>
      <table class="data-table">
        <thead><tr><th>Tipo</th><th>Entrega</th><th>Estado</th><th>Fecha</th></tr></thead>
        <tbody>
        <?php foreach ($enrollments as $e): ?>
        <tr>
          <td><span class="badge badge-<?= $e['type'] ?>"><?= ucfirst($e['type']) ?></span></td>
          <td><?= htmlspecialchars($e['title']) ?></td>
          <td><span class="badge"><?= htmlspecialchars($e['status']) ?></span></td>
          <td><?= date('d/m/Y', strtotime($e['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Actividad reciente -->
<div class="card">
  <div class="card-header"><strong>Actividad reciente</strong></div>
  <div class="card-body">
    <?php if (empty($logs)): ?>
      <p class="text-muted">Sin actividad registrada.</p>
    <?php else: ?>
    <table class="data-table">
      <thead><tr><th>Fecha</th><th>Acci&oacute;n</th><th>Detalle</th></tr></thead>
      <tbody>
      <?php foreach ($logs as $l): ?>
      <tr>
        <td class="text-sm text-muted"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
        <td><code><?= htmlspecialchars($l['action']) ?></code></td>
        <td class="text-sm"><?= htmlspecialchars($l['detail'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- Modal: Editar usuario -->
<div id="modal-edit-user" class="modal" hidden>
  <div class="modal-backdrop" onclick="closeModal('modal-edit-user')"></div>
  <div class="modal-box modal-box--lg">
    <button class="modal-close" onclick="closeModal('modal-edit-user')" aria-label="Cerrar">&times;</button>
    <h2>Editar usuario</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/usuarios/guardar" class="form">
      <?= \Csrf::field() ?>
      <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
      <div class="form-grid">
        <div class="form-group">
          <label>Nombre *</label>
          <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Apellidos</label>
          <input type="text" name="surnames" value="<?= htmlspecialchars($user['surnames'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
        <div class="form-group">
          <label>Tel&eacute;fono</label>
          <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Direcci&oacute;n</label>
          <input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>C&oacute;digo postal</label>
          <input type="text" name="postal_code" value="<?= htmlspecialchars($user['postal_code'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Poblaci&oacute;n</label>
          <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Provincia</label>
          <input type="text" name="province" value="<?= htmlspecialchars($user['province'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Instagram</label>
          <input type="text" name="instagram" value="<?= htmlspecialchars($user['instagram'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>TikTok</label>
          <input type="text" name="tiktok" value="<?= htmlspecialchars($user['tiktok'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Nueva contrase&ntilde;a <span class="text-muted">(dejar en blanco para no cambiar)</span></label>
          <input type="password" name="password">
        </div>
        <div class="form-group">
          <label>Rol</label>
          <select name="role">
            <option value="alumno" <?= ($user['role'] === 'alumno') ? 'selected' : '' ?>>Alumno</option>
            <option value="admin"  <?= ($user['role'] === 'admin')  ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeModal('modal-edit-user')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>
