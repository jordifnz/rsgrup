<?php
$metaTitle = htmlspecialchars($delivery['title']);
$robots    = 'noindex,nofollow';
include BASE_PATH . '/templates/layouts/base.php';

$isMatricula = ($delivery['type'] === 'matricula');
?>
<section class="delivery-page">
  <div class="container">

    <nav class="breadcrumb" aria-label="Ruta" style="margin-bottom:var(--space-5);font-size:var(--text-sm);color:var(--color-text-muted)">
      <a href="<?= BASE_URL ?>/dashboard">Dashboard</a>
      <span aria-hidden="true"> / </span>
      <span><?= htmlspecialchars($delivery['title']) ?></span>
    </nav>

    <?php if ($flash = getFlash()): ?>
      <div class="flash flash--<?= $flash['type'] ?>" role="alert"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Tarjeta principal igual que .course-card del dashboard -->
    <div class="course-card">

      <!-- Cabecera -->
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-3);margin-bottom:var(--space-4)">
        <div style="display:flex;align-items:center;gap:var(--space-3)">
          <span class="badge badge-<?= $delivery['type'] ?>"><?= ucfirst($delivery['type']) ?></span>
          <h1 style="font-size:var(--text-lg);font-weight:700;margin:0"><?= htmlspecialchars($delivery['title']) ?></h1>
        </div>
        <?php if (!$isEnrolled && !$isMatricula && (float)$delivery['price'] > 0): ?>
          <span style="font-size:var(--text-sm);font-weight:600;color:var(--color-text-muted)">
            <?= number_format((float)$delivery['price'], 2, ',', '.') ?> &euro;
          </span>
        <?php endif; ?>
      </div>

      <?php if ($isEnrolled): ?>

        <!-- Descripción -->
        <?php if (!empty($delivery['description'])): ?>
        <div class="delivery-list" style="margin-bottom:var(--space-2)">
          <div class="delivery-row enrolled" style="align-items:flex-start">
            <div class="delivery-info" style="flex-direction:column;align-items:flex-start;gap:var(--space-1)">
              <span style="font-size:var(--text-xs);font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted)">Descripción</span>
              <div class="richtext" style="font-size:var(--text-sm)"><?= $delivery['description'] ?></div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($isMatricula): ?>
          <!-- Matrícula: acceso concedido -->
          <div class="delivery-list">
            <div class="delivery-row enrolled">
              <div class="delivery-info">
                <i data-lucide="check-circle" style="width:16px;height:16px;color:var(--color-success)"></i>
                <span class="delivery-title">Matrícula activa</span>
              </div>
              <div class="delivery-actions">
                <a href="<?= BASE_URL ?>/dashboard" class="btn btn-sm btn-secondary">Ir al dashboard</a>
              </div>
            </div>
          </div>

        <?php else: ?>
          <div class="delivery-list">

            <!-- Fila PDF -->
            <?php if (!empty($delivery['pdf_file'])): ?>
            <div class="delivery-row enrolled">
              <div class="delivery-info">
                <i data-lucide="file-text" style="width:16px;height:16px"></i>
                <span class="delivery-title">Temario en PDF</span>
              </div>
              <div class="delivery-actions">
                <a href="<?= BASE_URL ?>/descargar-pdf/<?= (int)$enrollment['id'] ?>" class="btn btn-sm btn-primary">
                  <i data-lucide="download"></i> Descargar
                </a>
              </div>
            </div>
            <?php endif; ?>

            <!-- Fila Examen -->
            <?php if ($delivery['exam_id'] && $exam): ?>
            <div class="delivery-row enrolled" style="flex-wrap:wrap">
              <div class="delivery-info">
                <i data-lucide="file-check" style="width:16px;height:16px"></i>
                <span class="delivery-title">Examen: <?= htmlspecialchars($exam['title']) ?></span>
                <?php if ($attempt): ?>
                  <span class="badge badge-score">
                    <?= number_format((float)$attempt['score'] / 10, 1) ?> / 10
                  </span>
                  <?php if ((float)$attempt['score'] >= 50): ?>
                    <span class="badge badge-success">Aprobado</span>
                  <?php else: ?>
                    <span class="badge" style="background:rgba(161,44,123,.1);color:#a12c7b;border-color:rgba(161,44,123,.2)">Suspenso</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge badge-muted">Pendiente</span>
                <?php endif; ?>
              </div>
              <div class="delivery-actions">
                <?php if ($attempt): ?>
                  <span style="font-size:var(--text-xs);color:var(--color-text-muted)">Ya realizado</span>
                <?php else: ?>
                  <button type="button" class="btn btn-sm btn-primary" onclick="toggleExamen()" id="btn-abrir-examen">
                    <i data-lucide="pencil"></i> Realizar
                  </button>
                <?php endif; ?>
              </div>

              <!-- Formulario oculto -->
              <?php if (!$attempt): ?>
              <div id="examen-form-wrap" style="display:none;width:100%;margin-top:var(--space-4);border-top:1px solid var(--color-divider);padding-top:var(--space-4)">
                <?php if (!empty($exam['questions'])): ?>
                <form action="<?= BASE_URL ?>/examen/enviar" method="POST" class="exam-form">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="exam_id" value="<?= (int)$exam['id'] ?>">
                  <?php foreach ($exam['questions'] as $qi => $q): ?>
                  <div style="margin-bottom:var(--space-5)">
                    <p style="font-weight:500;font-size:var(--text-sm);margin-bottom:var(--space-2)">
                      <strong><?= $qi + 1 ?>.</strong>
                      <?= htmlspecialchars($q['title'] ?? $q['question_text'] ?? '') ?>
                    </p>
                    <?php if (!empty($q['question_desc'])): ?>
                      <div class="richtext" style="margin-bottom:var(--space-2);font-size:var(--text-sm)"><?= $q['question_desc'] ?></div>
                    <?php endif; ?>
                    <div style="display:flex;flex-direction:column;gap:.35rem;margin-left:var(--space-4)">
                      <?php foreach ($q['answers'] as $a): ?>
                      <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:var(--text-sm)">
                        <input type="<?= $q['answer_type'] ?>"
                               name="answers[<?= (int)$q['id'] ?>]<?= $q['answer_type'] === 'checkbox' ? '[]' : '' ?>"
                               value="<?= (int)$a['id'] ?>">
                        <?= htmlspecialchars($a['text'] ?? $a['answer_text'] ?? '') ?>
                      </label>
                      <?php endforeach; ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                  <div style="display:flex;gap:var(--space-3)">
                    <button type="submit" class="btn btn-sm btn-primary">Enviar respuestas</button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="toggleExamen()">Cancelar</button>
                  </div>
                </form>
                <?php else: ?>
                  <p style="font-size:var(--text-sm);color:var(--color-text-muted)">El examen no tiene preguntas todavía.</p>
                <?php endif; ?>
              </div>
              <?php endif; ?>

            </div>
            <?php endif; ?>

          </div>
        <?php endif; // end !isMatricula ?>

      <?php else: ?>

        <!-- Usuario NO inscrito -->
        <div class="delivery-list">
          <div class="delivery-row">
            <div class="delivery-info">
              <i data-lucide="lock" style="width:16px;height:16px;color:var(--color-text-muted)"></i>
              <span class="delivery-title">Inscríbete para acceder al contenido</span>
              <?php if (!$isMatricula && (float)$delivery['price'] > 0): ?>
                <span class="price-tag"><?= number_format((float)$delivery['price'], 2, ',', '.') ?> &euro;</span>
              <?php endif; ?>
            </div>
            <div class="delivery-actions">
              <?php if ($canEnroll): ?>
                <form action="<?= BASE_URL ?>/inscribir" method="POST" style="margin:0">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="delivery_id" value="<?= (int)$delivery['id'] ?>">
                  <?php if ($delivery['payment_type'] === 'online' && (float)$delivery['price'] > 0): ?>
                    <button type="submit" class="btn btn-sm btn-primary">
                      <i data-lucide="credit-card"></i> Pagar con PayPal
                    </button>
                  <?php else: ?>
                    <button type="submit" class="btn btn-sm btn-primary">
                      <i data-lucide="check"></i> Inscribirme
                    </button>
                  <?php endif; ?>
                </form>
              <?php else: ?>
                <span style="font-size:var(--text-xs);color:var(--color-text-muted)"><?= htmlspecialchars($canEnrollReason) ?></span>
                <a href="<?= BASE_URL ?>/dashboard" class="btn btn-sm btn-secondary">&larr; Volver</a>
              <?php endif; ?>
            </div>
          </div>
        </div>

      <?php endif; ?>

    </div><!-- /.course-card -->
  </div>
</section>

<script>
function toggleExamen() {
  var wrap = document.getElementById('examen-form-wrap');
  var btn  = document.getElementById('btn-abrir-examen');
  if (!wrap) return;
  var open = wrap.style.display !== 'none';
  wrap.style.display = open ? 'none' : 'block';
  if (btn) btn.innerHTML = open
    ? '<i data-lucide="pencil"></i> Realizar'
    : '<i data-lucide="x"></i> Cancelar';
  if (!open) { wrap.scrollIntoView({ behavior: 'smooth', block: 'start' }); lucide.createIcons(); }
}
</script>

<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>
