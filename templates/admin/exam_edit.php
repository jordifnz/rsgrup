<?php
// Called by AdminController::examEditor
requireAdmin();
$metaTitle  = ($exam ?? null) ? 'Editar exámen' : 'Nuevo exámen';
$examData   = ($exam ?? null) ? $exam : [];
$questions  = $examData['questions'] ?? [];
$topicsList = $topics ?? [];
$currentTopicId = null;
if ($examData) {
    $linkedTopic = Database::fetch('SELECT id FROM rsgrup_topics WHERE exam_id=?', [$examData['id']]);
    $currentTopicId = $linkedTopic['id'] ?? null;
}
?>
<?php include BASE_PATH . '/templates/admin/layout_admin.php'; ?>

<div class="admin-page-header">
  <div class="admin-page-header-left">
    <h1><?= $metaTitle ?></h1>
  </div>
  <a href="<?= BASE_URL ?>/admin/examenes" class="btn-admin-secondary">← Volver</a>
</div>

<form method="post" action="<?= BASE_URL ?>/admin/examenes/guardar" id="examForm">
  <?= csrfField() ?>
  <input type="hidden" name="id" value="<?= (int)($examData['id'] ?? 0) ?>">

  <div class="admin-card">
    <div class="form-row">
      <label>Título del exámen</label>
      <input type="text" name="title" required value="<?= htmlspecialchars($examData['title'] ?? '') ?>">
    </div>
    <div class="form-row">
      <label>Descripción</label>
      <textarea name="description" rows="3"><?= htmlspecialchars($examData['description'] ?? '') ?></textarea>
    </div>
    <div class="form-row">
      <label>Tema vinculado</label>
      <select name="topic_id">
        <option value="">-- Sin tema --</option>
        <?php foreach ($topicsList as $t): ?>
          <option value="<?= $t['id'] ?>" <?= $currentTopicId == $t['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <small class="help-text">El exámen quedará vinculado al tema seleccionado.</small>
    </div>
  </div>

  <div class="admin-card" style="margin-top:var(--space-6)">
    <h2>Preguntas</h2>
    <div id="questions-container">
      <?php foreach ($questions as $qi => $q): ?>
        <?php include __DIR__ . '/_question_block.php'; ?>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn-admin-secondary" onclick="addQuestion()">+ Añadir pregunta</button>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-admin-primary">Guardar exámen</button>
  </div>
</form>

<script>
let qIdx = <?= count($questions) ?>;

function addQuestion() {
  const container = document.getElementById('questions-container');
  const i = qIdx++;
  const block = document.createElement('div');
  block.className = 'question-block';
  block.innerHTML = `
    <div class="question-header">
      <span>Pregunta #${i+1}</span>
      <button type="button" class="btn-admin-sm btn-admin-delete" onclick="this.closest('.question-block').remove()">× Eliminar</button>
    </div>
    <div class="form-row">
      <label>Enunciado</label>
      <textarea name="questions[${i}][text]" required rows="2"></textarea>
    </div>
    <div class="form-row">
      <label>Tipo</label>
      <select name="questions[${i}][type]" onchange="toggleOptions(this,${i})">
        <option value="single">Una respuesta</option>
        <option value="multiple">Múltiple respuesta</option>
        <option value="text">Respuesta libre</option>
      </select>
    </div>
    <div class="options-container" id="opts_${i}">
      ${optionHtml(i, 0)}
      ${optionHtml(i, 1)}
    </div>
    <button type="button" class="btn-admin-sm" onclick="addOption(${i})">+ Opción</button>
  `;
  container.appendChild(block);
}

function optionHtml(qi, oi) {
  return `<div class="option-row">
    <input type="text" name="questions[${qi}][options][${oi}][text]" placeholder="Opción ${oi+1}">
    <label><input type="checkbox" name="questions[${qi}][options][${oi}][correct]" value="1"> Correcta</label>
    <button type="button" class="btn-icon" onclick="this.closest('.option-row').remove()">×</button>
  </div>`;
}

let optCounters = {};
function addOption(qi) {
  optCounters[qi] = (optCounters[qi] ?? 2) + 1;
  document.getElementById('opts_' + qi).insertAdjacentHTML('beforeend', optionHtml(qi, optCounters[qi]));
}

function toggleOptions(sel, qi) {
  const container = document.getElementById('opts_' + qi);
  container.style.display = sel.value === 'text' ? 'none' : '';
}
</script>
