<?php
$metaTitle = htmlspecialchars($delivery['title']);
$robots    = 'noindex,nofollow';
include BASE_PATH . '/templates/layouts/base.php';
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

    <div class="delivery-header">
      <span class="badge badge-<?= $delivery['type'] ?>"><?= ucfirst($delivery['type']) ?></span>
      <h1><?= htmlspecialchars($delivery['title']) ?></h1>
      <p class="text-muted"><?= number_format((float)$delivery['price'],2,',','.') ?> €</p>
    </div>

    <?php if (!empty($delivery['description'])): ?>
    <div class="delivery-description richtext">
      <?= $delivery['description'] ?>
    </div>
    <?php endif; ?>

    <?php if ($isEnrolled): ?>
      <!-- PDF download -->
      <?php if ($delivery['type'] !== TYPE_MATRICULA && $delivery['pdf_filename']): ?>
      <div class="content-card">
        <h2><i data-lucide="file-text"></i> Temario en PDF</h2>
        <a href="<?= BASE_URL ?>/descargar-pdf/<?= $delivery['id'] ?>" class="btn btn-primary">
          <i data-lucide="download"></i> Descargar PDF
        </a>
      </div>
      <?php endif; ?>

      <!-- Exam -->
      <?php if ($delivery['exam_id'] && $exam): ?>
      <div class="content-card" id="examen">
        <h2><i data-lucide="file-check"></i> Examen</h2>
        <?php if ($attempt): ?>
          <div class="exam-result">
            <p>Ya realizaste este examen.</p>
            <p class="exam-score-big">Tu nota: <strong><?= number_format((float)$attempt['score'],1) ?></strong> / 10</p>
            <?php if ($attempt['passed']): ?>
              <p class="text-success"><i data-lucide="check-circle"></i> Aprobado</p>
            <?php else: ?>
              <p class="text-error"><i data-lucide="x-circle"></i> Suspenso</p>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <form action="<?= BASE_URL ?>/examen/enviar" method="POST" class="exam-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="delivery_id" value="<?= $delivery['id'] ?>">
            <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
            <?php foreach ($exam['questions'] as $qi => $q): ?>
            <div class="exam-question">
              <p class="question-text"><strong><?= $qi+1 ?>.</strong> <?= $q['question_text'] ?></p>
              <?php if (!empty($q['question_desc'])): ?>
                <div class="question-desc richtext"><?= $q['question_desc'] ?></div>
              <?php endif; ?>
              <div class="question-answers">
                <?php foreach ($q['answers'] as $ai => $a): ?>
                <label class="answer-label">
                  <input type="<?= $q['answer_type'] ?>" name="answers[<?= $q['id'] ?>]<?= $q['answer_type']==='checkbox' ? '[]' : '' ?>"
                         value="<?= $a['id'] ?>">
                  <?= htmlspecialchars($a['answer_text']) ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary">Enviar respuestas</button>
          </form>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    <?php else: ?>
      <!-- Not enrolled: show enroll CTA -->
      <div class="content-card enroll-cta">
        <h2>Inscríbete para acceder al contenido</h2>
        <?php if ($canEnroll): ?>
          <form action="<?= BASE_URL ?>/inscribir" method="POST">
            <?= Csrf::field() ?>
            <input type="hidden" name="delivery_id" value="<?= $delivery['id'] ?>">
            <input type="hidden" name="redirect" value="<?= BASE_URL ?>/entrega/<?= htmlspecialchars($delivery['slug']) ?>">
            <?php if ($delivery['payment_type'] === PAYMENT_ONLINE && $delivery['price'] > 0): ?>
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
          <p class="text-muted">Debes completar las entregas anteriores antes de inscribirte en ésta.</p>
          <a href="<?= BASE_URL ?>/dashboard" class="btn">Volver al dashboard</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>
