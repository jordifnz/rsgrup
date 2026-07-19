<?php
$metaTitle = 'Mi panel';
include BASE_PATH . '/templates/partials/header.php';

$allDeliveries = Database::fetchAll(
    "SELECT d.id FROM rsgrup_deliveries d WHERE d.active=1 AND d.type IN('entrega','practica') ORDER BY d.sort_order"
);
$allDeliveryIds = array_column($allDeliveries, 'id');

$titleReady = false;
if (!empty($allDeliveryIds)) {
    $placeholders = implode(',', array_fill(0, count($allDeliveryIds), '?'));
    $completedCount = Database::fetchColumn(
        "SELECT COUNT(DISTINCT e.delivery_id)
         FROM rsgrup_enrollments e
         JOIN rsgrup_exam_attempts a ON a.user_id = e.user_id
             AND a.exam_id IN (SELECT exam_id FROM rsgrup_deliveries WHERE id = e.delivery_id AND exam_id IS NOT NULL)
         WHERE e.user_id = ?
           AND e.status = 'active'
           AND e.delivery_id IN ({$placeholders})",
        array_merge([$_SESSION['user_id']], $allDeliveryIds)
    );
    $titleReady = ((int)$completedCount === count($allDeliveryIds));
}

$courses     = Database::fetchAll("SELECT * FROM rsgrup_courses WHERE active=1 ORDER BY id");
$userEnrolls = Database::fetchAll(
    "SELECT delivery_id FROM rsgrup_enrollments WHERE user_id=? AND status='active'",
    [$_SESSION['user_id']]
);
$enrolledIds = array_column($userEnrolls, 'delivery_id');
?>
<div class="dashboard-wrap">
  <h1>Mi panel</h1>

  <?php if(!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success" style="margin-bottom:var(--space-4)"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>
  <?php if(!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-error" style="margin-bottom:var(--space-4)"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <?php if(empty($courses)): ?>
    <div class="empty-state">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      <h3>No hay cursos disponibles</h3>
      <p>Cuando haya cursos publicados aparecer&aacute;n aqu&iacute;.</p>
    </div>
  <?php else: ?>
    <?php foreach($courses as $course): ?>
      <?php
        $deliveries = Database::fetchAll(
            "SELECT d.*, e.id AS enroll_id, a.score
             FROM rsgrup_deliveries d
             LEFT JOIN rsgrup_enrollments e ON e.delivery_id=d.id AND e.user_id=? AND e.status='active'
             LEFT JOIN rsgrup_exam_attempts a ON a.user_id=? AND a.exam_id=d.exam_id
             WHERE d.course_id=? AND d.active=1
             ORDER BY d.sort_order",
            [$_SESSION['user_id'], $_SESSION['user_id'], $course['id']]
        );
      ?>
      <div class="course-card">
        <h2><?= htmlspecialchars($course['title']) ?></h2>
        <?php if(!empty($course['description'])): ?><p><?= $course['description'] ?></p><?php endif; ?>
        <div class="delivery-list">
          <?php foreach($deliveries as $d): ?>
            <?php $enrolled = !empty($d['enroll_id']); ?>
            <div class="delivery-row <?= $enrolled ? 'enrolled' : 'not-enrolled' ?>">
              <div class="delivery-info">
                <span class="badge badge-<?= $d['type'] ?>"><?= ucfirst($d['type']) ?></span>
                <span class="delivery-title"><?= htmlspecialchars($d['title']) ?></span>
                <?php if($enrolled && $d['score'] !== null): ?>
                  <span class="badge badge-score">Nota: <?= number_format((float)$d['score'],1) ?></span>
                <?php elseif($enrolled && $d['exam_id']): ?>
                  <span class="badge badge-muted">Examen pendiente</span>
                <?php endif; ?>
              </div>
              <div class="delivery-actions">
                <?php if($enrolled): ?>
                  <a href="<?= BASE_URL ?>/entrega/<?= $d['id'] ?>" class="btn btn-sm btn-primary">Acceder</a>
                <?php else: ?>
                  <?php if($d['type']==='practica'): ?>
                    <span class="price-tag"><?= number_format((float)$d['price'],2,',','.') ?> &euro; (presencial)</span>
                  <?php elseif($d['price']>0): ?>
                    <span class="price-tag"><?= number_format((float)$d['price'],2,',','.') ?> &euro;</span>
                  <?php endif; ?>
                  <a href="<?= BASE_URL ?>/inscribir/<?= $d['id'] ?>" class="btn btn-sm">Inscribirme</a>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <div class="title-cta" style="text-align:center">
    <?php if($titleReady): ?>
      <a href="<?= BASE_URL ?>/descargar-titulo" class="btn btn-primary btn-lg">&#127891; Descargar mi t&iacute;tulo</a>
    <?php else: ?>
      <button class="btn btn-primary btn-lg" disabled title="Completa todas las entregas y sus ex&aacute;menes">
        &#127891; Descargar mi t&iacute;tulo
      </button>
      <p style="margin-top:var(--space-2);color:var(--color-text-muted);font-size:var(--text-sm)">Disponible cuando completes todas las entregas y ex&aacute;menes.</p>
    <?php endif; ?>
  </div>
</div>
<?php include BASE_PATH . '/templates/partials/footer.php'; ?>
