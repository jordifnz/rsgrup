<?php
$metaTitle = 'Entregas';
include BASE_PATH . '/templates/admin/layout_admin.php';
?>
<div class="section-header">
  <h1>Gestión de Entregas</h1>
  <button class="btn btn-primary" onclick="openModal('modal-delivery')">+ Nueva entrega</button>
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
    <td><?= number_format($d['price'], 2, ',', '.') ?> €</td>
    <td><span class="badge"><?= $d['payment_type'] ?></span></td>
    <td class="text-sm"><?= htmlspecialchars($d['exam_title'] ?? '—') ?></td>
    <td><?= $d['notify_email'] ? '✅' : '—' ?></td>
    <td><?= $d['notify_whatsapp'] ? '✅' : '—' ?></td>
    <td><?= $d['active'] ? '✅' : '❌' ?></td>
    <td class="actions">
      <button class="btn btn-sm" onclick="editDelivery(<?= htmlspecialchars(json_encode($d)) ?>)">Editar</button>
      <form action="<?= BASE_URL ?>/admin/entregas/<?= $d['id'] ?>/eliminar" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar esta entrega?')">
        <?= Csrf::field() ?><button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<div id="modal-delivery" class="modal" hidden>
  <div class="modal-backdrop" onclick="closeModal('modal-delivery')"></div>
  <div class="modal-box modal-box--lg">
    <h2 id="modal-delivery-title">Nueva Entrega</h2>
    <form action="<?= BASE_URL ?>/admin/entregas/guardar" method="POST" enctype="multipart/form-data" class="form">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" id="field-id">
      <div class="form-grid">
        <div class="form-group"><label>Título *</label><input type="text" name="title" id="field-title" required></div>
        <div class="form-group"><label>Curso *</label>
          <select name="course_id" id="field-course_id">
            <?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Tipo</label>
          <select name="type" id="field-type">
            <option value="matricula">Matrícula</option>
            <option value="entrega">Entrega</option>
            <option value="practica">Práctica</option>
          </select>
        </div>
        <div class="form-group"><label>Tipo de pago</label>
          <select name="payment_type" id="field-payment_type">
            <option value="online">Online (PayPal)</option>
            <option value="presencial">Presencial</option>
          </select>
        </div>
        <div class="form-group"><label>Precio (€)</label><input type="number" name="price" id="field-price" step="0.01" min="0" value="0"></div>
        <div class="form-group"><label>Orden</label><input type="number" name="sort_order" id="field-sort_order" value="0" min="0"></div>
        <div class="form-group"><label>Examen vinculado</label>
          <select name="exam_id" id="field-exam_id">
            <option value="">— Sin examen —</option>
            <?php foreach ($exams as $e): ?><option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['title']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>PDF de la entrega</label><input type="file" name="pdf_file" accept=".pdf"></div>
      </div>
      <div class="form-group form-group--full"><label>Descripción</label><textarea name="description" id="field-description" class="wysiwyg-editor" rows="5"></textarea></div>
      <div class="form-row">
        <label class="checkbox-label"><input type="checkbox" name="notify_email" id="field-notify_email" value="1"> Notificar por e-mail</label>
        <label class="checkbox-label"><input type="checkbox" name="notify_whatsapp" id="field-notify_whatsapp" value="1"> Notificar por WhatsApp</label>
        <label class="checkbox-label"><input type="checkbox" name="active" id="field-active" value="1" checked> Activa</label>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeModal('modal-delivery')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>
<script>
function editDelivery(d){
  ['id','title','price','sort_order'].forEach(k=>{ const el=document.getElementById('field-'+k); if(el) el.value=d[k]??''; });
  ['course_id','type','payment_type','exam_id'].forEach(k=>{ const el=document.getElementById('field-'+k); if(el) el.value=d[k]??''; });
  ['notify_email','notify_whatsapp','active'].forEach(k=>{ const el=document.getElementById('field-'+k); if(el) el.checked=!!d[k]; });
  if(window.tinymce&&tinymce.get('field-description')) tinymce.get('field-description').setContent(d.description||'');
  document.getElementById('modal-delivery-title').textContent='Editar Entrega';
  openModal('modal-delivery');
}
</script>
<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>