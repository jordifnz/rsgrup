<?php
/** @var array $delivery */
/** @var array $check  — ['ok' => bool, 'reason' => string] */
include BASE_PATH . '/templates/layouts/base.php';
$isGratis   = ($delivery['payment_type'] ?? '') === 'gratis';
$isPractica = $delivery['type'] === 'practica';
?>
<div class="page-container" style="max-width:640px;margin:var(--space-12) auto;padding:0 var(--space-4)">

  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-error" style="margin-bottom:var(--space-4)">
      <?= htmlspecialchars($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <div class="card" style="padding:var(--space-8)">

    <!-- Tipo badge -->
    <span class="badge badge-<?= htmlspecialchars($delivery['type']) ?>" style="margin-bottom:var(--space-4);display:inline-block">
      <?= ucfirst(htmlspecialchars($delivery['type'])) ?>
    </span>

    <h1 style="font-size:var(--text-xl);margin-bottom:var(--space-2)">
      <?= htmlspecialchars($delivery['title']) ?>
    </h1>

    <?php if (!empty($delivery['description'])): ?>
      <div class="prose" style="margin-bottom:var(--space-6);color:var(--color-text-muted)">
        <?= $delivery['description'] /* HTML seguro generado por admin WYSIWYG */ ?>
      </div>
    <?php endif; ?>

    <!-- Precio -->
    <div style="display:flex;align-items:baseline;gap:var(--space-2);margin-bottom:var(--space-6)">
      <?php if ($isGratis): ?>
        <span style="font-size:var(--text-xl);font-weight:700;color:var(--color-success)">Sin Coste</span>
      <?php else: ?>
        <span style="font-size:var(--text-xl);font-weight:700;color:var(--color-text)">
          <?= number_format((float)($delivery['price'] ?? 0), 2, ',', '.') ?> &euro;
        </span>
        <?php if ($isPractica): ?>
          <span style="font-size:var(--text-sm);color:var(--color-text-muted)">(pago presencial)</span>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if (!$check['ok']): ?>
      <!-- No puede inscribirse: mostrar razón -->
      <div class="alert alert-warning" style="margin-bottom:var(--space-6)">
        <strong>No puedes inscribirte aún:</strong>
        <?= htmlspecialchars($check['reason']) ?>
      </div>
      <a href="<?= BASE_URL ?>/dashboard" class="btn btn-secondary" style="width:100%;text-align:center">
        Volver al dashboard
      </a>

    <?php else: ?>
      <!-- Puede inscribirse -->
      <?php if ($isGratis): ?>
        <p style="margin-bottom:var(--space-4);font-size:var(--text-sm);color:var(--color-text-muted)">
          Esta entrega es sin coste. Al confirmar quedarás inscrito de inmediato.
        </p>
      <?php elseif ($isPractica): ?>
        <p style="margin-bottom:var(--space-4);font-size:var(--text-sm);color:var(--color-text-muted)">
          Esta práctica tiene pago presencial. Al confirmar quedarás inscrito y realizarás el pago en persona.
        </p>
      <?php else: ?>
        <p style="margin-bottom:var(--space-4);font-size:var(--text-sm);color:var(--color-text-muted)">
          Al confirmar serás redirigido a PayPal para completar el pago de forma segura.
        </p>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>/inscribir">
        <?= Csrf::field() ?>
        <input type="hidden" name="delivery_id" value="<?= (int)$delivery['id'] ?>">
        <button type="submit" class="btn btn-primary" style="width:100%">
          <?php if ($isGratis || $isPractica): ?>
            Confirmar inscripción
          <?php else: ?>
            Confirmar e ir a PayPal
          <?php endif; ?>
        </button>
      </form>

      <a href="<?= BASE_URL ?>/dashboard"
         style="display:block;text-align:center;margin-top:var(--space-4);font-size:var(--text-sm);color:var(--color-text-muted)">
        Cancelar y volver
      </a>
    <?php endif; ?>

  </div>
</div>
<?php include BASE_PATH . '/templates/layouts/base_close.php'; ?>
