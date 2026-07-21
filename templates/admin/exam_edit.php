<?php
// Called by AdminController::examEditor
requireAdmin();
$metaTitle  = ($exam ?? null) ? 'Editar examen' : 'Nuevo examen';
$examData   = ($exam ?? null) ? $exam : [];
$questions  = $examData['questions'] ?? [];
$deliveries = $deliveries ?? [];
?>
<?php include BASE_PATH . '/templates/admin/layout_admin.php'; ?>

<div class="section-header">
  <h1><?= ($exam ?? null) ? 'Editar examen' : 'Nuevo examen' ?></h1>
  <a href="<?= BASE_URL ?>/admin/examenes" class="btn">&larr; Volver</a>
</div>

<form id="exam-builder-form"
      action="<?= BASE_URL ?>/admin/examenes/guardar"
      method="POST">

  <?= Csrf::field() ?>
  <?php if (!empty($examData['id'])): ?>
    <input type="hidden" name="id" value="<?= (int)$examData['id'] ?>">
  <?php endif; ?>

  <!-- Datos generales -->
  <div class="form-grid" style="margin-bottom:var(--space-6)">
    <div class="form-group">
      <label for="exam-title">T&iacute;tulo del examen *</label>
      <input type="text" id="exam-title" name="title"
             value="<?= htmlspecialchars($examData['title'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label for="exam-delivery">Entrega vinculada</label>
      <select id="exam-delivery" name="delivery_id">
        <option value="">&mdash; Sin vincular &mdash;</option>
        <?php foreach ($deliveries as $d): ?>
          <option value="<?= $d['id'] ?>"
            <?= (($examData['delivery_id'] ?? null) == $d['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group form-group--full">
      <label for="exam-description">Descripci&oacute;n (opcional)</label>
      <textarea id="exam-description" name="description" rows="3"
                class="wysiwyg-exam"><?= htmlspecialchars($examData['description'] ?? '') ?></textarea>
    </div>
  </div>

  <!-- Bloque de preguntas -->
  <div style="display:flex;align-items:center;gap:var(--space-4);margin-bottom:var(--space-4)">
    <h2 class="h3" style="margin:0">Preguntas</h2>
    <button type="button" id="add-question-btn" class="btn btn-primary">+ A&ntilde;adir pregunta</button>
  </div>

  <div id="questions-container">
    <?php foreach ($questions as $qi => $q): ?>
    <!-- pregunta existente renderizada por PHP -->
    <div class="question-block" data-index="<?= $qi ?>" style="border:1px solid var(--color-border);border-radius:var(--radius-md);padding:var(--space-5);margin-bottom:var(--space-5);background:var(--color-surface)">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-3)">
        <strong style="font-size:var(--text-sm)">Pregunta #<span class="q-num"><?= $qi + 1 ?></span></strong>
        <button type="button" class="btn btn-sm btn-danger remove-question-btn">Eliminar pregunta</button>
      </div>
      <div class="form-group">
        <label>Enunciado *</label>
        <input type="text" name="questions[<?= $qi ?>][title]"
               value="<?= htmlspecialchars($q['title'] ?? '') ?>" required
               placeholder="Texto de la pregunta">
      </div>
      <div class="form-group">
        <label>Tipo de respuesta</label>
        <select name="questions[<?= $qi ?>][answer_type]" class="answer-type-select">
          <option value="radio"   <?= ($q['answer_type'] ?? 'radio') === 'radio'    ? 'selected' : '' ?>>Una respuesta (radio)</option>
          <option value="checkbox" <?= ($q['answer_type'] ?? 'radio') === 'checkbox' ? 'selected' : '' ?>>M&uacute;ltiple (checkbox)</option>
        </select>
      </div>
      <div class="answers-wrapper" style="margin-top:var(--space-3)">
        <?php foreach (($q['answers'] ?? []) as $ai => $a): ?>
        <div class="answer-row" style="display:flex;gap:var(--space-3);align-items:center;margin-bottom:var(--space-2)">
          <input type="text" name="questions[<?= $qi ?>][answers][<?= $ai ?>][text]"
                 value="<?= htmlspecialchars($a['text'] ?? '') ?>"
                 placeholder="Texto de la respuesta" style="flex:1" required>
          <label style="display:flex;align-items:center;gap:4px;white-space:nowrap;font-size:var(--text-sm)">
            <input type="checkbox" name="questions[<?= $qi ?>][answers][<?= $ai ?>][is_correct]" value="1"
                   <?= !empty($a['is_correct']) ? 'checked' : '' ?>> Correcta
          </label>
          <button type="button" class="btn btn-sm remove-answer-btn">&times;</button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-sm add-answer-btn" style="margin-top:var(--space-2)">+ A&ntilde;adir respuesta</button>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="display:flex;gap:var(--space-3);justify-content:flex-end;margin-top:var(--space-6)">
    <a href="<?= BASE_URL ?>/admin/examenes" class="btn">Cancelar</a>
    <button type="submit" class="btn btn-primary">Guardar examen</button>
  </div>
</form>

<script>
(function () {
  'use strict';

  // Contador global de preguntas (continúa desde las ya renderizadas por PHP)
  var qCount = document.querySelectorAll('.question-block').length;

  /* ── Plantilla HTML de una nueva pregunta ────────────────────────── */
  function questionHTML(qi) {
    return [
      '<div class="question-block" data-index="' + qi + '" style="border:1px solid var(--color-border);border-radius:var(--radius-md);padding:var(--space-5);margin-bottom:var(--space-5);background:var(--color-surface)">',
        '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-3)">',
          '<strong style="font-size:var(--text-sm)">Pregunta #<span class="q-num">' + (qi + 1) + '</span></strong>',
          '<button type="button" class="btn btn-sm btn-danger remove-question-btn">Eliminar pregunta</button>',
        '</div>',
        '<div class="form-group">',
          '<label>Enunciado *</label>',
          '<input type="text" name="questions[' + qi + '][title]" required placeholder="Texto de la pregunta">',
        '</div>',
        '<div class="form-group">',
          '<label>Tipo de respuesta</label>',
          '<select name="questions[' + qi + '][answer_type]" class="answer-type-select">',
            '<option value="radio">Una respuesta (radio)</option>',
            '<option value="checkbox">M&uacute;ltiple (checkbox)</option>',
          '</select>',
        '</div>',
        '<div class="answers-wrapper" style="margin-top:var(--space-3)"></div>',
        '<button type="button" class="btn btn-sm add-answer-btn" style="margin-top:var(--space-2)">+ A&ntilde;adir respuesta</button>',
      '</div>'
    ].join('');
  }

  /* ── Plantilla HTML de una nueva respuesta ───────────────────────── */
  function answerHTML(qi, ai) {
    return [
      '<div class="answer-row" style="display:flex;gap:var(--space-3);align-items:center;margin-bottom:var(--space-2)">',
        '<input type="text" name="questions[' + qi + '][answers][' + ai + '][text]"',
               ' placeholder="Texto de la respuesta" style="flex:1" required>',
        '<label style="display:flex;align-items:center;gap:4px;white-space:nowrap;font-size:var(--text-sm)">',
          '<input type="checkbox"',
                 ' name="questions[' + qi + '][answers][' + ai + '][is_correct]"',
                 ' value="1"> Correcta',
        '</label>',
        '<button type="button" class="btn btn-sm remove-answer-btn">&times;</button>',
      '</div>'
    ].join('');
  }

  /* ── Renumera visualmente las preguntas ──────────────────────────── */
  function renumberQuestions() {
    document.querySelectorAll('#questions-container .question-block').forEach(function (block, idx) {
      var numEl = block.querySelector('.q-num');
      if (numEl) numEl.textContent = idx + 1;
    });
  }

  /* ── Añadir pregunta ─────────────────────────────────────────────── */
  document.getElementById('add-question-btn').addEventListener('click', function () {
    var container = document.getElementById('questions-container');
    var div       = document.createElement('div');
    div.innerHTML = questionHTML(qCount);
    container.appendChild(div.firstElementChild);
    // Añadir 2 respuestas vacías por defecto
    var newBlock   = container.lastElementChild;
    var wrapper    = newBlock.querySelector('.answers-wrapper');
    addAnswerToWrapper(wrapper, qCount, 0);
    addAnswerToWrapper(wrapper, qCount, 1);
    qCount++;
    renumberQuestions();
  });

  /* ── Delegación de eventos en el contenedor ──────────────────────── */
  document.getElementById('questions-container').addEventListener('click', function (e) {

    // Eliminar pregunta
    if (e.target.classList.contains('remove-question-btn')) {
      var block = e.target.closest('.question-block');
      if (block) {
        if (document.querySelectorAll('.question-block').length === 1) {
          alert('El examen debe tener al menos una pregunta.');
          return;
        }
        block.remove();
        renumberQuestions();
      }
      return;
    }

    // Añadir respuesta
    if (e.target.classList.contains('add-answer-btn')) {
      var block   = e.target.closest('.question-block');
      var qi      = parseInt(block.dataset.index, 10);
      var wrapper = block.querySelector('.answers-wrapper');
      var ai      = wrapper.querySelectorAll('.answer-row').length;
      addAnswerToWrapper(wrapper, qi, ai);
      return;
    }

    // Eliminar respuesta
    if (e.target.classList.contains('remove-answer-btn')) {
      var wrapper = e.target.closest('.answers-wrapper');
      if (wrapper && wrapper.querySelectorAll('.answer-row').length <= 1) {
        alert('Debe haber al menos una respuesta.');
        return;
      }
      var row = e.target.closest('.answer-row');
      if (row) row.remove();
      return;
    }
  });

  /* ── Helper: añadir fila de respuesta al wrapper ─────────────────── */
  function addAnswerToWrapper(wrapper, qi, ai) {
    var tmp = document.createElement('div');
    tmp.innerHTML = answerHTML(qi, ai);
    wrapper.appendChild(tmp.firstElementChild);
  }

  /* ── TinyMCE en el textarea de descripción (ya visible, no modal) ── */
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof tinymce !== 'undefined') {
      var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      tinymce.init({
        selector    : '#exam-description',
        language    : 'es',
        language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@23.10.9/langs6/es.js',
        plugins     : 'lists link code',
        toolbar     : 'undo redo | formatselect | bold italic | bullist numlist | link | code',
        menubar     : false,
        branding    : false,
        promotion   : false,
        height      : 200,
        skin        : isDark ? 'oxide-dark' : 'oxide',
        content_css : isDark ? 'dark'       : 'default'
      });
    }
  });

  /* ── Validación antes de enviar: mínimo 1 pregunta y 1 respuesta ─── */
  document.getElementById('exam-builder-form').addEventListener('submit', function (e) {
    var blocks = document.querySelectorAll('#questions-container .question-block');
    if (blocks.length === 0) {
      e.preventDefault();
      alert('Añade al menos una pregunta antes de guardar.');
      return;
    }
    var ok = true;
    blocks.forEach(function (block) {
      if (block.querySelectorAll('.answer-row').length === 0) {
        ok = false;
      }
    });
    if (!ok) {
      e.preventDefault();
      alert('Cada pregunta debe tener al menos una respuesta.');
    }
    // Volcar TinyMCE al textarea antes de enviar
    if (window.tinymce) {
      var ed = tinymce.get('exam-description');
      if (ed) ed.save();
    }
  });

})();
</script>

<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>
