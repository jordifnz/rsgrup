<?php
// Called by AdminController::newExam and AdminController::editExam
requireAdmin();
$metaTitle = $exam ? 'Editar examen' : 'Nuevo examen';
?>
<?php include BASE_PATH.'/templates/admin/layout_admin.php'; ?>
<div class="section-header">
  <h1><?= $exam ? 'Editar examen' : 'Nuevo examen' ?></h1>
  <a href="<?= BASE_URL ?>/admin/examenes" class="btn">← Volver</a>
</div>

<form id="exam-builder-form"
      action="<?= BASE_URL ?>/admin/examenes/guardar" method="POST"
      data-initial-questions="0"
      data-questions="<?= $exam ? htmlspecialchars(json_encode($exam['questions']??[])) : '' ?>">
  <?= Csrf::field() ?>
  <?php if ($exam): ?><input type="hidden" name="id" value="<?= $exam['id'] ?>"><?php endif; ?>

  <div class="form-grid" style="margin-bottom:var(--space-4)">
    <div class="form-group">
      <label>Título del examen *</label>
      <input type="text" name="title" value="<?= htmlspecialchars($exam['title']??'') ?>" required>
    </div>
    <div class="form-group">
      <label>Entrega vinculada</label>
      <select name="delivery_id">
        <option value="">— Sin vincular —</option>
        <?php foreach ($deliveries as $d): ?>
        <option value="<?= $d['id'] ?>" <?= ($exam['delivery_id']??null)==$d['id']?'selected':'' ?>>
          <?= htmlspecialchars($d['title']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group form-group--full">
      <label>Descripción del examen</label>
      <textarea name="description" class="wysiwyg-editor" rows="3"><?= htmlspecialchars($exam['description']??'') ?></textarea>
    </div>
  </div>

  <h2 class="h3" style="margin-bottom:var(--space-4)">Preguntas</h2>
  <div id="questions-container"></div>

  <button type="button" id="add-question-btn" class="btn" style="margin-bottom:var(--space-6)">+ Añadir pregunta</button>

  <div style="display:flex;gap:var(--space-3);justify-content:flex-end">
    <a href="<?= BASE_URL ?>/admin/examenes" class="btn">Cancelar</a>
    <button type="submit" class="btn btn-primary">Guardar examen</button>
  </div>
</form>

<!-- TinyMCE CDN -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<?php include BASE_PATH.'/templates/admin/layout_admin_close.php'; ?>
