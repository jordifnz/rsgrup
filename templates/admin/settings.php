<?php
$metaTitle = 'Settings';
include BASE_PATH . '/templates/admin/layout_admin.php';
?>
<h1>Configuración</h1>
<form action="<?= BASE_URL ?>/admin/settings/guardar" method="POST" enctype="multipart/form-data" class="form">
  <?= Csrf::field() ?>

  <section class="settings-section">
    <h2>PayPal</h2>
    <div class="form-grid">
      <div class="form-group"><label>Modo</label>
        <select name="paypal_mode">
          <option value="sandbox" <?= ($s['paypal_mode'] ?? '') === 'sandbox' ? 'selected' : '' ?>>Sandbox (pruebas)</option>
          <option value="live" <?= ($s['paypal_mode'] ?? '') === 'live' ? 'selected' : '' ?>>Live (producción)</option>
        </select>
      </div>
      <div class="form-group"><label>Client ID</label><input type="text" name="paypal_client_id" value="<?= htmlspecialchars($s['paypal_client_id'] ?? '') ?>"></div>
      <div class="form-group"><label>Client Secret</label><input type="password" name="paypal_client_secret" value="<?= htmlspecialchars($s['paypal_client_secret'] ?? '') ?>"></div>
    </div>
  </section>

  <section class="settings-section">
    <h2>SMTP (Email)</h2>
    <div class="form-grid">
      <div class="form-group"><label>Host SMTP</label><input type="text" name="smtp_host" value="<?= htmlspecialchars($s['smtp_host'] ?? 'smtp.gmail.com') ?>"></div>
      <div class="form-group"><label>Puerto</label><input type="number" name="smtp_port" value="<?= htmlspecialchars($s['smtp_port'] ?? '587') ?>"></div>
      <div class="form-group"><label>Usuario</label><input type="email" name="smtp_user" value="<?= htmlspecialchars($s['smtp_user'] ?? '') ?>"></div>
      <div class="form-group"><label>Contraseña SMTP</label><input type="password" name="smtp_pass" value="<?= htmlspecialchars($s['smtp_pass'] ?? '') ?>"></div>
      <div class="form-group"><label>Nombre remitente</label><input type="text" name="smtp_from_name" value="<?= htmlspecialchars($s['smtp_from_name'] ?? 'RSGrup') ?>"></div>
    </div>
  </section>

  <section class="settings-section">
    <h2>Evolution API (WhatsApp)</h2>
    <div class="form-grid">
      <div class="form-group"><label>URL Evolution API</label><input type="url" name="evolution_api_url" value="<?= htmlspecialchars($s['evolution_api_url'] ?? '') ?>"></div>
      <div class="form-group"><label>API Token</label><input type="text" name="evolution_api_token" value="<?= htmlspecialchars($s['evolution_api_token'] ?? '') ?>"></div>
      <div class="form-group"><label>Instancia (Instance Name)</label><input type="text" name="evolution_api_instance" value="<?= htmlspecialchars($s['evolution_api_instance'] ?? '') ?>"></div>
    </div>
  </section>

  <section class="settings-section">
    <h2>WhatsApp de contacto</h2>
    <div class="form-group" style="max-width:320px">
      <label>Número (ej: 34612345678)</label>
      <input type="text" name="whatsapp_contact_number" value="<?= htmlspecialchars($s['whatsapp_contact_number'] ?? '') ?>" placeholder="34612345678">
    </div>
  </section>

  <section class="settings-section">
    <h2>Plantillas de notificación</h2>
    <div class="form-group">
      <label>Plantilla e-mail (HTML)</label>
      <p class="help-text">Variables disponibles: <code>{{nombre}}</code> <code>{{apellidos}}</code> <code>{{entrega}}</code> <code>{{curso}}</code> <code>{{precio}}</code> <code>{{fecha}}</code> <code>{{url_entrega}}</code></p>
      <textarea name="email_template" class="wysiwyg-editor" rows="8"><?= htmlspecialchars($s['email_template'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
      <label>Plantilla WhatsApp (texto plano)</label>
      <p class="help-text">Variables: <code>{{nombre}}</code> <code>{{entrega}}</code> <code>{{fecha}}</code></p>
      <textarea name="whatsapp_template" rows="4" class="form-control" style="font-family:monospace"><?= htmlspecialchars($s['whatsapp_template'] ?? '') ?></textarea>
    </div>
  </section>

  <section class="settings-section">
    <h2>Título de alumnos</h2>
    <div class="form-grid">
      <div class="form-group">
        <label>PNG de fondo (apaisado)</label>
        <input type="file" name="certificate_bg" accept=".png">
        <?php if (!empty($s['certificate_bg'])): ?><p class="text-sm text-muted">Actual: <code><?= htmlspecialchars($s['certificate_bg']) ?></code></p><?php endif; ?>
      </div>
      <div class="form-group"><label>Coord X nombre</label><input type="number" name="certificate_name_x" value="<?= htmlspecialchars($s['certificate_name_x'] ?? '400') ?>"></div>
      <div class="form-group"><label>Coord Y nombre</label><input type="number" name="certificate_name_y" value="<?= htmlspecialchars($s['certificate_name_y'] ?? '300') ?>"></div>
      <div class="form-group"><label>Tamaño fuente (px)</label><input type="number" name="certificate_name_font_size" value="<?= htmlspecialchars($s['certificate_name_font_size'] ?? '48') ?>"></div>
    </div>
    <?php if (!empty($s['certificate_bg'])): ?>
    <div class="cert-preview">
      <p class="text-sm text-muted">Vista previa (marca roja = posición del nombre):</p>
      <div style="position:relative;display:inline-block;max-width:100%;overflow:hidden">
        <img src="<?= BASE_URL ?>/uploads/certificates/<?= htmlspecialchars($s['certificate_bg']) ?>" style="max-width:600px;width:100%" alt="Preview certificado">
        <div style="position:absolute;left:<?= (int)($s['certificate_name_x'] ?? 400) ?>px;top:<?= (int)($s['certificate_name_y'] ?? 300) ?>px;color:red;font-size:<?= (int)($s['certificate_name_font_size'] ?? 48) ?>px;font-weight:bold;pointer-events:none;white-space:nowrap;line-height:1">Nombre Alumno</div>
      </div>
    </div>
    <?php endif; ?>
  </section>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Guardar configuración</button>
  </div>
</form>

<section class="settings-section">
  <h2>Tokens API</h2>
  <form action="<?= BASE_URL ?>/admin/api-tokens/crear" method="POST" class="form-inline">
    <?= Csrf::field() ?>
    <input type="text" name="label" placeholder="Etiqueta del token" required class="form-control">
    <button type="submit" class="btn btn-primary">Crear token</button>
  </form>
  <table class="data-table" style="margin-top:var(--space-4)">
    <thead><tr><th>Etiqueta</th><th>Token (últimos 8)</th><th>Último uso</th><th>Creado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($apiTokens as $t): ?>
    <tr>
      <td><?= htmlspecialchars($t['label']) ?></td>
      <td><code>...<?= substr($t['token'], -8) ?></code></td>
      <td class="text-muted text-sm"><?= $t['last_used_at'] ? date('d/m/Y H:i', strtotime($t['last_used_at'])) : '—' ?></td>
      <td class="text-sm"><?= date('d/m/Y', strtotime($t['created_at'])) ?></td>
      <td>
        <form action="<?= BASE_URL ?>/admin/api-tokens/<?= $t['id'] ?>/eliminar" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar token?')">
          <?= Csrf::field() ?><button class="btn btn-sm btn-danger">Eliminar</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>