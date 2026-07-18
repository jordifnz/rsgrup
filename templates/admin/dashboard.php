<?php
$metaTitle = 'Dashboard Admin';
include BASE_PATH . '/templates/admin/layout_admin.php';
?>
<h1>Panel de administración</h1>
<div class="kpi-grid">
  <div class="kpi-card">
    <p class="kpi-label">Usuarios registrados</p>
    <p class="kpi-value"><?= number_format($stats['total_users']) ?></p>
  </div>
  <div class="kpi-card">
    <p class="kpi-label">Matriculados</p>
    <p class="kpi-value"><?= number_format($stats['total_enrolled']) ?></p>
  </div>
  <div class="kpi-card">
    <p class="kpi-label">Inscripciones activas</p>
    <p class="kpi-value"><?= number_format($stats['total_enrollments']) ?></p>
  </div>
  <div class="kpi-card">
    <p class="kpi-label">Ingresos totales</p>
    <p class="kpi-value"><?= number_format($stats['total_revenue'], 2, ',', '.') ?> €</p>
  </div>
</div>
<h2 style="margin-top:var(--space-8)">Actividad reciente</h2>
<table class="data-table">
  <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Descripción</th></tr></thead>
  <tbody>
  <?php foreach ($recentActivity as $log): ?>
  <tr>
    <td class="nowrap text-sm"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
    <td><?= htmlspecialchars($log['name'] ?? 'Anónimo') ?></td>
    <td><code class="text-sm"><?= htmlspecialchars($log['action']) ?></code></td>
    <td class="text-muted"><?= htmlspecialchars($log['description']) ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>