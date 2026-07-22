<?php
$metaTitle = 'Impresión masiva de títulos';
$robots    = 'noindex,nofollow';
include BASE_PATH . '/templates/layouts/base.php';

$perPage  = 20;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;
$total    = (int)($totalRows ?? 0);
$pages    = $total > 0 ? (int)ceil($total / $perPage) : 1;
?>

<section class="delivery-page">
  <div class="container">

    <nav class="breadcrumb-nav" aria-label="Ruta">
      <a href="<?= BASE_URL ?>/admin/settings" class="breadcrumb-link">
        <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 6.5L8 2l6 4.5V14a1 1 0 01-1 1H3a1 1 0 01-1-1V6.5z"/><path d="M6 15V9h4v6"/></svg>
        Ajustes
      </a>
      <span class="breadcrumb-sep" aria-hidden="true">›</span>
      <span class="breadcrumb-current">Impresión masiva de títulos</span>
    </nav>

    <?php if ($flash = getFlash()): ?>
      <div class="flash flash--<?= $flash['type'] ?>" role="alert"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <div class="course-card">

      <!-- Cabecera -->
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-3);margin-bottom:var(--space-4)">
        <h1 style="font-size:var(--text-lg);font-weight:700;margin:0">Impresión masiva de títulos</h1>
        <div style="display:flex;gap:var(--space-2);align-items:center">
          <button type="button" class="btn btn-sm btn-secondary" onclick="toggleAll(true)">Seleccionar todo</button>
          <button type="button" class="btn btn-sm btn-secondary" onclick="toggleAll(false)">Deseleccionar todo</button>
          <button type="button" class="btn btn-sm btn-primary" id="btn-generate" onclick="submitBulk()">
            <i data-lucide="printer"></i> Generar PDF
          </button>
        </div>
      </div>

      <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-4)">
        Alumnos inscritos en todas las entregas del curso y con examen realizado · <strong><?= $total ?></strong> registros
      </p>

      <?php if (empty($rows)): ?>
        <div style="text-align:center;padding:var(--space-12);color:var(--color-text-muted)">
          <i data-lucide="inbox" style="width:40px;height:40px;margin-bottom:var(--space-3)"></i>
          <p>No hay alumnos que cumplan los requisitos todavía.</p>
        </div>
      <?php else: ?>

      <form id="bulk-form" action="<?= BASE_URL ?>/admin/titulos-masivos/generar" method="POST">
        <?= Csrf::field() ?>

        <div class="delivery-list">

          <!-- Cabecera de tabla -->
          <div style="display:grid;grid-template-columns:2rem 1fr 1fr 1fr auto;gap:var(--space-2);padding:var(--space-2) var(--space-3);font-size:var(--text-xs);font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);border-bottom:1px solid var(--color-divider)">
            <span></span>
            <span>Alumno</span>
            <span>Email</span>
            <span>Fecha matrícula</span>
            <span>Nota</span>
          </div>

          <?php foreach ($rows as $row): ?>
          <?php $bulkKey = (int)$row['user_id'] . '_' . (int)$row['course_id']; ?>
          <div class="delivery-row enrolled" style="display:grid;grid-template-columns:2rem 1fr 1fr 1fr auto;gap:var(--space-2);align-items:center">
            <div>
              <input type="checkbox" name="bulk_keys[]" value="<?= htmlspecialchars($bulkKey) ?>"
                     class="bulk-check" style="width:16px;height:16px;cursor:pointer;accent-color:var(--color-primary)">
            </div>
            <div style="font-size:var(--text-sm);font-weight:500">
              <?= htmlspecialchars($row['name'] . ' ' . $row['surnames']) ?>
            </div>
            <div style="font-size:var(--text-xs);color:var(--color-text-muted)">
              <?= htmlspecialchars($row['email']) ?>
            </div>
            <div style="font-size:var(--text-xs);color:var(--color-text-muted)">
              <?= htmlspecialchars($row['enrolled_at']) ?>
            </div>
            <div style="font-size:var(--text-xs);white-space:nowrap">
              <?php if ($row['score'] !== null): ?>
                <span class="badge badge-score"><?= number_format((float)$row['score'] / 10, 1) ?> / 10</span>
                <?php if ((float)$row['score'] >= $passingScore): ?>
                  <span class="badge badge-success">Aprobado</span>
                <?php else: ?>
                  <span class="badge" style="background:rgba(161,44,123,.1);color:#a12c7b;border-color:rgba(161,44,123,.2)">Suspenso</span>
                <?php endif; ?>
              <?php else: ?>
                <span class="badge badge-muted">Sin nota</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>

        </div>
      </form>

      <!-- Paginación -->
      <?php if ($pages > 1): ?>
      <div style="display:flex;justify-content:center;align-items:center;gap:var(--space-2);margin-top:var(--space-6)">
        <?php if ($page > 1): ?>
          <a href="?page=<?= $page - 1 ?>" class="btn btn-sm btn-secondary">&larr; Anterior</a>
        <?php endif; ?>
        <span style="font-size:var(--text-sm);color:var(--color-text-muted)">
          Página <?= $page ?> de <?= $pages ?>
        </span>
        <?php if ($page < $pages): ?>
          <a href="?page=<?= $page + 1 ?>" class="btn btn-sm btn-secondary">Siguiente &rarr;</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php endif; ?>

    </div><!-- /.course-card -->
  </div>
</section>

<script>
function toggleAll(state) {
  document.querySelectorAll('.bulk-check').forEach(c => c.checked = state);
}
function submitBulk() {
  const checked = document.querySelectorAll('.bulk-check:checked');
  if (checked.length === 0) {
    alert('Selecciona al menos un alumno.');
    return;
  }
  document.getElementById('bulk-form').submit();
}
</script>

<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>
