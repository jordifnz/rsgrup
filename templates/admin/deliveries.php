<?php
$metaTitle = 'Entregas';
include BASE_PATH . '/templates/admin/layout_admin.php';
?>
<div class="section-header">
  <h1>Gestión de Entregas</h1>
  <button class="btn btn-primary" onclick="openDeliveryModal(null)">+ Nueva entrega</button>
</div>

<table class="data-table">
  <thead><tr><th>Orden</th><th>Tipo</th><th>Título</th><th>Curso</th><th>Precio</th><th>Pago</th><th>Examen</th><th>Email</th><th>WA</th><th>Activa</th><th>Inscritos</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($deliveries as $d): ?>
  <tr>
    <td><?= $d['sort_order'] ?></td>
    <td><span class="badge badge-<?= $d['type'] ?>"><?= ucfirst($d['type']) ?></span></td>
    <td><?= htmlspecialchars($d['title']) ?></td>
    <td class="text-sm text-muted"><?= htmlspecialchars($d['course_title'] ?? '-') ?></td>
    <td><?= number_format((float)$d['price'], 2, ',', '.') ?> €</td>
    <td><span class="badge"><?= $d['payment_type'] ?></span></td>
    <td class="text-sm"><?= htmlspecialchars($d['exam_title'] ?? '—') ?></td>
    <td><?= $d['notify_email']    ? '✅' : '—' ?></td>
    <td><?= $d['notify_whatsapp'] ? '✅' : '—' ?></td>
    <td><?= $d['active']          ? '✅' : '❌' ?></td>
    <td>
      <button class="btn btn-sm btn-secondary"
        onclick="openEnrolledModal(<?= (int)$d['id'] ?>, <?= htmlspecialchars(json_encode($d['title']), ENT_QUOTES) ?>)">
        Ver inscritos
      </button>
    </td>
    <td class="actions">
      <button class="btn btn-sm"
        onclick="openDeliveryModal(<?= htmlspecialchars(json_encode($d), ENT_QUOTES) ?>)">
        Editar
      </button>
      <form action="<?= BASE_URL ?>/admin/entregas/<?= $d['id'] ?>/eliminar"
            method="POST" style="display:inline"
            onsubmit="return confirm('¿Eliminar esta entrega?')">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- Modal: Inscritos en una entrega -->
<div id="modal-enrolled" class="modal" hidden>
  <div class="modal-backdrop" onclick="closeModal('modal-enrolled')"></div>
  <div class="modal-box modal-box--lg">
    <button class="modal-close" onclick="closeModal('modal-enrolled')" aria-label="Cerrar">&times;</button>
    <h2 id="modal-enrolled-title">Inscritos</h2>
    <div id="modal-enrolled-body" style="margin-top:1rem"></div>
  </div>
</div>

<!-- Modal Entrega -->
<div id="modal-delivery" class="modal" hidden>
  <div class="modal-backdrop" onclick="closeModal('modal-delivery')"></div>
  <div class="modal-box modal-box--lg">
    <button class="modal-close" onclick="closeModal('modal-delivery')" aria-label="Cerrar">&times;</button>
    <h2 id="modal-delivery-title">Nueva Entrega</h2>
    <form action="<?= BASE_URL ?>/admin/entregas/guardar" method="POST" enctype="multipart/form-data" class="form">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" id="dlv-id">
      <div class="form-grid">
        <div class="form-group">
          <label for="dlv-title">Título *</label>
          <input type="text" name="title" id="dlv-title" required>
        </div>
        <div class="form-group">
          <label for="dlv-course_id">Curso *</label>
          <select name="course_id" id="dlv-course_id">
            <?php foreach ($courses as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="dlv-type">Tipo</label>
          <select name="type" id="dlv-type">
            <option value="matricula">Matrícula</option>
            <option value="entrega">Entrega</option>
            <option value="practica">Práctica</option>
          </select>
        </div>
        <div class="form-group">
          <label for="dlv-payment_type">Tipo de pago</label>
          <select name="payment_type" id="dlv-payment_type">
            <option value="online">Online (PayPal)</option>
            <option value="presencial">Presencial</option>
          </select>
        </div>
        <div class="form-group">
          <label for="dlv-price">Precio (€)</label>
          <input type="number" name="price" id="dlv-price" step="0.01" min="0" value="0">
        </div>
        <div class="form-group">
          <label for="dlv-sort_order">Orden</label>
          <input type="number" name="sort_order" id="dlv-sort_order" value="0" min="0">
        </div>
        <div class="form-group">
          <label for="dlv-exam_id">Examen vinculado</label>
          <select name="exam_id" id="dlv-exam_id">
            <option value="">— Sin examen —</option>
            <?php foreach ($exams as $e): ?>
              <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="dlv-pdf_file">PDF de la entrega</label>
          <input type="file" name="pdf_file" id="dlv-pdf_file" accept=".pdf">
          <span id="dlv-pdf-current" class="text-sm text-muted"></span>
          <input type="hidden" name="existing_pdf" id="dlv-existing_pdf">
        </div>
      </div>
      <div class="form-group form-group--full">
        <label for="dlv-description">Descripción</label>
        <textarea name="description" id="dlv-description" rows="5"></textarea>
      </div>
      <div class="form-row">
        <label class="checkbox-label"><input type="checkbox" name="notify_email"    id="dlv-notify_email"    value="1"> Notificar por e-mail</label>
        <label class="checkbox-label"><input type="checkbox" name="notify_whatsapp" id="dlv-notify_whatsapp" value="1"> Notificar por WhatsApp</label>
        <label class="checkbox-label"><input type="checkbox" name="active"          id="dlv-active"          value="1" checked> Activa</label>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeModal('modal-delivery')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
// CSRF token disponible en PHP (renderizado inline para el fetch)
var CSRF_TOKEN = <?= json_encode(\Csrf::generate()) ?>;
var BASE_URL   = <?= json_encode(BASE_URL) ?>;

function openEnrolledModal(deliveryId, deliveryTitle) {
  document.getElementById('modal-enrolled-title').textContent = 'Inscritos: ' + deliveryTitle;
  var body = document.getElementById('modal-enrolled-body');
  body.innerHTML = '<p style="color:var(--color-text-muted)">Cargando…</p>';
  openModal('modal-enrolled');

  fetch(BASE_URL + '/admin/entregas/' + deliveryId + '/inscritos', {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (!data.length) {
      body.innerHTML = '<p style="color:var(--color-text-muted);padding:.5rem 0">Sin alumnos inscritos.</p>';
      return;
    }
    var rows = data.map(function(u) {
      return '<tr>'
        + '<td>' + escHtml(u.name + ' ' + u.surnames) + '</td>'
        + '<td>' + escHtml(u.email) + '</td>'
        + '<td><span class="badge">' + escHtml(u.status) + '</span></td>'
        + '<td style="white-space:nowrap">' + u.enrolled_at + '</td>'
        + '<td>'
        + '<form method="POST" action="' + BASE_URL + '/admin/entregas/' + deliveryId + '/baja/' + u.enrollment_id + '" onsubmit="return confirm(\'¿Dar de baja a ' + escHtml(u.name) + ' de esta entrega?\')" style="display:inline">'
        + '<input type="hidden" name="csrf_token" value="' + escHtml(CSRF_TOKEN) + '">'
        + '<button type="submit" class="btn btn-sm btn-danger">Dar de baja</button>'
        + '</form>'
        + '</td>'
        + '</tr>';
    }).join('');
    body.innerHTML = '<div style="overflow-x:auto"><table class="data-table"><thead><tr><th>Nombre</th><th>Email</th><th>Estado</th><th>Inscrito</th><th></th></tr></thead><tbody>' + rows + '</tbody></table></div>';
  })
  .catch(function() {
    body.innerHTML = '<p style="color:var(--color-danger)">Error al cargar los datos.</p>';
  });
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function openDeliveryModal(d) {
  var isNew       = !d || !d.id;
  var descContent = isNew ? '' : (d.description || '');
  document.getElementById('modal-delivery-title').textContent   = isNew ? 'Nueva Entrega' : 'Editar Entrega';
  document.getElementById('dlv-id').value                       = isNew ? '' : (d.id          || '');
  document.getElementById('dlv-title').value                    = isNew ? '' : (d.title        || '');
  document.getElementById('dlv-price').value                    = isNew ? '0': (d.price        || '0');
  document.getElementById('dlv-sort_order').value               = isNew ? '0': (d.sort_order   || '0');
  document.getElementById('dlv-description').value              = descContent;
  setSelectVal('dlv-course_id',    isNew ? '' : String(d.course_id     || ''));
  setSelectVal('dlv-type',         isNew ? 'entrega'  : (d.type          || 'entrega'));
  setSelectVal('dlv-payment_type', isNew ? 'online'   : (d.payment_type  || 'online'));
  setSelectVal('dlv-exam_id',      isNew ? '' : String(d.exam_id       || ''));
  document.getElementById('dlv-notify_email').checked    = isNew ? false : (parseInt(d.notify_email,    10) === 1);
  document.getElementById('dlv-notify_whatsapp').checked = isNew ? false : (parseInt(d.notify_whatsapp, 10) === 1);
  document.getElementById('dlv-active').checked          = isNew ? true  : (parseInt(d.active,          10) === 1);
  var pdfSpan   = document.getElementById('dlv-pdf-current');
  var pdfHidden = document.getElementById('dlv-existing_pdf');
  if (!isNew && d.pdf_file) {
    pdfSpan.textContent = 'Archivo actual: ' + d.pdf_file;
    pdfHidden.value     = d.pdf_file;
  } else {
    pdfSpan.textContent = '';
    pdfHidden.value     = '';
  }
  openModal('modal-delivery');
  initEditorInModal('dlv-description', descContent);
}
</script>

<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>
