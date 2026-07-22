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
      <h2>&#128196; Material de la entrega</h2>
      <a href="<?= BASE_URL ?>/descarga/pdf/<?= (int)$enrollment['id'] ?>" class="btn btn-secondary">
        Descargar PDF
      </a>
    </div>
    <?php endif; ?>

    <!-- EXAMEN -->
    <?php if ($exam): ?>
    <?php
      $passingScore   = ExamModel::passingScore();
      $lastPassed     = $attempt && ExamModel::isPassing((float)$attempt['score']);
      $lastFailed     = $attempt && !ExamModel::isPassing((float)$attempt['score']);
      $exhausted      = $attemptCount >= 2;
    ?>
    <div class="card exam-card">
      <h2>&#128221; Examen: <?= htmlspecialchars($exam['title']) ?></h2>

      <?php if ($lastPassed): ?>
        <!-- APROBADO -->
        <div class="alert alert-success">
          &#9989; <strong>Examen aprobado.</strong> Nota obtenida: <strong><?= htmlspecialchars((string)$attempt['score']) ?>%</strong>
          (m&iacute;nimo para aprobar: <?= (int)$passingScore ?>%).
        </div>

      <?php elseif ($lastFailed && $exhausted): ?>
        <!-- SUSPENDIDO SIN MÁS INTENTOS -->
        <div class="alert alert-error">
          &#10060; <strong>Has agotado los 2 intentos permitidos.</strong>
          Tu última nota fue <strong><?= htmlspecialchars((string)$attempt['score']) ?>%</strong>
          (m&iacute;nimo para aprobar: <?= (int)$passingScore ?>%).
        </div>
        <?php if (count($attempts) > 1): ?>
        <details style="margin-top:.75rem;font-size:.9em;color:var(--color-text-muted,#666)">
          <summary>Ver historial de intentos</summary>
          <ul style="margin-top:.5rem;padding-left:1rem">
            <?php foreach ($attempts as $i => $at): ?>
            <li>Intento <?= $i + 1 ?>: <strong><?= htmlspecialchars((string)$at['score']) ?>%</strong>
              &mdash; <?= htmlspecialchars((new DateTimeImmutable($at['created_at']))->format('d/m/Y H:i')) ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </details>
        <?php endif; ?>

      <?php elseif ($lastFailed && $canRetry): ?>
        <!-- SUSPENDIDO CON SEGUNDA OPORTUNIDAD -->
        <div class="alert alert-warning" style="margin-bottom:1rem">
          &#9888;&#65039; <strong>Has suspendido el primer intento</strong>
          con un <strong><?= htmlspecialchars((string)$attempt['score']) ?>%</strong>
          (m&iacute;nimo para aprobar: <?= (int)$passingScore ?>%).<br>
          Tienes una <strong>segunda oportunidad</strong> para realizarlo.
        </div>

        <?php if (!$examAvailable['available']): ?>
          <div class="alert alert-warning" style="margin-bottom:1rem">
            &#128274; <strong>Examen no disponible hoy.</strong><br>
            <?= htmlspecialchars($examAvailable['reason']) ?>
            <?php if ($examAvailable['next']): ?>
              <br>&#128197; Pr&oacute;xima fecha disponible: <strong><?= htmlspecialchars($examAvailable['next']) ?></strong>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <!-- Formulario segundo intento -->
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
                      onclick="return confirm('&#191;Seguro que quieres enviar el examen? Esta es tu segunda y última oportunidad.')">
                Enviar segunda oportunidad
              </button>
            </div>
          </form>
        <?php endif; ?>

      <?php elseif (!$attempt && !$examAvailable['available']): ?>
        <!-- SIN INTENTOS, fuera de ventana -->
        <div class="alert alert-warning" style="margin-bottom:1rem">
          &#128274; <strong>Examen no disponible hoy.</strong><br>
          <?= htmlspecialchars($examAvailable['reason']) ?>
          <?php if ($examAvailable['next']): ?>
            <br>&#128197; Pr&oacute;xima fecha disponible: <strong><?= htmlspecialchars($examAvailable['next']) ?></strong>
          <?php endif; ?>
        </div>

      <?php elseif (!$attempt): ?>
        <!-- SIN INTENTOS, disponible: mostrar formulario -->
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
                    onclick="return confirm('&#191;Seguro que quieres enviar el examen? Esta acción no se puede deshacer.')">
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
        <p>Todav&iacute;a no est&aacute;s inscrito en esta entrega.</p>
        <a href="<?= BASE_URL ?>/inscribir/<?= (int)$delivery['id'] ?>" class="btn btn-primary">Inscribirme</a>
      <?php else: ?>
        <div class="alert alert-warning"><?= htmlspecialchars($canEnrollReason) ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php include BASE_PATH . '/templates/student/layout_close.php'; ?>
