<?php
$metaTitle = 'Dashboard';
$robots    = 'noindex,nofollow';
include BASE_PATH . '/templates/layouts/base.php';
?>
<section class="dashboard-section">
  <div class="container">
    <div class="dashboard-header">
      <div class="welcome-block">
        <?php if ($user['avatar']): ?>
          <img src="<?= BASE_URL . htmlspecialchars($user['avatar']) ?>" class="avatar-img" alt="Avatar" width="56" height="56">
        <?php else: ?>
          <div class="avatar-initials"><?= htmlspecialchars(substr($user['name'],0,1) . substr($user['surnames'],0,1)) ?></div>
        <?php endif; ?>
        <div>
          <h1 class="h2">Hola, <?= htmlspecialchars($user['name']) ?></h1>
          <p class="text-muted">Gestiona tus entregas y exámenes</p>
        </div>
      </div>
      <?php if ($canDownloadCertificate): ?>
        <a href="<?= BASE_URL ?>/descargar-titulo" class="btn btn-primary">
          <i data-lucide="award"></i> Descargar título
        </a>
      <?php else: ?>
        <button class="btn" disabled title="Completa todas las entregas para descargar el título">
          <i data-lucide="award"></i> Descargar título
        </button>
      <?php endif; ?>
    </div>

    <?php if ($flash = getFlash()): ?>
      <div class="flash flash--<?= $flash['type'] ?>" role="alert"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <?php foreach ($courses as $course): ?>
    <div class="course-block">
      <h2 class="course-title"><?= htmlspecialchars($course['title']) ?></h2>
      <?php if (!empty($course['description'])): ?>
        <p class="course-desc text-muted"><?= htmlspecialchars(strip_tags($course['description'])) ?></p>
      <?php endif; ?>
      <div class="deliveries-grid">
        <?php foreach ($course['deliveries'] as $d):
          $enrolled  = $d['enrollment'] ?? null;
          $isActive  = ($enrolled['status'] ?? '') === STATUS_ACTIVE;
          $canEnroll = $d['can_enroll'];
          $examScore = $d['exam_score'] ?? null;
        ?>
        <div class="delivery-card delivery-card--<?= $d['type'] ?> <?= $isActive ? 'delivery-card--active' : '' ?>">
          <div class="delivery-card__header">
            <span class="badge badge-<?= $d['type'] ?>"><?= ucfirst($d['type']) ?></span>
            <span class="delivery-order">#<?= $d['sort_order'] ?></span>
          </div>
          <h3 class="delivery-card__title"><?= htmlspecialchars($d['title']) ?></h3>
          <p class="delivery-card__price">
            <?= $d['price'] > 0 ? number_format((float)$d['price'], 2, ',', '.') . ' €' : 'Gratuito' ?>
          </p>

          <?php if ($isActive): ?>
            <div class="delivery-card__actions">
              <?php if ($d['type'] !== TYPE_MATRICULA && $d['pdf_filename']): ?>
                <a href="<?= BASE_URL ?>/descargar-pdf/<?= $d['id'] ?>" class="btn btn-sm">
                  <i data-lucide="download"></i> Descargar PDF
                </a>
              <?php endif; ?>
              <?php if ($d['exam_id']): ?>
                <?php if ($examScore !== null): ?>
                  <span class="exam-score">Nota: <strong><?= number_format((float)$examScore, 1) ?></strong></span>
                <?php else: ?>
                  <a href="<?= BASE_URL ?>/entrega/<?= htmlspecialchars($d['slug']) ?>#examen" class="btn btn-sm btn-primary">
                    <i data-lucide="pencil"></i> Hacer examen
                  </a>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          <?php elseif ($canEnroll): ?>
            <form action="<?= BASE_URL ?>/inscribir" method="POST" class="delivery-card__enroll">
              <?= Csrf::field() ?>
              <input type="hidden" name="delivery_id" value="<?= $d['id'] ?>">
              <?php if ($d['payment_type'] === PAYMENT_ONLINE && $d['price'] > 0): ?>
                <button type="submit" class="btn btn-primary btn-sm">
                  <i data-lucide="credit-card"></i> Inscribirme (PayPal)
                </button>
              <?php else: ?>
                <button type="submit" class="btn btn-sm">
                  <i data-lucide="check"></i> Inscribirme
                </button>
              <?php endif; ?>
            </form>
          <?php else: ?>
            <button class="btn btn-sm" disabled>Bloqueado</button>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>
