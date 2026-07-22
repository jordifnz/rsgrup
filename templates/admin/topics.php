<?php
$metaTitle = 'Temas';
include BASE_PATH . '/templates/admin/layout_admin.php';

$deliveryFilter = (int)($_GET['delivery_id'] ?? 0);
?>

<script>
window.openTopicModal = function(t) {
  var m = document.getElementById('topic-modal');
  document.getElementById('topic-modal-title').textContent = t ? 'Editar Tema' : 'Nuevo Tema';
  document.getElementById('t-id').value       = t ? t.id : '';
  document.getElementById('t-title').value    = t ? t.title : '';
  document.getElementById('t-desc').value     = t ? (t.description || '') : '';
  document.getElementById('t-order').value    = t ? t.sort_order : '0';
  document.getElementById('t-active').checked = t ? t.active == 1 : true;
  var dEl = document.getElementById('t-delivery');
  dEl.value = t ? (t.delivery_id || '') : '';
  var eEl = document.getElementById('t-exam');
  eEl.value = t ? (t.exam_id || '') : '';
  var pdfInfo = document.getElementById('t-pdf-current');
  if (t && t.pdf_file) {
    pdfInfo.textContent = 'PDF actual: ' + t.pdf_file;
    pdfInfo.style.display = 'block';
  } else {
    pdfInfo.style.display = 'none';
  }
  m.style.display = 'flex';
};
</script>

<div class="section-header">
  <h1>Gestión de Temas</h1>
  <button class="btn btn-primary" onclick="openTopicModal(null)">+ Nuevo tema</button>
</div>

<!-- Filtro por entrega -->
<form method="GET" action="<?= BASE_URL ?>/admin/temas" style="margin-bottom:var(--space-4);display:flex;gap:var(--space-3);align-items:center">
  <label for="filter-delivery"><strong>Filtrar por entrega:</strong></label>
  <select name="delivery_id" id="filter-delivery" onchange="this.form.submit()">
    <option value="">-- Todas --</option>
    <?php foreach ($deliveries as $dv): ?>
      <option value="<?= $dv['id'] ?>" <?= $deliveryFilter === (int)$dv['id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($dv['title']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<?php
$visibleTopics = $deliveryFilter
    ? array_filter($topics, fn($t) => (int)$t['delivery_id'] === $deliveryFilter)
    : $topics;
?>

<table class="data-table">
  <thead>
    <tr>
      <th>Orden</th><th>Título</th><th>Entrega</th>
      <th>Examen</th><th>PDF</th><th>Activo</th><th>Acciones</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($visibleTopics as $t): ?>
  <tr>
    <td><?= (int)$t['sort_order'] ?></td>
    <td><?= htmlspecialchars($t['title']) ?></td>
    <td><?= htmlspecialchars($t['delivery_title'] ?? '—') ?></td>
    <td><?= $t['exam_title'] ? htmlspecialchars($t['exam_title']) : '<em style="color:var(--color-text-muted)">Sin examen</em>' ?></td>
    <td><?= $t['pdf_file'] ? '<span style="color:var(--color-success)">&#10003;</span>' : '—' ?></td>
    <td><?= $t['active'] ? '<span style="color:var(--color-success)">Sí</span>' : '<span style="color:var(--color-error)">No</span>' ?></td>
    <td class="actions">
      <button type="button" class="btn btn-sm"
              onclick="openTopicModal(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)">Editar</button>
      <form action="<?= BASE_URL ?>/admin/temas/<?= $t['id'] ?>/eliminar" method="POST"
            style="display:inline" onsubmit="return confirm('¿Eliminar tema?')">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- Modal tema -->
<div id="topic-modal" class="modal-overlay" style="display:none">
  <div class="modal-box" style="max-width:640px">
    <div class="modal-header">
      <h2 class="modal-title" id="topic-modal-title">Nuevo Tema</h2>
      <button type="button" class="modal-close"
              onclick="document.getElementById('topic-modal').style.display='none'">&times;</button>
    </div>
    <form action="<?= BASE_URL ?>/admin/temas/guardar" method="POST"
          enctype="multipart/form-data" id="topic-form">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" id="t-id" value="">
      <div class="form-grid">
        <div class="form-group form-group--full">
          <label>Título *</label>
          <input type="text" name="title" id="t-title" required>
        </div>
        <div class="form-group">
          <label>Entrega *</label>
          <select name="delivery_id" id="t-delivery" required>
            <option value="">-- Selecciona entrega --</option>
            <?php foreach ($deliveries as $dv): ?>
              <option value="<?= $dv['id'] ?>"><?= htmlspecialchars($dv['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Examen vinculado</label>
          <select name="exam_id" id="t-exam">
            <option value="">-- Sin examen --</option>
            <?php foreach ($exams as $ex): ?>
              <option value="<?= $ex['id'] ?>"><?= htmlspecialchars($ex['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Orden</label>
          <input type="number" name="sort_order" id="t-order" value="0">
        </div>
        <div class="form-group form-group--full">
          <label>Descripción</label>
          <textarea name="description" id="t-desc" rows="3"></textarea>
        </div>
        <div class="form-group form-group--full">
          <label>PDF (subir archivo)</label>
          <input type="file" name="pdf_file" id="t-pdf" accept=".pdf">
          <small id="t-pdf-current" style="display:none;margin-top:var(--space-1);color:var(--color-text-muted)"></small>
        </div>
        <div class="form-group">
          <label><input type="checkbox" name="active" id="t-active" checked> Activo</label>
        </div>
      </div>
      <div style="display:flex;gap:var(--space-3);justify-content:flex-end;margin-top:var(--space-6)">
        <button type="button" class="btn"
                onclick="document.getElementById('topic-modal').style.display='none'">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>
