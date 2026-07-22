<?php // Campos compartidos para nuevo/editar tema ?>
<div class="form-row">
  <label>Curso</label>
  <select name="course_id" required>
    <option value="">-- Selecciona curso --</option>
    <?php foreach ($courses as $c): ?>
      <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div class="form-row">
  <label>Título</label>
  <input type="text" name="title" required maxlength="255">
</div>
<div class="form-row">
  <label>Descripción</label>
  <textarea name="description" rows="3"></textarea>
</div>
<div class="form-row">
  <label>Exámen asociado</label>
  <select name="exam_id">
    <option value="">-- Sin exámen --</option>
    <?php foreach ($exams as $e): ?>
      <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['title']) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div class="form-row">
  <label>PDF del tema</label>
  <p class="existing-pdf-label" style="font-size:.85em;color:var(--color-text-muted)"></p>
  <input type="hidden" name="existing_pdf" value="">
  <input type="file" name="pdf_file" accept=".pdf">
</div>
<div class="form-row form-row--inline">
  <label>Orden</label>
  <input type="number" name="sort_order" value="0" style="width:80px">
</div>
<div class="form-row form-row--inline">
  <label><input type="checkbox" name="active" value="1" checked> Activo</label>
</div>
