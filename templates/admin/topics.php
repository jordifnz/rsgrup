<?php
$metaTitle = 'Temas';
include BASE_PATH . '/templates/admin/layout_admin.php';

$deliveryFilter = (int)($_GET['delivery_id'] ?? 0);
?>

<style>
#filter-delivery {
  background-color: var(--color-surface, #1c1b19);
  color: var(--color-text, #f0ede8);
  border: 1px solid var(--color-border, #3a3836);
  border-radius: 6px;
  padding: 6px 10px;
  font-size: 0.875rem;
  cursor: pointer;
  min-width: 180px;
}
#filter-delivery:focus {
  outline: 2px solid var(--color-brand, #e87722);
  outline-offset: 2px;
}
#filter-delivery option {
  background-color: var(--color-surface, #1c1b19);
  color: var(--color-text, #f0ede8);
}
.att-row {
  display: flex;
  align-items: flex-start;
  gap: var(--space-2);
  padding: var(--space-2) 0;
  border-bottom: 1px solid var(--color-divider);
}
.att-row:last-child { border-bottom: none; }
.att-row__meta { display: flex; flex-direction: column; gap: var(--space-1); flex: 1; min-width: 0; }
.att-row__name { font-size: var(--text-sm); word-break: break-all; }
.att-row__desc { font-size: var(--text-xs); color: var(--color-text-muted); }
.att-new-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-2);
  align-items: start;
  margin-top: var(--space-2);
  padding: var(--space-3);
  background: var(--color-surface-offset);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}
.att-new-row textarea {
  width: 100%;
  min-height: 72px;
  resize: vertical;
  font-size: var(--text-sm);
  padding: var(--space-2);
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  color: var(--color-text);
  font-family: inherit;
}
.att-new-row textarea:focus {
  outline: 2px solid var(--color-primary);
  outline-offset: 0;
  border-color: var(--color-primary);
}
.att-new-row__file { display: flex; flex-direction: column; gap: var(--space-1); }
.att-new-row__file input[type="file"] { font-size: var(--text-sm); }
.att-new-row__file label { font-size: var(--text-xs); color: var(--color-text-muted); }
.att-add-btn {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  margin-top: var(--space-2);
  font-size: var(--text-sm);
  cursor: pointer;
  color: var(--color-primary);
  background: none;
  border: none;
  padding: 0;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.att-add-btn:hover { color: var(--color-primary-hover); }
</style>

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

  // Adjuntos existentes
  var attContainer = document.getElementById('t-attachments-existing');
  attContainer.innerHTML = '';
  if (t && t._attachments && t._attachments.length > 0) {
    t._attachments.forEach(function(a) {
      var row = document.createElement('div');
      row.className = 'att-row';
      row.innerHTML =
        '<label style="cursor:pointer;padding-top:2px" title="Marcar para eliminar al guardar">'
        + '<input type="checkbox" name="delete_attachment[]" value="' + a.id + '">'
        + '<span style="color:var(--color-error);font-size:var(--text-xs);margin-left:4px">✕ Eliminar</span>'
        + '</label>'
        + '<div class="att-row__meta">'
        + '<span class="att-row__name">' + escHtml(a.original_name) + '</span>'
        + (a.description ? '<span class="att-row__desc">' + escHtml(a.description) + '</span>' : '')
        + '</div>';
      attContainer.appendChild(row);
    });
    document.getElementById('t-attachments-existing-wrap').style.display = 'block';
  } else {
    document.getElementById('t-attachments-existing-wrap').style.display = 'none';
  }

  // Limpiar filas de nuevo adjunto y añadir una vacía
  var newWrap = document.getElementById('t-attachments-new');
  newWrap.innerHTML = '';
  addAttachmentRow();

  m.style.display = 'flex';
};

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function addAttachmentRow() {
  var wrap = document.getElementById('t-attachments-new');
  var idx  = wrap.children.length;
  var row  = document.createElement('div');
  row.className = 'att-new-row';
  row.innerHTML =
    '<div class="att-new-row__file">'
    + '<label>Archivo ' + (idx + 1) + ' <small style="color:var(--color-text-muted)">(PDF, Word, Excel, imagen, ZIP…)</small></label>'
    + '<input type="file" name="attachments[]"'
    + ' accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.jpg,.jpeg,.png,.gif,.mp4,.mp3">'
    + '</div>'
    + '<div>'
    + '<label style="font-size:var(--text-xs);color:var(--color-text-muted);display:block;margin-bottom:4px">Descripción del adjunto <small>(opcional)</small></label>'
    + '<textarea name="attachment_desc[]" placeholder="Ej: Manual de uso, Hoja de cálculo de prácticas…" rows="3"></textarea>'
    + '</div>';
  wrap.appendChild(row);
}
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
      <th>Examen</th><th>PDF</th><th>Adjuntos</th><th>Activo</th><th>Acciones</th>
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
    <td><?= count($t['_attachments']) > 0
          ? '<span style="color:var(--color-primary);font-weight:600">'.count($t['_attachments']).' archivo'.(count($t['_attachments'])>1?'s':'').'</span>'
          : '—' ?></td>
    <td><?= $t['active'] ? '<span style="color:var(--color-success)">Sí</span>' : '<span style="color:var(--color-error)">No</span>' ?></td>
    <td class="actions">
      <button type="button" class="btn btn-sm"
              onclick="openTopicModal(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)">Editar</button>
      <form action="<?= BASE_URL ?>/admin/temas/<?= $t['id'] ?>/eliminar" method="POST"
            style="display:inline" onsubmit="return confirm('¿Eliminar tema y todos sus adjuntos?')">
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
  <div class="modal-box" style="max-width:720px">
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
          <textarea name="description" id="t-desc" rows="5"
                    style="width:100%;resize:vertical;min-height:100px"></textarea>
        </div>
        <div class="form-group form-group--full">
          <label>PDF principal (temario)</label>
          <input type="file" name="pdf_file" id="t-pdf" accept=".pdf">
          <small id="t-pdf-current" style="display:none;margin-top:var(--space-1);color:var(--color-text-muted)"></small>
        </div>

        <!-- Adjuntos adicionales existentes -->
        <div class="form-group form-group--full" id="t-attachments-existing-wrap" style="display:none">
          <label style="margin-bottom:var(--space-1);display:block">
            Adjuntos actuales
            <small style="color:var(--color-text-muted);font-weight:400"> — marca ✕ Eliminar para borrar al guardar</small>
          </label>
          <div id="t-attachments-existing"></div>
        </div>

        <!-- Nuevos adjuntos -->
        <div class="form-group form-group--full">
          <label style="margin-bottom:var(--space-2);display:block">
            Añadir adjuntos adicionales
            <small style="color:var(--color-text-muted);font-weight:400"> — PDF, Word, Excel, imagen, ZIP, vídeo…</small>
          </label>
          <div id="t-attachments-new"></div>
          <button type="button" class="att-add-btn" onclick="addAttachmentRow()">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="2" x2="8" y2="14"/><line x1="2" y1="8" x2="14" y2="8"/></svg>
            Añadir otro archivo
          </button>
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
