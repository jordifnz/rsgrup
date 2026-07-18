<?php
$metaTitle = 'Actividad';
include BASE_PATH . '/templates/admin/layout_admin.php';
?>
<h1>Registro de Actividad</h1>
<form method="GET" class="search-bar">
  <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Usuario, acción...">
  <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
  <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
  <button type="submit" class="btn">Filtrar</button>
</form>
<table class="data-table">
  <thead><tr><th>Fecha/Hora</th><th>Usuario</th><th>Email</th><th>Acción</th><th>Descripción</th><th>IP</th></tr></thead>
  <tbody>
  <?php foreach ($logs as $log): ?>
  <tr>
    <td class="nowrap text-sm"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
    <td><?= htmlspecialchars($log['name'] ?? 'Anónimo') ?></td>
    <td class="text-sm"><?= htmlspecialchars($log['email'] ?? '') ?></td>
    <td><code class="text-sm"><?= htmlspecialchars($log['action']) ?></code></td>
    <td class="text-muted"><?= htmlspecialchars($log['description']) ?></td>
    <td class="text-muted text-sm"><?= htmlspecialchars($log['ip_address']) ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>