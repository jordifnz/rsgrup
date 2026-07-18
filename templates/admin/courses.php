<?php
$metaTitle = 'Cursos';
include BASE_PATH . '/templates/admin/layout_admin.php';
?>
<div class="section-header">
  <h1>Cursos</h1>
  <button class="btn btn-primary" onclick="openModal('modal-course')">+ Nuevo curso</button>
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
      <button class="btn btn-sm" onclick="editCourse(<?= htmlspecialchars(json_encode($c)) ?>)">Editar</button>
      <form action="<?= BASE_URL ?>/admin/cursos/<?= $c['id'] ?>/eliminar" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
        <?= Csrf::field() ?><button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<div id="modal-course" class="modal" hidden>
  <div class="modal-backdrop" onclick="closeModal('modal-course')"></div>
  <div class="modal-box">
    <h2>Curso</h2>
    <form action="<?= BASE_URL ?>/admin/cursos/guardar" method="POST" class="form">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" id="course-id">
      <div class="form-group"><label>Título *</label><input type="text" name="title" id="course-title" required></div>
      <div class="form-group"><label>Descripción</label><textarea name="description" id="course-desc" class="wysiwyg-editor" rows="4"></textarea></div>
      <label class="checkbox-label"><input type="checkbox" name="active" id="course-active" value="1" checked> Activo</label>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeModal('modal-course')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>
<script>
function editCourse(c){
  document.getElementById('course-id').value=c.id||'';
  document.getElementById('course-title').value=c.title||'';
  document.getElementById('course-active').checked=!!c.active;
  if(window.tinymce&&tinymce.get('course-desc')) tinymce.get('course-desc').setContent(c.description||'');
  openModal('modal-course');
}
</script>
<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>