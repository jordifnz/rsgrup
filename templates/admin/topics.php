<?php
// AdminController::topics
requireAdmin();
$metaTitle = 'Temas';
?>
<?php include BASE_PATH . '/templates/admin/layout_admin.php'; ?>

<div class="admin-page-header">
  <div class="admin-page-header-left">
    <h1>Temas</h1>
    <p>Administra los temas de cada curso. Cada tema tiene un PDF y puede tener un exámen asociado.</p>
  </div>
  <button class="btn-admin-primary" onclick="openModal('modalCreateTopic')">+ Nuevo tema</button>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="alert-admin alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Orden</th>
        <th>Curso</th>
        <th>Título</th>
        <th>PDF</th>
        <th>Exámen</th>
        <th>Activo</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($topics as $t): ?>
      <tr>
        <td><?= $t['id'] ?></td>
        <td><?= $t['sort_order'] ?></td>
        <td><?= htmlspecialchars($t['course_title'] ?? '') ?></td>
        <td><?= htmlspecialchars($t['title']) ?></td>
        <td><?= $t['pdf_file'] ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-neutral">No</span>' ?></td>
        <td><?= $t['exam_title'] ? htmlspecialchars($t['exam_title']) : '<span class="text-muted">—</span>' ?></td>
        <td><?= $t['active'] ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-error">No</span>' ?></td>
        <td class="td-actions">
          <button class="btn-admin-sm btn-admin-edit"
            onclick="openEditTopic(<?= htmlspecialchars(json_encode($t)) ?>)">✏️ Editar</button>
          <form method="post" action="<?= BASE_URL ?>/admin/temas/<?= $t['id'] ?>/borrar" style="display:inline"
                onsubmit="return confirm('Borrar tema?')">
            <?= csrfField() ?>
            <button class="btn-admin-sm btn-admin-delete" type="submit">🗑 Borrar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal crear tema -->
<div id="modalCreateTopic" class="modal-admin" role="dialog" aria-modal="true" aria-labelledby="modalCreateTopicLabel" style="display:none">
  <div class="modal-admin-box">
    <h2 id="modalCreateTopicLabel">Nuevo Tema</h2>
    <form method="post" action="<?= BASE_URL ?>/admin/temas/guardar" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="id" value="0">
      <?php include __DIR__ . '/_topic_form_fields.php'; ?>
      <div class="modal-admin-actions">
        <button type="submit" class="btn-admin-primary">Guardar</button>
        <button type="button" class="btn-admin-secondary" onclick="closeModal('modalCreateTopic')">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal editar tema -->
<div id="modalEditTopic" class="modal-admin" role="dialog" aria-modal="true" aria-labelledby="modalEditTopicLabel" style="display:none">
  <div class="modal-admin-box">
    <h2 id="modalEditTopicLabel">Editar Tema</h2>
    <form method="post" action="<?= BASE_URL ?>/admin/temas/guardar" enctype="multipart/form-data" id="formEditTopic">
      <?= csrfField() ?>
      <input type="hidden" name="id" id="edit_topic_id">
      <?php include __DIR__ . '/_topic_form_fields.php'; ?>
      <div class="modal-admin-actions">
        <button type="submit" class="btn-admin-primary">Guardar</button>
        <button type="button" class="btn-admin-secondary" onclick="closeModal('modalEditTopic')">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditTopic(t) {
  const f = document.getElementById('formEditTopic');
  f.querySelector('[name=id]').value           = t.id;
  f.querySelector('[name=course_id]').value    = t.course_id;
  f.querySelector('[name=title]').value        = t.title;
  f.querySelector('[name=description]').value  = t.description || '';
  f.querySelector('[name=exam_id]').value      = t.exam_id || '';
  f.querySelector('[name=sort_order]').value   = t.sort_order;
  f.querySelector('[name=active]').checked     = t.active == 1;
  if (t.pdf_file) {
    const ep = f.querySelector('[name=existing_pdf]');
    if(ep) ep.value = t.pdf_file;
    const label = f.querySelector('.existing-pdf-label');
    if(label) label.textContent = 'Archivo actual: ' + t.pdf_file;
  }
  openModal('modalEditTopic');
}
</script>
