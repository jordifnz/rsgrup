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

<!-- Grid 2 columnas: datos personales + matrículas -->
<div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,2fr);gap:var(--space-5);margin-bottom:var(--space-6);align-items:start">

  <!-- Datos personales -->
  <div class="card">
    <div class="card-header"><strong>Datos personales</strong></div>
    <div class="card-body">
      <table class="detail-table">
        <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
        <tr><th>Tel&eacute;fono</th><td><?= htmlspecialchars($user['phone'] ?? '&mdash;') ?></td></tr>
        <tr><th>Direcci&oacute;n</th><td><?= htmlspecialchars($user['address'] ?? '&mdash;') ?></td></tr>
        <tr><th>C.P.</th><td><?= htmlspecialchars($user['postal_code'] ?? '&mdash;') ?></td></tr>
        <tr><th>Poblaci&oacute;n</th><td><?= htmlspecialchars($user['city'] ?? '&mdash;') ?></td></tr>
        <tr><th>Provincia</th><td><?= htmlspecialchars($user['province'] ?? '&mdash;') ?></td></tr>
        <tr><th>Instagram</th><td><?= htmlspecialchars($user['instagram'] ?? '&mdash;') ?></td></tr>
        <tr><th>TikTok</th><td><?= htmlspecialchars($user['tiktok'] ?? '&mdash;') ?></td></tr>
        <tr><th>Rol</th><td><span class="badge"><?= htmlspecialchars($user['role']) ?></span></td></tr>
        <tr><th>Registrado</th><td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td></tr>
      </table>
    </div>
  </div>

  <!-- Matrículas y entregas -->
  <div class="card">
    <div class="card-header"><strong>Matr&iacute;culas y entregas</strong></div>
    <div class="card-body" style="padding:0">
      <?php if (empty($enrollments)): ?>
        <p style="padding:var(--space-5);color:var(--color-text-muted)">Sin entregas registradas.</p>
      <?php else: ?>
      <div style="overflow-x:auto">
        <table class="data-table" style="min-width:480px">
          <thead>
            <tr>
              <th>Tipo</th>
              <th>Entrega</th>
              <th>Estado</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($enrollments as $e): ?>
          <tr>
            <td><span class="badge badge-<?= htmlspecialchars($e['type']) ?>"><?= ucfirst(htmlspecialchars($e['type'])) ?></span></td>
            <td><?= htmlspecialchars($e['title']) ?></td>
            <td><span class="badge"><?= htmlspecialchars($e['status']) ?></span></td>
            <td style="white-space:nowrap"><?= date('d/m/Y', strtotime($e['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Actividad reciente -->
<div class="card" style="margin-bottom:var(--space-6)">
  <div class="card-header"><strong>Actividad reciente</strong></div>
  <div class="card-body" style="padding:0">
    <?php if (empty($logs)): ?>
      <p style="padding:var(--space-5);color:var(--color-text-muted)">Sin actividad registrada.</p>
    <?php else: ?>
    <div style="overflow-x:auto">
      <table class="data-table">
        <thead>
          <tr><th>Fecha</th><th>Acci&oacute;n</th><th>Detalle</th></tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td style="white-space:nowrap;color:var(--color-text-muted);font-size:var(--text-xs)"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
          <td><code style="font-size:var(--text-xs)"><?= htmlspecialchars($l['action']) ?></code></td>
          <td style="font-size:var(--text-sm)"><?= htmlspecialchars($l['detail'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal: Editar usuario -->
<div id="modal-edit-user" class="modal" hidden>
  <div class="modal-backdrop" onclick="closeModal('modal-edit-user')"></div>
  <div class="modal-box modal-box--lg">
    <button class="modal-close" onclick="closeModal('modal-edit-user')" aria-label="Cerrar">&times;</button>
    <h2 style="margin-bottom:var(--space-5)">Editar usuario</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/usuarios/guardar">
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
          <label>Nueva contrase&ntilde;a <small>(dejar en blanco para no cambiar)</small></label>
          <input type="password" name="password" autocomplete="new-password">
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
