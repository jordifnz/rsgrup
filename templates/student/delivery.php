<?php
$metaTitle = htmlspecialchars($delivery['title']);
$robots = 'noindex,nofollow';
include BASE_PATH . '/templates/layouts/base.php';
?>
<section class="delivery-section">
  <div class="container container--narrow">
    <nav class="breadcrumb" aria-label="Ruta de navegación">
      <a href="<?= BASE_URL ?>/dashboard">Mi formación</a>
      <span aria-hidden="true">/</span>
      <span><?= htmlspecialchars($delivery['title']) ?></span>
    </nav>

    <div class="delivery-hero">
      <span class="badge badge-<?= $delivery['type'] ?>"><?= ucfirst($delivery['type']) ?></span>
      <h1><?= htmlspecialchars($delivery['title']) ?></h1>
      <?php if ($delivery['description']): ?>
        <div class="delivery-description"><?= $delivery['description'] /* HTML saneado */ ?></div>
      <?php endif; ?>
    </div>

    <?php if ($enrollment && $enrollment['status'] === 'active' && $delivery['type'] !== 'matricula'): ?>

      <!-- PDF Download -->
      <?php if ($delivery['pdf_path']): ?>
      <div class="delivery-resource">
        <div class="resource-icon">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div>
          <p class="font-semibold">Material de la entrega</p>
          <p class="text-muted text-sm">Descarga el PDF con el contenido de esta unidad</p>
        </div>
        <a href="<?= BASE_URL ?>/entrega/<?= $delivery['id'] ?>/descargar-pdf" class="btn btn-primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Descargar PDF
        </a>
      </div>
      <?php endif; ?>

      <!-- Examen -->
      <?php if (!empty($exam)): ?>
      <div class="exam-section" id="examen">
        <h2>Examen</h2>
        <?php if ($examAttempt): ?>
          <!-- Resultado -->
          <div class="exam-result">
            <div class="exam-score-big <?= $examAttempt['score'] >= 5 ? 'passed' : 'failed' ?>">
              <span class="score-number"><?= number_format($examAttempt['score'], 1) ?></span>
              <span class="score-label">/ 10</span>
            </div>
            <p><?= $examAttempt['score'] >= 5 ? '¡Aprobado! Puedes continuar a la siguiente entrega.' : 'Suspenso. Revisa el material y vuelve a intentarlo.' ?></p>
            <!-- Detalle respuestas -->
            <?php foreach ($examAttempt['details'] as $q): ?>
            <div class="exam-q-result <?= $q['correct'] ? 'q-correct' : 'q-wrong' ?>">
              <p class="q-text"><?= htmlspecialchars($q['question_text']) ?></p>
              <?php foreach ($q['answers'] as $a): ?>
                <p class="q-answer <?= $a['selected'] ? 'selected' : '' ?> <?= $a['is_correct'] ? 'correct' : ($a['selected'] ? 'wrong' : '') ?>">
                  <?= $a['selected'] ? (✓ o ✕ según is_correct) : '' ?>
                  <?= htmlspecialchars($a['text']) ?>
                </p>
              <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <!-- Formulario examen -->
          <form action="<?= BASE_URL ?>/entrega/<?= $delivery['id'] ?>/examen/enviar" method="POST" class="exam-form">
            <?= Csrf::field() ?>
            <?php foreach ($exam['questions'] as $qi => $q): ?>
            <fieldset class="exam-question">
              <legend>
                <span class="q-num"><?= $qi + 1 ?>.</span>
                <?= $q['text'] /* HTML del wysiwyg, saneado */ ?>
              </legend>
              <?php foreach ($q['answers'] as $a): ?>
              <label class="answer-label">
                <input
                  type="<?= $q['answer_type'] === 'checkbox' ? 'checkbox' : 'radio' ?>"
                  name="answers[<?= $q['id'] ?>]<?= $q['answer_type'] === 'checkbox' ? '[]' : '' ?>"
                  value="<?= $a['id'] ?>"
                >
                <span><?= htmlspecialchars($a['text']) ?></span>
              </label>
              <?php endforeach; ?>
            </fieldset>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary">Enviar examen</button>
          </form>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    <?php elseif (!$enrollment): ?>
      <!-- No inscrito -->
      <div class="delivery-gate">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        <h2>Acceso restringido</h2>
        <p>Debes inscribirte en esta entrega para acceder al contenido.</p>
        <?php if ($delivery['payment_type'] === 'online'): ?>
          <a href="<?= BASE_URL ?>/paypal/iniciar/<?= $delivery['id'] ?>" class="btn btn-primary">Inscribirme — <?= number_format($delivery['price'], 2, ',', '.') ?> €</a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/inscribir/<?= $delivery['id'] ?>" class="btn btn-secondary">Inscribirme (pago presencial)</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>