<?php
$metaTitle = 'Dashboard';
$robots    = 'noindex,nofollow';
include BASE_PATH . '/templates/layouts/base.php';

// Defensa: si $user no llega desde el controller, construirlo desde sesión
if (empty($user) || !is_array($user)) {
    $user = [
        'id'       => $_SESSION['user_id']       ?? null,
        'name'     => $_SESSION['user_name']     ?? '',
        'surnames' => $_SESSION['user_surnames'] ?? '',
        'email'    => $_SESSION['user_email']    ?? '',
        'role'     => $_SESSION['user_role']     ?? ROLE_ALUMNO,
        'avatar'   => $_SESSION['user_avatar']   ?? null,
    ];
}

$courses                = $courses                ?? [];
$canDownloadCertificate = $canDownloadCertificate ?? false;

$initials = strtoupper(
    mb_substr($user['name']     ?? '', 0, 1) .
    mb_substr($user['surnames'] ?? '', 0, 1)
);
?>
<section class="dashboard-section">
  <div class="container">
    <div class="dashboard-header">
      <div class="welcome-block">
        <?php if (!empty($user['avatar'])): ?>
          <img src="<?= BASE_URL . htmlspecialchars($user['avatar']) ?>" class="avatar-img" alt="Avatar" width="56" height="56">
        <?php else: ?>
          <div class="avatar-initials"><?= htmlspecialchars($initials) ?></div>
        <?php endif; ?>
        <div>
          <h1 class="h2">Hola, <?= htmlspecialchars($user['name'] ?? 'alumno') ?></h1>
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
      <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>" role="alert">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <?php if (empty($courses)): ?>
      <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="book-open"></i></div>
        <h3>No hay entregas disponibles</h3>
        <p>Cuando el administrador publique las entregas del curso aparecerán aquí.</p>
      </div>
    <?php else: ?>
      <?php foreach ($courses as $course): ?>
      <div class="course-block">
        <h2 class="course-title"><?= htmlspecialchars($course['title'] ?? 'Curso') ?></h2>
        <?php if (!empty($course['description'])): ?>
          <p class="course-desc text-muted"><?= htmlspecialchars(strip_tags($course['description'])) ?></p>
        <?php endif; ?>
        <div class="deliveries-grid">
          <?php foreach (($course['deliveries'] ?? []) as $d):
            $enrolled  = $d['enrollment'] ?? null;
            $isActive  = ($enrolled['status'] ?? '') === STATUS_ACTIVE;
            $canEnroll = $d['can_enroll'] ?? false;
            $examScore = $d['exam_score'] ?? null;
          ?>
          <div class="delivery-card delivery-card--<?= htmlspecialchars($d['type']) ?> <?= $isActive ? 'delivery-card--active' : '' ?>">
            <div class="delivery-card__header">
              <span class="badge badge-<?= htmlspecialchars($d['type']) ?>"><?= ucfirst($d['type']) ?></span>
              <span class="delivery-order">#<?= (int)($d['sort_order'] ?? 0) ?></span>
            </div>
            <h3 class="delivery-card__title"><?= htmlspecialchars($d['title'] ?? '') ?></h3>
            <p class="delivery-card__price">
              <?= ($d['price'] ?? 0) > 0
                ? number_format((float)$d['price'], 2, ',', '.') . ' €'
                : 'Gratuito' ?>
            </p>
            <?php if ($isActive): ?>
              <div class="delivery-card__actions">
                <?php if (($d['type'] ?? '') !== TYPE_MATRICULA && !empty($d['pdf_filename'])): ?>
                  <a href="<?= BASE_URL ?>/descargar-pdf/<?= (int)$d['id'] ?>" class="btn btn-sm">
                    <i data-lucide="download"></i> Descargar PDF
                  </a>
                <?php endif; ?>
                <?php if (!empty($d['exam_id'])): ?>
                  <?php if ($examScore !== null): ?>
                    <span class="exam-score">Nota: <strong><?= number_format((float)$examScore, 1) ?></strong></span>
                  <?php else: ?>
                    <a href="<?= BASE_URL ?>/entrega/<?= htmlspecialchars($d['slug'] ?? '') ?>#examen" class="btn btn-sm btn-primary">
                      <i data-lucide="pencil"></i> Hacer examen
                    </a>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            <?php elseif ($canEnroll): ?>
              <form action="<?= BASE_URL ?>/inscribir" method="POST" class="delivery-card__enroll">
                <?= Csrf::field() ?>
                <input type="hidden" name="delivery_id" value="<?= (int)$d['id'] ?>">
                <?php if (($d['payment_type'] ?? '') === PAYMENT_ONLINE && ($d['price'] ?? 0) > 0): ?>
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
              <button class="btn btn-sm" disabled title="Completa las entregas anteriores primero">Bloqueado</button>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>
