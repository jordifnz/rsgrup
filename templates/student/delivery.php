<?php
$metaTitle = htmlspecialchars($delivery['title']);
$robots    = 'noindex,nofollow';
include BASE_PATH . '/templates/layouts/base.php';

$isMatricula = ($delivery['type'] === 'matricula');
?>
<section class="delivery-page">
  <div class="container container--narrow">

    <nav class="breadcrumb" aria-label="Ruta">
      <a href="<?= BASE_URL ?>/dashboard">Dashboard</a>
      <span aria-hidden="true">/</span>
      <span><?= htmlspecialchars($delivery['title']) ?></span>
    </nav>

    <?php if ($flash = getFlash()): ?>
      <div class="flash flash--<?= $flash['type'] ?>" role="alert"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Cabecera: precio solo si NO inscrito y no es matrícula -->
    <div class="delivery-header">
      <span class="badge badge-<?= $delivery['type'] ?>"><?= ucfirst($delivery['type']) ?></span>
      <h1><?= htmlspecialchars($delivery['title']) ?></h1>
      <?php if (!$isEnrolled && !$isMatricula && (float)$delivery['price'] > 0): ?>
        <p class="text-muted"><?= number_format((float)$delivery['price'], 2, ',', '.') ?> &euro;</p>
      <?php endif; ?>
    </div>

    <?php if ($isEnrolled): ?>

      <!-- === USUARIO INSCRITO === -->

      <?php if (!empty($delivery['description'])): ?>
      <div class="delivery-description richtext" style="margin-bottom:var(--space-6)">
        <?= $delivery['description'] ?>
      </div>
      <?php endif; ?>

      <?php if ($isMatricula): ?>
        <div class="content-card">
          <p style="color:var(--color-text-muted)">Ya estás matriculado/a. Accede al resto de entregas desde el <a href="<?= BASE_URL ?>/dashboard">dashboard</a>.</p>
        </div>

      <?php else: ?>

        <!-- PDF -->
        <?php if (!empty($delivery['pdf_file'])): ?>
        <div class="content-card" style="margin-bottom:var(--space-5)">
          <h2 style="margin-bottom:var(--space-3)"><i data-lucide="file-text"></i> Temario en PDF</h2>
          <a href="<?= BASE_URL ?>/descargar-pdf/<?= (int)$enrollment['id'] ?>" class="btn btn-primary">
            <i data-lucide="download"></i> Descargar PDF
          </a>
        </div>
        <?php endif; ?>

        <!-- Examen -->
        <?php if ($delivery['exam_id'] && $exam): ?>
        <div class="content-card" id="bloque-examen">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-3)">
            <h2 style="margin:0"><i data-lucide="file-check"></i> Examen</h2>

            <?php if ($attempt): ?>
              <!-- Ya realizado: mostrar resultado -->
              <div style="display:flex;align-items:center;gap:var(--space-3);flex-wrap:wrap">
                <span style="font-size:1.5rem;font-weight:700">
                  <?= number_format((float)$attempt['score'] / 10, 1) ?> <span style="font-size:1rem;font-weight:400;color:var(--color-text-muted)">/ 10</span>
                </span>
                <?php if ((float)$attempt['score'] >= 50): ?>
                  <span style="color:var(--color-success);display:flex;align-items:center;gap:.3rem"><i data-lucide="check-circle"></i> Aprobado</span>
                <?php else: ?>
                  <span style="color:var(--color-danger);display:flex;align-items:center;gap:.3rem"><i data-lucide="x-circle"></i> Suspenso</span>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <!-- Pendiente: botón para desplegar -->
              <button type="button" class="btn btn-primary" onclick="toggleExamen()" id="btn-abrir-examen">
                <i data-lucide="pencil"></i> Realizar examen
              </button>
            <?php endif; ?>
          </div>

          <?php if ($attempt): ?>
            <p style="margin-top:var(--space-3);color:var(--color-text-muted);font-size:var(--text-sm)">Ya realizaste este examen. La nota no puede modificarse.</p>
          <?php else: ?>
            <!-- Formulario oculto hasta pulsar el botón -->
            <div id="examen-form-wrap" style="display:none;margin-top:var(--space-5)">
              <?php if (!empty($exam['questions'])): ?>
              <form action="<?= BASE_URL ?>/examen/enviar" method="POST" class="exam-form">
                <?= Csrf::field() ?>
                <input type="hidden" name="exam_id" value="<?= (int)$exam['id'] ?>">
                <?php foreach ($exam['questions'] as $qi => $q): ?>
                <div class="exam-question" style="margin-bottom:var(--space-5)">
                  <p class="question-text"><strong><?= $qi + 1 ?>.</strong>
                    <?= htmlspecialchars($q['title'] ?? $q['question_text'] ?? '') ?>
                  </p>
                  <?php if (!empty($q['question_desc'])): ?>
                    <div class="question-desc richtext"><?= $q['question_desc'] ?></div>
                  <?php endif; ?>
                  <div class="question-answers" style="margin-top:var(--space-2)">
                    <?php foreach ($q['answers'] as $a): ?>
                    <label class="answer-label" style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;cursor:pointer">
                      <input type="<?= $q['answer_type'] ?>"
                             name="answers[<?= (int)$q['id'] ?>]<?= $q['answer_type'] === 'checkbox' ? '[]' : '' ?>"
                             value="<?= (int)$a['id'] ?>">
                      <?= htmlspecialchars($a['text'] ?? $a['answer_text'] ?? '') ?>
                    </label>
                    <?php endforeach; ?>
                  </div>
                </div>
                <?php endforeach; ?>
                <div style="margin-top:var(--space-4)">
                  <button type="submit" class="btn btn-primary">Enviar respuestas</button>
                  <button type="button" class="btn" onclick="toggleExamen()" style="margin-left:var(--space-2)">Cancelar</button>
                </div>
              </form>
              <?php else: ?>
                <p style="color:var(--color-text-muted)">El examen no tiene preguntas todavía.</p>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

      <?php endif; // end !isMatricula ?>

    <?php else: ?>

      <!-- === USUARIO NO INSCRITO === -->
      <div class="content-card enroll-cta">
        <h2 style="margin-bottom:var(--space-3)">Inscríbete para acceder al contenido</h2>
        <?php if ($canEnroll): ?>
          <form action="<?= BASE_URL ?>/inscribir" method="POST">
            <?= Csrf::field() ?>
            <input type="hidden" name="delivery_id" value="<?= (int)$delivery['id'] ?>">
            <?php if ($delivery['payment_type'] === 'online' && (float)$delivery['price'] > 0): ?>
              <p style="margin-bottom:var(--space-3);font-size:1.25rem;font-weight:600">
                <?= number_format((float)$delivery['price'], 2, ',', '.') ?> &euro;
              </p>
              <button type="submit" class="btn btn-primary">
                <i data-lucide="credit-card"></i> Inscribirme y pagar con PayPal
              </button>
            <?php else: ?>
              <button type="submit" class="btn btn-primary">
                <i data-lucide="check"></i> Inscribirme
              </button>
            <?php endif; ?>
          </form>
        <?php else: ?>
          <p style="color:var(--color-text-muted);margin-bottom:var(--space-4)"><?= htmlspecialchars($canEnrollReason) ?></p>
          <a href="<?= BASE_URL ?>/dashboard" class="btn">&larr; Volver al dashboard</a>
        <?php endif; ?>
      </div>

    <?php endif; ?>

  </div>
</section>

<script>
function toggleExamen() {
  var wrap = document.getElementById('examen-form-wrap');
  var btn  = document.getElementById('btn-abrir-examen');
  if (!wrap) return;
  var open = wrap.style.display !== 'none';
  wrap.style.display = open ? 'none' : 'block';
  if (btn) btn.textContent = open ? 'Realizar examen' : 'Ocultar examen';
  if (!open) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>
