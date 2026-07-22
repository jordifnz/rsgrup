<?php // Campos compartidos para crear/editar entrega ?>
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
  <label>Tipo</label>
  <select name="type">
    <option value="matricula">Matrícula</option>
    <option value="entrega">Entrega</option>
    <option value="practica">Práctica</option>
  </select>
</div>
<div class="form-row">
  <label>Precio (€)</label>
  <input type="number" name="price" step="0.01" min="0" value="0">
</div>
<div class="form-row">
  <label>Pago</label>
  <select name="payment_type">
    <option value="online">Online (PayPal)</option>
    <option value="presencial">Presencial</option>
  </select>
</div>
<div class="form-row">
  <label>Temas vinculados</label>
  <div class="topic-checkboxes">
    <?php foreach ($topics as $t): ?>
      <label class="checkbox-topic">
        <input type="checkbox" name="topic_ids[]" value="<?= $t['id'] ?>">
        <?= htmlspecialchars($t['title']) ?>
        <?php if ($t['course_title']): ?><small>(<?= htmlspecialchars($t['course_title']) ?>)</small><?php endif; ?>
      </label>
    <?php endforeach; ?>
  </div>
</div>
<div class="form-row form-row--inline">
  <label>Orden</label>
  <input type="number" name="sort_order" value="0" style="width:80px">
</div>
<div class="form-row form-row--inline">
  <label><input type="checkbox" name="notify_email" value="1"> Notificar email</label>
</div>
<div class="form-row form-row--inline">
  <label><input type="checkbox" name="notify_whatsapp" value="1"> Notificar WhatsApp</label>
</div>
<div class="form-row form-row--inline">
  <label><input type="checkbox" name="active" value="1" checked> Activo</label>
</div>
