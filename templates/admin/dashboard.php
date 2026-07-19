<?php
$metaTitle = 'Dashboard';
include BASE_PATH . '/templates/admin/layout_admin.php';

// KPIs adicionales
$totalRevenue = Database::fetchColumn("SELECT IFNULL(SUM(d.price),0) FROM rsgrup_enrollments e JOIN rsgrup_deliveries d ON d.id=e.delivery_id WHERE e.status='active'");
$matriculados = Database::fetchColumn("SELECT COUNT(DISTINCT user_id) FROM rsgrup_enrollments e JOIN rsgrup_deliveries d ON d.id=e.delivery_id WHERE d.type='matricula' AND e.status='active'");
?>
<div class="section-header">
  <h1>Panel de administración</h1>
</div>

<div class="kpi-grid">
  <div class="kpi-card kpi-blue">
    <div class="kpi-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div class="kpi-body">
      <div class="kpi-value"><?= number_format((int)$stats['users']) ?></div>
      <div class="kpi-label">Usuarios registrados</div>
    </div>
  </div>

  <div class="kpi-card kpi-green">
    <div class="kpi-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
    <div class="kpi-body">
      <div class="kpi-value"><?= number_format((int)$matriculados) ?></div>
      <div class="kpi-label">Matriculados</div>
    </div>
  </div>

  <div class="kpi-card kpi-orange">
    <div class="kpi-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    </div>
    <div class="kpi-body">
      <div class="kpi-value"><?= number_format((int)$stats['enrollments']) ?></div>
      <div class="kpi-label">Inscripciones activas</div>
    </div>
  </div>

  <div class="kpi-card kpi-purple">
    <div class="kpi-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    </div>
    <div class="kpi-body">
      <div class="kpi-value"><?= number_format((float)$totalRevenue, 2, ',', '.') ?> €</div>
      <div class="kpi-label">Ingresos totales</div>
    </div>
  </div>

  <div class="kpi-card kpi-teal">
    <div class="kpi-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    </div>
    <div class="kpi-body">
      <div class="kpi-value"><?= number_format((int)$stats['exams_done']) ?></div>
      <div class="kpi-label">Exámenes realizados</div>
    </div>
  </div>

  <div class="kpi-card kpi-gray">
    <div class="kpi-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
    </div>
    <div class="kpi-body">
      <div class="kpi-value"><?= number_format((int)$stats['courses']) ?></div>
      <div class="kpi-label">Cursos activos</div>
    </div>
  </div>
</div>

<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>
