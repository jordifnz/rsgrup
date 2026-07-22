<?php
include BASE_PATH . '/templates/student/layout.php';
?>

<div class="delivery-page">
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <div class="delivery-header">
    <h1><?= htmlspecialchars($delivery['title']) ?></h1>
    <?php if ($delivery['description']): ?>
      <div class="delivery-description"><?= $delivery['description'] ?></div>
    <?php endif; ?>
  </div>

  <?php if ($isEnrolled): ?>
    <!-- PDF -->
    <?php if (!empty($delivery['pdf_file'])): ?>
    <div class="card" style="margin-bottom:1.5rem">
      <h2>📄 Material de la entrega</h2>
      <a href="<?= BASE_URL ?>/descarga/pdf/<?= (int)$enrollment['id'] ?>" class="btn btn-secondary">
        Descargar PDF
      </a>
    </div>
    <?php endif; ?>

    <!-- EXAMEN -->
    <?php if ($exam): ?>
    <div class="card exam-card">
      <h2>📝 Examen: <?= htmlspecialchars($exam['title']) ?></h2>

      <?php if ($attempt): ?>
        <!-- Ya realizado -->
        <div class="alert alert-success">
          ✅ Ya has realizado este examen. Nota: <strong><?= htmlspecialchars((string)$attempt['score']) ?>%</strong>
        </div>

      <?php elseif (!$examAvailable['available']): ?>
        <!-- Fuera de la ventana de disponibilidad -->
        <div class="alert alert-warning" style="margin-bottom:1rem">
          🔒 <strong>Examen no disponible hoy.</strong><br>
          <?= htmlspecialchars($examAvailable['reason']) ?>
          <?php if ($examAvailable['next']): ?>
            <br>📅 Próxima fecha disponible: <strong><?= htmlspecialchars($examAvailable['next']) ?></strong>
          <?php endif; ?>
        </div>

      <?php else: ?>
        <!-- Disponible: mostrar formulario -->
        <form method="POST" action="<?= BASE_URL ?>/examen/enviar" id="exam-form">
          <?= \Csrf::field() ?>
          <input type="hidden" name="exam_id" value="<?= (int)$exam['id'] ?>">

          <?php foreach ($exam['questions'] as $qi => $q): ?>
          <div class="question-block">
            <p class="question-title"><strong><?= ($qi + 1) ?>. <?= htmlspecialchars($q['title']) ?></strong></p>
            <?php foreach ($q['answers'] as $a): ?>
            <label class="answer-option">
              <input type="<?= $q['answer_type'] === 'checkbox' ? 'checkbox' : 'radio' ?>"
                     name="answers[<?= (int)$q['id'] ?>]<?= $q['answer_type'] === 'checkbox' ? '[]' : '' ?>"
                     value="<?= (int)$a['id'] ?>">
              <?= htmlspecialchars($a['text']) ?>
            </label>
            <?php endforeach; ?>
          </div>
          <?php endforeach; ?>

          <div style="margin-top:1.5rem">
            <button type="submit" class="btn btn-primary"
                    onclick="return confirm('¿Seguro que quieres enviar el examen? Esta acción no se puede deshacer.')">
              Enviar examen
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- No inscrito -->
    <div class="card">
      <?php if ($canEnroll): ?>
        <p>Todavía no estás inscrito en esta entrega.</p>
        <a href="<?= BASE_URL ?>/inscribir/<?= (int)$delivery['id'] ?>" class="btn btn-primary">Inscribirme</a>
      <?php else: ?>
        <div class="alert alert-warning"><?= htmlspecialchars($canEnrollReason) ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php include BASE_PATH . '/templates/student/layout_close.php'; ?>
