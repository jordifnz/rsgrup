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

    <!-- Cabecera -->
    <div class="delivery-header">
      <span class="badge badge-<?= $delivery['type'] ?>"><?= ucfirst($delivery['type']) ?></span>
      <h1><?= htmlspecialchars($delivery['title']) ?></h1>
      <?php if (!$isMatricula && (float)$delivery['price'] > 0): ?>
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
        <!-- Matrícula: solo mensaje de bienvenida -->
        <div class="content-card">
          <p style="color:var(--color-text-muted)">Ya estás matriculado/a. Accede al resto de entregas desde el <a href="<?= BASE_URL ?>/dashboard">dashboard</a>.</p>
        </div>

      <?php else: ?>
        <!-- Entrega / Práctica: PDF + Examen -->

        <?php if (!empty($delivery['pdf_file'])): ?>
        <div class="content-card" style="margin-bottom:var(--space-5)">
          <h2 style="margin-bottom:var(--space-3)"><i data-lucide="file-text"></i> Temario en PDF</h2>
          <a href="<?= BASE_URL ?>/descargar-pdf/<?= (int)$enrollment['id'] ?>" class="btn btn-primary">
            <i data-lucide="download"></i> Descargar PDF
          </a>
        </div>
        <?php endif; ?>

        <?php if ($delivery['exam_id'] && $exam): ?>
        <div class="content-card" id="examen">
          <h2 style="margin-bottom:var(--space-4)"><i data-lucide="file-check"></i> Examen: <?= htmlspecialchars($exam['title']) ?></h2>

          <?php if ($attempt): ?>
            <!-- Ya realizado -->
            <div class="exam-result">
              <p style="margin-bottom:var(--space-2)">Ya realizaste este examen.</p>
              <p class="exam-score-big" style="font-size:2rem;font-weight:700;margin-bottom:var(--space-2)">
                <?= number_format((float)$attempt['score'], 1) ?><span style="font-size:1rem;font-weight:400"> / 100</span>
              </p>
              <?php if ((float)$attempt['score'] >= 50): ?>
                <p class="text-success" style="display:flex;align-items:center;gap:.4rem">
                  <i data-lucide="check-circle"></i> Aprobado
                </p>
              <?php else: ?>
                <p class="text-danger" style="display:flex;align-items:center;gap:.4rem">
                  <i data-lucide="x-circle"></i> Suspenso
                </p>
              <?php endif; ?>
            </div>

          <?php elseif (!empty($exam['questions'])): ?>
            <!-- Examen pendiente -->
            <p style="color:var(--color-text-muted);margin-bottom:var(--space-4)">Tienes este examen pendiente de realizar.</p>
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
              <button type="submit" class="btn btn-primary">Enviar respuestas</button>
            </form>

          <?php else: ?>
            <p style="color:var(--color-text-muted)">El examen no tiene preguntas todavía.</p>
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
<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>
