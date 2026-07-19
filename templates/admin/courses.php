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
      <input type="hidden" name="id" id="course-id">
      <div class="form-group">
        <label for="course-title-input">Título *</label>
        <input type="text" name="title" id="course-title-input" required>
      </div>
      <div class="form-group">
        <label for="course-desc">Descripción</label>
        <textarea name="description" id="course-desc" rows="6"></textarea>
      </div>
      <label class="checkbox-label">
        <input type="checkbox" name="active" id="course-active" value="1" checked> Activo
      </label>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeModal('modal-course')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
// Datos del curso pendientes de cargar en TinyMCE (si aún no estaba listo)
var _pendingCourse = null;

function openCourseModal(course) {
  var isNew = !course || !course.id;

  document.getElementById('modal-course-title').textContent = isNew ? 'Nuevo Curso' : 'Editar Curso';
  document.getElementById('course-id').value            = isNew ? '' : course.id;
  document.getElementById('course-title-input').value   = isNew ? '' : (course.title || '');
  document.getElementById('course-active').checked      = isNew ? true : !!parseInt(course.active);

  var descContent = isNew ? '' : (course.description || '');
  var editor = window.tinymce ? tinymce.get('course-desc') : null;

  if (editor) {
    // TinyMCE ya inicializado: cargar contenido directamente
    editor.setContent(descContent);
  } else {
    // TinyMCE aún no listo: escribir en el textarea y guardar para el callback
    document.getElementById('course-desc').value = descContent;
    _pendingCourse = { content: descContent, editorId: 'course-desc' };
  }

  openModal('modal-course');
}

// Callback que se llama cuando TinyMCE termina de inicializar un editor
function onTinyMceReady(editorId) {
  if (_pendingCourse && _pendingCourse.editorId === editorId) {
    var ed = tinymce.get(editorId);
    if (ed) {
      ed.setContent(_pendingCourse.content);
      _pendingCourse = null;
    }
  }
}
</script>
<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>
