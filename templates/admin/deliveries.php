<?php
$metaTitle = 'Entregas';
include BASE_PATH . '/templates/admin/layout_admin.php';
?>

<?php $csrfToken = Csrf::generate(); $baseUrl = BASE_URL; ?>
<script>
window.openDeliveryModal = function(d) {
  var m = document.getElementById('delivery-modal');
  if (!m) return;
  document.getElementById('delivery-modal-title').textContent = d ? 'Editar Entrega' : 'Nueva Entrega';
  document.getElementById('d-id').value      = d ? d.id : '';
  document.getElementById('d-title').value   = d ? d.title : '';
  document.getElementById('d-price').value   = d ? d.price : '0';
  document.getElementById('d-order').value   = d ? d.sort_order : '0';
  var courseEl = document.getElementById('d-course');
  if (d && d.course_id) courseEl.value = d.course_id; else courseEl.value = '';
  var typeEl = document.getElementById('d-type');
  if (d && d.type) typeEl.value = d.type;
  var payEl = document.getElementById('d-payment');
  if (d && d.payment_type) payEl.value = d.payment_type;
  document.getElementById('d-notify-email').checked = d ? d.notify_email == 1 : false;
  document.getElementById('d-notify-wa').checked    = d ? d.notify_whatsapp == 1 : false;
  document.getElementById('d-active').checked       = d ? d.active == 1 : true;
  m.style.display = 'flex';

  // Inicializar TinyMCE (el textarea ya es visible al poner display:flex)
  var descContent = d ? (d.description || '') : '';
  if (typeof initEditorInModal === 'function') {
    initEditorInModal('d-desc', descContent);
  } else {
    document.getElementById('d-desc').value = descContent;
  }
};

window.closeDeliveryModal = function() {
  var m = document.getElementById('delivery-modal');
  // Destruir instancia TinyMCE antes de ocultar
  if (window.tinymce && tinymce.get('d-desc')) {
    tinymce.get('d-desc').remove();
  }
  if (m) m.style.display = 'none';
};

// Volcar contenido TinyMCE al textarea antes de enviar el form
document.addEventListener('DOMContentLoaded', function() {
  var form = document.getElementById('delivery-form');
  if (form) {
    form.addEventListener('submit', function() {
      if (window.tinymce && tinymce.get('d-desc')) {
        tinymce.get('d-desc').save();
      }
    });
  }
});

window.loadEnrolled = function(deliveryId) {
  document.getElementById('enrolled-modal').style.display = 'flex';
  document.getElementById('enrolled-list').innerHTML = '<p style="padding:1rem">Cargando…</p>';
  fetch('<?= $baseUrl ?>/admin/entregas/' + deliveryId + '/inscritos')
    .then(function(r){ return r.json(); })
    .then(function(rows) {
      if (!rows.length) {
        document.getElementById('enrolled-list').innerHTML = '<p style="padding:1rem">Sin inscritos.</p>';
        return;
      }
      var html = '<table class="data-table"><thead><tr><th>Alumno</th><th>Email</th><th>Estado</th><th>Fecha</th><th>Acción</th></tr></thead><tbody>';
      rows.forEach(function(r) {
        html += '<tr><td>' + r.name + ' ' + r.surnames + '</td><td>' + r.email + '</td>';
        html += '<td>' + r.status + '</td><td>' + r.enrolled_at + '</td>';
        html += '<td><form method="POST" action="<?= $baseUrl ?>/admin/entregas/' + deliveryId + '/baja/' + r.enrollment_id + '" onsubmit="return confirm(\'¿Dar de baja?\')">'
              + '<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">'
              + '<button type="submit" class="btn btn-sm btn-danger">Baja</button></form></td></tr>';
      });
      html += '</tbody></table>';
      document.getElementById('enrolled-list').innerHTML = html;
    });
};
</script>

<div class="section-header">
  <h1>Gestión de Entregas</h1>
  <button class="btn btn-primary" onclick="openDeliveryModal(null)">+ Nueva entrega</button>
</div>

<table class="data-table">
  <thead>
    <tr>
      <th>Orden</th><th>Título</th><th>Curso</th><th>Tipo</th>
      <th>Temas</th><th>Inscritos</th><th>Precio</th>
      <th>Activa</th><th>WhatsApp</th><th>Email</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($deliveries as $d): ?>
  <tr>
    <td><?= (int)$d['sort_order'] ?></td>
    <td><?= htmlspecialchars($d['title']) ?></td>
    <td><?= htmlspecialchars($d['course_title'] ?? '—') ?></td>
    <td><span class="badge"><?= htmlspecialchars($d['type']) ?></span></td>
    <td><?= (int)($d['topic_count'] ?? 0) ?></td>
    <td>
      <?= (int)($d['enrolled_count'] ?? 0) ?>
      <button type="button" class="btn btn-sm" onclick="loadEnrolled(<?= $d['id'] ?>)">Ver</button>
    </td>
    <td><?= number_format((float)$d['price'], 2) ?> €</td>
    <td style="text-align:center"><?= $d['active']           ? '✅' : '❌' ?></td>
    <td style="text-align:center"><?= $d['notify_whatsapp']  ? '✅' : '❌' ?></td>
    <td style="text-align:center"><?= $d['notify_email']     ? '✅' : '❌' ?></td>
    <td class="actions">
      <button type="button" class="btn btn-sm" onclick="openDeliveryModal(<?= htmlspecialchars(json_encode($d), ENT_QUOTES) ?>)">Editar</button>
      <form action="<?= BASE_URL ?>/admin/entregas/<?= $d['id'] ?>/eliminar" method="POST"
            style="display:inline" onsubmit="return confirm('¿Eliminar entrega?')">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- Modal inscritos -->
<div id="enrolled-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="modal-header">
      <h2 class="modal-title">Alumnos inscritos</h2>
      <button type="button" class="modal-close" onclick="document.getElementById('enrolled-modal').style.display='none'">&times;</button>
    </div>
    <div id="enrolled-list"></div>
  </div>
</div>

<!-- Modal entrega -->
<div id="delivery-modal" class="modal-overlay" style="display:none">
  <div class="modal-box" style="max-width:640px">
    <div class="modal-header">
      <h2 class="modal-title" id="delivery-modal-title">Nueva Entrega</h2>
      <button type="button" class="modal-close" onclick="closeDeliveryModal()">&times;</button>
    </div>
    <form action="<?= BASE_URL ?>/admin/entregas/guardar" method="POST" id="delivery-form">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" id="d-id" value="">
      <div class="form-grid">
        <div class="form-group form-group--full">
          <label>Título *</label>
          <input type="text" name="title" id="d-title" required>
        </div>
        <div class="form-group">
          <label>Curso</label>
          <select name="course_id" id="d-course">
            <option value="">-- Sin curso --</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Tipo</label>
          <select name="type" id="d-type">
            <option value="entrega">Entrega</option>
            <option value="matricula">Matrícula</option>
            <option value="practica">Práctica</option>
            <option value="videollamada">Videollamada</option>
          </select>
        </div>
        <div class="form-group">
          <label>Precio (€)</label>
          <input type="number" name="price" id="d-price" step="0.01" value="0">
        </div>
        <div class="form-group">
          <label>Pago</label>
          <select name="payment_type" id="d-payment">
            <option value="online">Online (PayPal)</option>
            <option value="presencial">Presencial</option>
          </select>
        </div>
        <div class="form-group">
          <label>Orden</label>
          <input type="number" name="sort_order" id="d-order" value="0">
        </div>
        <div class="form-group form-group--full">
          <label>Descripción</label>
          <textarea name="description" id="d-desc" rows="4"></textarea>
        </div>
        <div class="form-group" style="display:flex;gap:var(--space-4)">
          <label><input type="checkbox" name="notify_email"    id="d-notify-email">    Email al inscribir</label>
          <label><input type="checkbox" name="notify_whatsapp" id="d-notify-wa">       WhatsApp al inscribir</label>
          <label><input type="checkbox" name="active"          id="d-active" checked>   Activa</label>
        </div>
      </div>
      <div style="display:flex;gap:var(--space-3);justify-content:flex-end;margin-top:var(--space-6)">
        <button type="button" class="btn" onclick="closeDeliveryModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>
