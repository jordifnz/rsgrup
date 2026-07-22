<?php
$metaTitle = htmlspecialchars($delivery['title']);
$robots    = 'noindex,nofollow';
include BASE_PATH . '/templates/layouts/base.php';

$isMatricula  = ($delivery['type'] === 'matricula');
$passingScore = ExamModel::passingScore();

// Cargar adjuntos adicionales de todos los temas de esta entrega
$attachmentsByTopic = TopicModel::attachmentsForDelivery((int)$delivery['id']);

// Mapa de iconos por extensión
function attachmentIcon(string $ext): string {
    return match($ext) {
        'pdf'                      => 'file-text',
        'doc','docx'               => 'file-type-2',
        'xls','xlsx'               => 'table',
        'ppt','pptx'               => 'presentation',
        'zip','rar'                => 'file-archive',
        'jpg','jpeg','png','gif'   => 'image',
        'mp4'                      => 'video',
        'mp3'                      => 'music',
        default                    => 'paperclip',
    };
}
?>
<section class="delivery-page">
  <div class="container">

    <nav class="breadcrumb-nav" aria-label="Ruta">
      <a href="<?= BASE_URL ?>/dashboard" class="breadcrumb-link">
        <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 6.5L8 2l6 4.5V14a1 1 0 01-1 1H3a1 1 0 01-1-1V6.5z"/><path d="M6 15V9h4v6"/></svg>
        Dashboard
      </a>
      <span class="breadcrumb-sep" aria-hidden="true">&#8250;</span>
      <a href="<?= BASE_URL ?>/dashboard" class="breadcrumb-link">Mis Entregas</a>
      <span class="breadcrumb-sep" aria-hidden="true">&#8250;</span>
      <span class="breadcrumb-current"><?= htmlspecialchars($delivery['title']) ?></span>
    </nav>

    <?php if ($flash = getFlash()): ?>
      <div class="flash flash--<?= $flash['type'] ?>" role="alert"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

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

        <!-- Descripción de la entrega -->
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

        <?php elseif (empty($topics)): ?>
          <p style="font-size:var(--text-sm);color:var(--color-text-muted);padding:var(--space-4) 0">
            Esta entrega todavía no tiene temas asignados.
          </p>

        <?php else: ?>
          <!-- Lista de temas -->
          <div class="delivery-list">
          <?php foreach ($topics as $topic):
            $topicId      = (int)$topic['id'];
            $attempt      = $topic['_attempt'];
            $attemptsAll  = $topic['_attempts_all'];
            $aCnt         = $topic['_attempt_count'];
            $canRetry     = $topic['_can_retry'];
            $examObj      = $topic['_exam_obj'];
            $lastPassed   = $attempt && ExamModel::isPassing((float)$attempt['score']);
            $lastFailed   = $attempt && !ExamModel::isPassing((float)$attempt['score']);
            $exhausted    = $aCnt >= 2;
            $topicAttachments = $attachmentsByTopic[$topicId] ?? [];
          ?>
          <div class="delivery-row enrolled" style="flex-wrap:wrap;border-bottom:1px solid var(--color-divider);padding-bottom:var(--space-3);margin-bottom:var(--space-1)">

            <!-- Encabezado del tema -->
            <div class="delivery-info" style="width:100%;margin-bottom:var(--space-2)">
              <i data-lucide="book-open" style="width:16px;height:16px;flex-shrink:0"></i>
              <span class="delivery-title" style="font-weight:600"><?= htmlspecialchars($topic['title']) ?></span>
            </div>

            <?php if (!empty($topic['description'])): ?>
            <div style="width:100%;margin-left:var(--space-6);margin-bottom:var(--space-2)">
              <div class="richtext" style="font-size:var(--text-sm);color:var(--color-text-muted)"><?= $topic['description'] ?></div>
            </div>
            <?php endif; ?>

            <!-- Acciones: PDF, Adjuntos y Examen -->
            <div style="width:100%;margin-left:var(--space-6);display:flex;flex-wrap:wrap;gap:var(--space-3);align-items:flex-start">

              <!-- PDF principal del tema -->
              <?php if (!empty($topic['pdf_file'])): ?>
              <a href="<?= BASE_URL ?>/descargar-pdf/topic/<?= $topicId ?>" class="btn btn-sm btn-secondary"
                 style="display:inline-flex;align-items:center;gap:var(--space-1)">
                <i data-lucide="file-text" style="width:13px;height:13px"></i> Descargar PDF
              </a>
              <?php endif; ?>

              <!-- Adjuntos adicionales -->
              <?php if (!empty($topicAttachments)): ?>
              <div style="display:flex;flex-direction:column;gap:var(--space-2)">
                <?php foreach ($topicAttachments as $att):
                  $ext   = strtolower(pathinfo($att['original_name'], PATHINFO_EXTENSION));
                  $icon  = attachmentIcon($ext);
                  $label = $att['description'] ?: $att['original_name'];
                ?>
                <div style="display:flex;align-items:flex-start;gap:var(--space-2)">
                  <a href="<?= BASE_URL ?>/descargar-adjunto/<?= (int)$att['id'] ?>"
                     class="btn btn-sm btn-secondary"
                     style="display:inline-flex;align-items:center;gap:var(--space-1);flex-shrink:0">
                    <i data-lucide="<?= $icon ?>" style="width:13px;height:13px"></i>
                    <?= htmlspecialchars($label) ?>
                  </a>
                  <?php if (!empty($att['description']) && $att['description'] !== $att['original_name']): ?>
                  <span style="font-size:var(--text-xs);color:var(--color-text-muted);padding-top:4px;line-height:1.4">
                    <?= htmlspecialchars($att['original_name']) ?>
                  </span>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>

              <!-- Examen del tema -->
              <?php if ($topic['exam_id'] && $examObj): ?>
              <div style="display:flex;align-items:center;gap:var(--space-2);flex-wrap:wrap">
                <i data-lucide="file-check" style="width:15px;height:15px"></i>
                <span style="font-size:var(--text-sm)">Examen: <?= htmlspecialchars($examObj['title']) ?></span>

                <?php if ($lastPassed): ?>
                  <span class="badge badge-score"><?= number_format((float)$attempt['score'] / 10, 1) ?> / 10</span>
                  <span class="badge badge-success">Aprobado</span>

                <?php elseif ($lastFailed && $exhausted): ?>
                  <span class="badge badge-score"><?= number_format((float)$attempt['score'] / 10, 1) ?> / 10</span>
                  <span class="badge" style="background:rgba(161,44,123,.1);color:#a12c7b;border-color:rgba(161,44,123,.2)">Sin más intentos</span>

                <?php elseif ($lastFailed && $canRetry): ?>
                  <span class="badge badge-score"><?= number_format((float)$attempt['score'] / 10, 1) ?> / 10</span>
                  <span class="badge" style="background:rgba(161,44,123,.1);color:#a12c7b;border-color:rgba(161,44,123,.2)">Suspenso</span>
                  <span class="badge" style="background:rgba(218,113,1,.1);color:#da7101;border-color:rgba(218,113,1,.2)">2.&ordf; oportunidad</span>

                <?php else: ?>
                  <span class="badge badge-muted">Pendiente</span>
                <?php endif; ?>

                <!-- Botón para abrir/hacer examen -->
                <?php if ($lastPassed): ?>
                  <span style="font-size:var(--text-xs);color:var(--color-text-muted)">Ya aprobado</span>

                <?php elseif ($lastFailed && $exhausted): ?>
                  <span style="font-size:var(--text-xs);color:var(--color-text-muted)">Intentos agotados</span>

                <?php elseif ((!$attempt || $canRetry) && $examAvailable['available']): ?>
                  <button type="button" class="btn btn-sm btn-primary"
                    onclick="toggleExamen(<?= $topicId ?>)"
                    id="btn-abrir-examen-<?= $topicId ?>">
                    <i data-lucide="pencil"></i>
                    <?= ($canRetry ? '2.&ordf; oportunidad' : 'Realizar') ?>
                  </button>

                <?php elseif (!$examAvailable['available']): ?>
                  <span style="font-size:var(--text-xs);color:var(--color-text-muted)">
                    Disponible el <?= htmlspecialchars($examAvailable['next'] ?? '...') ?>
                  </span>
                <?php endif; ?>
              </div>
              <?php endif; ?>

            </div><!-- /acciones -->

            <!-- Formulario de examen inline (oculto) -->
            <?php if ($topic['exam_id'] && $examObj && ((!$attempt && $examAvailable['available']) || ($canRetry && $examAvailable['available']))): ?>
            <div id="examen-form-wrap-<?= $topicId ?>" style="display:none;width:100%;margin-top:var(--space-4);border-top:1px solid var(--color-divider);padding-top:var(--space-4)">
              <?php if ($canRetry): ?>
              <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-3)">
                &#9888;&#65039; Esta es tu <strong>segunda y última oportunidad</strong>.
                Nota anterior: <strong><?= htmlspecialchars((string)$attempt['score']) ?>%</strong>.
              </p>
              <?php endif; ?>
              <?php if (!empty($examObj['questions'])): ?>
              <form action="<?= BASE_URL ?>/examen/enviar" method="POST" class="exam-form">
                <?= Csrf::field() ?>
                <input type="hidden" name="exam_id" value="<?= (int)$examObj['id'] ?>">
                <?php foreach ($examObj['questions'] as $qi => $q): ?>
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
                  <button type="button" class="btn btn-sm btn-secondary" onclick="toggleExamen(<?= $topicId ?>)">Cancelar</button>
                </div>
              </form>
              <?php else: ?>
                <p style="font-size:var(--text-sm);color:var(--color-text-muted)">El examen no tiene preguntas todavía.</p>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Historial de intentos -->
            <?php if ($lastFailed && $exhausted && count($attemptsAll) > 1): ?>
            <div style="width:100%;margin-top:var(--space-3);border-top:1px solid var(--color-divider);padding-top:var(--space-3)">
              <details style="font-size:var(--text-xs);color:var(--color-text-muted)">
                <summary style="cursor:pointer">Ver historial de intentos</summary>
                <ul style="margin-top:var(--space-2);padding-left:var(--space-4)">
                  <?php foreach ($attemptsAll as $i => $at): ?>
                  <li>Intento <?= $i + 1 ?>: <strong><?= number_format((float)$at['score'] / 10, 1) ?> / 10</strong>
                    &mdash; <?= htmlspecialchars((new DateTimeImmutable($at['created_at']))->format('d/m/Y H:i')) ?>
                  </li>
                  <?php endforeach; ?>
                </ul>
              </details>
            </div>
            <?php endif; ?>

          </div><!-- /delivery-row topic -->
          <?php endforeach; ?>
          </div><!-- /delivery-list -->

        <?php endif; // topics ?>

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
function toggleExamen(topicId) {
  var wrap = document.getElementById('examen-form-wrap-' + topicId);
  var btn  = document.getElementById('btn-abrir-examen-' + topicId);
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
