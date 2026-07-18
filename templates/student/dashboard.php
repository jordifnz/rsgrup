<?php
$metaTitle = 'Mi formación';
$robots = 'noindex,nofollow';
include BASE_PATH . '/templates/layouts/base.php';
?>
<section class="dashboard-section">
  <div class="container">
    <div class="dashboard-header">
      <div class="dashboard-welcome">
        <?php if ($user['avatar']): ?>
          <img src="<?= BASE_URL . htmlspecialchars($user['avatar']) ?>" alt="Avatar" width="48" height="48" class="avatar-img avatar-md">
        <?php else: ?>
          <div class="avatar-initials avatar-md"><?= htmlspecialchars(UserModel::getInitials($user)) ?></div>
        <?php endif; ?>
        <div>
          <h1 class="h2">Hola, <?= htmlspecialchars($user['name']) ?></h1>
          <p class="text-muted">Aquí tienes tu progreso formativo</p>
        </div>
      </div>
    </div>

    <?php foreach ($courses as $course): ?>
    <div class="course-block">
      <h2 class="course-title"><?= htmlspecialchars($course['title']) ?></h2>
      <?php if (!empty($course['description'])): ?>
        <p class="course-desc text-muted"><?= htmlspecialchars(strip_tags($course['description'])) ?></p>
      <?php endif; ?>

      <div class="deliveries-grid">
        <?php foreach ($course['deliveries'] as $d):
          $enrolled   = $d['enrollment'] ?? null;
          $canEnroll  = $d['can_enroll'];
          $isPractica = $d['type'] === 'practica';
          $isMatricula = $d['type'] === 'matricula';
          $examScore  = $d['exam_score'] ?? null;
          $hasExam    = !empty($d['exam_id']);
        ?>
        <article class="delivery-card delivery-card--<?= $d['type'] ?><?= $enrolled ? ' delivery-card--enrolled' : '' ?>">
          <div class="delivery-card__header">
            <span class="badge badge-type badge-<?= $d['type'] ?>"><?= $isMatricula ? 'Matrícula' : ($isPractica ? 'Práctica' : 'Entrega') ?></span>
            <span class="delivery-order">#<?= (int)$d['sort_order'] ?></span>
          </div>
          <h3 class="delivery-title"><?= htmlspecialchars($d['title']) ?></h3>
          <?php if (!empty($d['description'])): ?>
            <p class="delivery-desc"><?= htmlspecialchars(strip_tags($d['description'])) ?></p>
          <?php endif; ?>
          <div class="delivery-card__footer">
            <?php if ($enrolled && $enrolled['status'] === 'active'): ?>
              <span class="badge badge-success">Inscrito</span>
              <?php if (!$isMatricula): ?>
                <a href="<?= BASE_URL ?>/entrega/<?= $d['id'] ?>" class="btn btn-sm btn-primary">Ver entrega</a>
              <?php endif; ?>
              <?php if ($hasExam): ?>
                <?php if ($examScore !== null): ?>
                  <span class="exam-score">Nota: <strong><?= number_format($examScore, 1) ?></strong></span>
                <?php else: ?>
                  <a href="<?= BASE_URL ?>/entrega/<?= $d['id'] ?>#examen" class="btn btn-sm">Hacer examen</a>
                <?php endif; ?>
              <?php endif; ?>
            <?php elseif ($canEnroll): ?>
              <span class="delivery-price"><?= number_format($d['price'], 2, ',', '.') ?> €</span>
              <?php if ($isPractica): ?>
                <a href="<?= BASE_URL ?>/inscribir/<?= $d['id'] ?>" class="btn btn-sm btn-secondary">Inscribirme (presencial)</a>
              <?php else: ?>
                <a href="<?= BASE_URL ?>/paypal/iniciar/<?= $d['id'] ?>" class="btn btn-sm btn-primary">Inscribirme</a>
              <?php endif; ?>
            <?php else: ?>
              <span class="badge badge-locked">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                Bloqueado
              </span>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- Título -->
    <?php if ($canDownloadCertificate): ?>
    <div class="certificate-banner">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
      <div>
        <strong>¡Enhorabuena! Has completado toda la formación.</strong>
        <p class="text-muted text-sm">Ya puedes descargar tu título.</p>
      </div>
      <a href="<?= BASE_URL ?>/titulo/descargar" class="btn btn-primary">Descargar título</a>
    </div>
    <?php else: ?>
    <div class="certificate-banner certificate-banner--locked">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
      <div>
        <strong>Título no disponible aún</strong>
        <p class="text-muted text-sm">Completa todas las entregas y exámenes para desbloquear tu título.</p>
      </div>
      <button class="btn btn-disabled" disabled>Descargar título</button>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>