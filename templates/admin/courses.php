<?php
$metaTitle = 'Cursos';
include BASE_PATH . '/templates/admin/layout_admin.php';
?>
<div class="section-header">
  <h1>Cursos</h1>
  <button class="btn btn-primary" onclick="openCourseModal(null)">+ Nuevo curso</button>
</div>
<table class="data-table">
  <thead><tr><th>ID</th><th>Título</th><th>Slug</th><th>Activo</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($courses as $c): ?>
  <tr>
    <td><?= $c['id'] ?></td>
    <td><?= htmlspecialchars($c['title']) ?></td>
    <td><code><?= htmlspecialchars($c['slug']) ?></code></td>
    <td><?= $c['active'] ? '✅' : '❌' ?></td>
    <td class="actions">
      <button class="btn btn-sm" onclick="openCourseModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)">Editar</button>
      <form action="<?= BASE_URL ?>/admin/cursos/<?= $c['id'] ?>/eliminar" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
        <?= Csrf::field() ?><button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- Modal Curso -->
<div id="modal-course" class="modal" hidden>
  <div class="modal-backdrop" onclick="closeModal('modal-course')"></div>
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('modal-course')" aria-label="Cerrar">&times;</button>
    <h2 id="modal-course-title">Curso</h2>
    <form action="<?= BASE_URL ?>/admin/cursos/guardar" method="POST" class="form">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" id="crs-id">
      <div class="form-group">
        <label for="crs-title">Título *</label>
        <input type="text" name="title" id="crs-title" required>
      </div>
      <div class="form-group">
        <label for="crs-desc">Descripción</label>
        <textarea name="description" id="crs-desc" class="wysiwyg-editor" rows="6"></textarea>
      </div>
      <label class="checkbox-label">
        <input type="checkbox" name="active" id="crs-active" value="1" checked> Activo
      </label>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeModal('modal-course')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
function openCourseModal(c) {
  var isNew = !c || !c.id;

  // 1. Rellenar campos simples ANTES de abrir el modal
  document.getElementById('modal-course-title').textContent = isNew ? 'Nuevo Curso' : 'Editar Curso';
  document.getElementById('crs-id').value             = isNew ? '' : (c.id    || '');
  document.getElementById('crs-title').value          = isNew ? '' : (c.title || '');
  document.getElementById('crs-active').checked       = isNew ? true : (parseInt(c.active, 10) === 1);

  var descContent = isNew ? '' : (c.description || '');

  // 2. Abrir el modal (quita [hidden], hace el textarea visible)
  openModal('modal-course');

  // 3. Inicializar TinyMCE AHORA que el elemento es visible, pasando el contenido
  window.initEditorInModal('crs-desc', descContent);
}
</script>
<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>
