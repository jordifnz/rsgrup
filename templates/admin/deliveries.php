<?php
// AdminController::deliveries
requireAdmin();
$metaTitle = 'Entregas';
?>
<?php include BASE_PATH . '/templates/admin/layout_admin.php'; ?>

<div class="admin-page-header">
  <div class="admin-page-header-left">
    <h1>Entregas</h1>
    <p>Una Entrega es el nivel al que se inscriben los alumnos. Contiene uno o varios Temas.</p>
  </div>
  <button class="btn-admin-primary" onclick="openModal('modalCreateDelivery')">+ Nueva entrega</button>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="alert-admin alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Ord.</th>
        <th>Curso</th>
        <th>Título</th>
        <th>Tipo</th>
        <th>Precio</th>
        <th>Temas</th>
        <th>Inscritos</th>
        <th>Activo</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($deliveries as $d): ?>
      <tr>
        <td><?= $d['id'] ?></td>
        <td><?= $d['sort_order'] ?></td>
        <td><?= htmlspecialchars($d['course_title'] ?? '') ?></td>
        <td><?= htmlspecialchars($d['title']) ?></td>
        <td><span class="badge badge-type-<?= $d['type'] ?>"><?= $d['type'] ?></span></td>
        <td><?= number_format((float)$d['price'], 2, ',', '.') ?> €</td>
        <td><?= $d['topic_count'] ?></td>
        <td>
          <button class="btn-admin-sm btn-admin-edit"
            onclick="loadEnrolled(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['title'])) ?>')"
            title="Ver inscritos">
            <?= $d['enrolled_count'] ?> 👥
          </button>
        </td>
        <td><?= $d['active'] ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-error">No</span>' ?></td>
        <td class="td-actions">
          <button class="btn-admin-sm btn-admin-edit"
            onclick="openEditDelivery(<?= htmlspecialchars(json_encode($d)) ?>)">✏️ Editar</button>
          <form method="post" action="<?= BASE_URL ?>/admin/entregas/<?= $d['id'] ?>/borrar"
                style="display:inline" onsubmit="return confirm('Borrar entrega?')">
            <?= csrfField() ?>
            <button class="btn-admin-sm btn-admin-delete" type="submit">🗑 Borrar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal inscritos -->
<div id="modalEnrolled" class="modal-admin" style="display:none">
  <div class="modal-admin-box">
    <h2 id="enrolledTitle">Inscritos</h2>
    <div id="enrolledContent"><p>Cargando…</p></div>
    <div class="modal-admin-actions">
      <button type="button" class="btn-admin-secondary" onclick="closeModal('modalEnrolled')">Cerrar</button>
    </div>
  </div>
</div>

<!-- Modal crear entrega -->
<div id="modalCreateDelivery" class="modal-admin" role="dialog" aria-modal="true" style="display:none">
  <div class="modal-admin-box">
    <h2>Nueva Entrega</h2>
    <form method="post" action="<?= BASE_URL ?>/admin/entregas/guardar">
      <?= csrfField() ?>
      <input type="hidden" name="id" value="0">
      <?php include __DIR__ . '/_delivery_form_fields.php'; ?>
      <div class="modal-admin-actions">
        <button type="submit" class="btn-admin-primary">Guardar</button>
        <button type="button" class="btn-admin-secondary" onclick="closeModal('modalCreateDelivery')">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal editar entrega -->
<div id="modalEditDelivery" class="modal-admin" role="dialog" aria-modal="true" style="display:none">
  <div class="modal-admin-box">
    <h2>Editar Entrega</h2>
    <form method="post" action="<?= BASE_URL ?>/admin/entregas/guardar" id="formEditDelivery">
      <?= csrfField() ?>
      <input type="hidden" name="id" id="edit_delivery_id">
      <?php include __DIR__ . '/_delivery_form_fields.php'; ?>
      <div class="modal-admin-actions">
        <button type="submit" class="btn-admin-primary">Guardar</button>
        <button type="button" class="btn-admin-secondary" onclick="closeModal('modalEditDelivery')">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
const ALL_TOPICS = <?= json_encode(array_map(fn($t) => ['id'=>$t['id'],'title'=>$t['title'],'course_id'=>$t['course_id']], $topics)) ?>;

function openEditDelivery(d) {
  const f = document.getElementById('formEditDelivery');
  f.querySelector('[name=id]').value           = d.id;
  f.querySelector('[name=course_id]').value    = d.course_id;
  f.querySelector('[name=title]').value        = d.title;
  f.querySelector('[name=description]').value  = d.description || '';
  f.querySelector('[name=type]').value         = d.type;
  f.querySelector('[name=price]').value        = d.price;
  f.querySelector('[name=payment_type]').value = d.payment_type;
  f.querySelector('[name=sort_order]').value   = d.sort_order;
  f.querySelector('[name=notify_email]').checked    = d.notify_email == 1;
  f.querySelector('[name=notify_whatsapp]').checked = d.notify_whatsapp == 1;
  f.querySelector('[name=active]').checked     = d.active == 1;
  // Temas: desmarcar todos y luego marcar los de esta entrega
  // (los IDs vinculados los cargamos por AJAX o los pasamos en PHP)
  openModal('modalEditDelivery');
}

function loadEnrolled(deliveryId, title) {
  document.getElementById('enrolledTitle').textContent = 'Inscritos en: ' + title;
  document.getElementById('enrolledContent').innerHTML = '<p>Cargando…</p>';
  openModal('modalEnrolled');
  fetch('<?= BASE_URL ?>/admin/entregas/' + deliveryId + '/inscritos')
    .then(r => r.json())
    .then(rows => {
      if (!rows.length) { document.getElementById('enrolledContent').innerHTML = '<p>Ningún inscrito.</p>'; return; }
      let html = '<table class="admin-table"><thead><tr><th>Nombre</th><th>Email</th><th>Estado</th><th>Inscrito</th><th></th></tr></thead><tbody>';
      rows.forEach(r => {
        html += `<tr><td>${r.name} ${r.surnames}</td><td>${r.email}</td><td>${r.status}</td><td>${r.enrolled_at}</td>
          <td><form method="post" action="<?= BASE_URL ?>/admin/entregas/${deliveryId}/baja/${r.enrollment_id}" onsubmit="return confirm('Dar de baja?')">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <button class="btn-admin-sm btn-admin-delete">Baja</button></form></td></tr>`;
      });
      html += '</tbody></table>';
      document.getElementById('enrolledContent').innerHTML = html;
    });
}
</script>
