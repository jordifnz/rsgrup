<?php
$metaTitle = 'Entregas';
include BASE_PATH . '/templates/admin/layout_admin.php';
?>
<div class="section-header">
  <h1>Gestión de Entregas</h1>
  <button class="btn btn-primary" onclick="openDeliveryModal(null)">+ Nueva entrega</button>
</div>

<table class="data-table">
  <thead><tr><th>Orden</th><th>Tipo</th><th>Título</th><th>Curso</th><th>Precio</th><th>Pago</th><th>Examen</th><th>Email</th><th>WA</th><th>Activa</th><th>Acciones</th></tr></thead>
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

<!-- Modal Entrega (en el DOM directamente, nunca clonado) -->
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
function openDeliveryModal(d) {
  var isNew       = !d || !d.id;
  var descContent = isNew ? '' : (d.description || '');

  // 1. Campos simples
  document.getElementById('modal-delivery-title').textContent   = isNew ? 'Nueva Entrega' : 'Editar Entrega';
  document.getElementById('dlv-id').value                       = isNew ? '' : (d.id          || '');
  document.getElementById('dlv-title').value                    = isNew ? '' : (d.title        || '');
  document.getElementById('dlv-price').value                    = isNew ? '0': (d.price        || '0');
  document.getElementById('dlv-sort_order').value               = isNew ? '0': (d.sort_order   || '0');
  document.getElementById('dlv-description').value              = descContent;

  // Selects (usando setSelectVal de app.js)
  setSelectVal('dlv-course_id',    isNew ? '' : String(d.course_id     || ''));
  setSelectVal('dlv-type',         isNew ? 'entrega'  : (d.type          || 'entrega'));
  setSelectVal('dlv-payment_type', isNew ? 'online'   : (d.payment_type  || 'online'));
  setSelectVal('dlv-exam_id',      isNew ? '' : String(d.exam_id       || ''));

  // Checkboxes
  document.getElementById('dlv-notify_email').checked    = isNew ? false : (parseInt(d.notify_email,    10) === 1);
  document.getElementById('dlv-notify_whatsapp').checked = isNew ? false : (parseInt(d.notify_whatsapp, 10) === 1);
  document.getElementById('dlv-active').checked          = isNew ? true  : (parseInt(d.active,          10) === 1);

  // PDF actual
  var pdfSpan   = document.getElementById('dlv-pdf-current');
  var pdfHidden = document.getElementById('dlv-existing_pdf');
  if (!isNew && d.pdf_file) {
    pdfSpan.textContent = 'Archivo actual: ' + d.pdf_file;
    pdfHidden.value     = d.pdf_file;
  } else {
    pdfSpan.textContent = '';
    pdfHidden.value     = '';
  }

  // 2. Abrir modal (quita [hidden] → textarea visible)
  openModal('modal-delivery');

  // 3. Inicializar TinyMCE ahora que el elemento es visible
  initEditorInModal('dlv-description', descContent);
}
</script>

<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>
