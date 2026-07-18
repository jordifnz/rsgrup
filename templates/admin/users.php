<?php
$metaTitle = 'Usuarios';
include BASE_PATH . '/templates/admin/layout_admin.php';
?>
<div class="section-header">
  <h1>Usuarios</h1>
</div>
<form method="GET" class="search-bar">
  <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Nombre, email...">
  <select name="role">
    <option value="">Todos los roles</option>
    <option value="alumno" <?= ($_GET['role'] ?? '') === 'alumno' ? 'selected' : '' ?>>Alumno</option>
    <option value="admin" <?= ($_GET['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
  </select>
  <button type="submit" class="btn">Buscar</button>
</form>
<table class="data-table">
  <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Rol</th><th>Registrado</th><th>Matriculado</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): ?>
  <tr>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['name'] . ' ' . $u['surnames']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><?= htmlspecialchars($u['phone'] ?? '') ?></td>
    <td><span class="badge"><?= $u['role'] ?></span></td>
    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
    <td><?= $u['has_matricula'] ? '✅' : '❌' ?></td>
    <td class="actions">
      <a href="<?= BASE_URL ?>/admin/usuarios/<?= $u['id'] ?>" class="btn btn-sm">Ver detalle</a>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>