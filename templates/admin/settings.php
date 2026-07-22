<?php
$metaTitle = 'Ajustes';
include BASE_PATH . '/templates/admin/layout_admin.php';
?>

<div class="section-header"><h1>Ajustes</h1></div>

<?php if(!empty($_SESSION['flash_success'])): ?>
  <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if(!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<style>
.pwd-wrap { position:relative; display:flex; align-items:center; }
.pwd-wrap input { flex:1; padding-right:2.5rem; }
.pwd-eye {
  position:absolute; right:.6rem;
  background:none; border:none; padding:0; cursor:pointer;
  color:var(--color-text-muted); display:flex; align-items:center;
}
.pwd-eye:hover { color:var(--color-text); }
.day-checkboxes { display:flex; gap:1rem; flex-wrap:wrap; margin-top:.5rem; }
.day-checkboxes label { display:flex; align-items:center; gap:.35rem; font-size:.9rem; cursor:pointer; }
</style>

<form method="POST" action="<?= BASE_URL ?>/admin/settings/guardar" enctype="multipart/form-data" id="form-settings">
<?= \Csrf::field() ?>

<!-- ESTILOS -->
<details class="settings-section" open>
  <summary>🎨 Estilos de la aplicación</summary>
  <div class="settings-body">
    <div class="form-grid">
      <div class="form-group">
        <label>Color de botones y enlaces</label>
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
          <input type="color" id="pick-brand" name="brand_accent_color"
                 value="<?= htmlspecialchars($s['brand_accent_color'] ?? '#e87722') ?>"
                 style="width:48px;height:38px;padding:2px;border-radius:6px;cursor:pointer;border:1px solid var(--color-border)">
          <input type="text" id="hex-brand" name="brand_accent_color_hex"
                 value="<?= htmlspecialchars($s['brand_accent_color'] ?? '#e87722') ?>"
                 placeholder="#e87722" maxlength="7" style="width:100px">
          <span style="font-size:.8rem;color:var(--color-text-muted)">Vista previa:
            <a href="#" style="color:var(--color-brand)">enlace</a> ·
            <button type="button" class="btn btn-primary btn-sm" style="padding:.2rem .6rem;font-size:.8rem">botón</button>
          </span>
        </div>
      </div>
    </div>
  </div>
</details>

<!-- EXÁMENES -->
<details class="settings-section">
  <summary>📝 Exámenes</summary>
  <div class="settings-body">
    <p style="font-size:.875rem;color:var(--color-text-muted);margin-bottom:1rem">
      Controla en qué momentos pueden los alumnos acceder a los exámenes.
    </p>
    <?php $examSchedule = $s['exam_schedule'] ?? 'last_saturday'; ?>
    <div class="form-group">
      <label>Modo de disponibilidad</label>
      <select name="exam_schedule" id="exam-schedule-select">
        <option value="last_saturday" <?= $examSchedule === 'last_saturday' ? 'selected' : '' ?>>Solamente el último sábado de cada mes</option>
        <option value="always"        <?= $examSchedule === 'always'        ? 'selected' : '' ?>>Siempre disponible</option>
        <option value="custom_days"   <?= $examSchedule === 'custom_days'   ? 'selected' : '' ?>>Días personalizados de la semana</option>
      </select>
    </div>

    <div class="form-group" id="exam-custom-days-wrap" style="display:<?= $examSchedule === 'custom_days' ? 'block' : 'none' ?>">
      <label>Días en que los exámenes estarán disponibles</label>
      <?php
        $customDays = array_filter(array_map('intval', explode(',', $s['exam_custom_days'] ?? '')));
        $dayNames   = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
      ?>
      <div class="day-checkboxes">
        <?php for ($d = 0; $d <= 6; $d++): ?>
        <label>
          <input type="checkbox" name="exam_custom_days[]" value="<?= $d ?>"
                 <?= in_array($d, $customDays, true) ? 'checked' : '' ?>>
          <?= $dayNames[$d] ?>
        </label>
        <?php endfor; ?>
      </div>
      <small style="color:var(--color-text-muted)">Selecciona uno o más días.</small>
    </div>

    <?php
      // Mostrar info de próxima fecha según modo actual
      if ($examSchedule === 'last_saturday'):
        $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));
        $month = (int)$now->format('n'); $year = (int)$now->format('Y');
        $lastDay = (int)(new DateTimeImmutable("last day of {$year}-{$month}-01"))->format('j');
        $ls = $lastDay;
        while ((int)(new DateTimeImmutable("{$year}-{$month}-{$ls}"))->format('w') !== 6) $ls--;
        $lsDate = (new DateTimeImmutable("{$year}-{$month}-{$ls}"))->format('d/m/Y');
    ?>
    <p style="margin-top:.75rem;font-size:.875rem;color:var(--color-text-muted)">
      📅 El último sábado de este mes es: <strong><?= $lsDate ?></strong>
    </p>
    <?php endif; ?>
  </div>
</details>

<!-- PAYPAL -->
<details class="settings-section">
  <summary>💳 PayPal</summary>
  <div class="settings-body">
    <div class="form-grid">
      <div class="form-group">
        <label>Client ID</label>
        <input type="text" name="paypal_client_id" value="<?= htmlspecialchars($s['paypal_client_id'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Client Secret</label>
        <div class="pwd-wrap">
          <input type="password" name="paypal_client_secret" id="paypal_client_secret" value="<?= htmlspecialchars($s['paypal_client_secret'] ?? '') ?>">
          <button type="button" class="pwd-eye" data-target="paypal_client_secret" aria-label="Ver/ocultar"><?= eyeIcon() ?></button>
        </div>
      </div>
      <div class="form-group">
        <label>Modo</label>
        <select name="paypal_mode">
          <option value="sandbox" <?= ($s['paypal_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox (pruebas)</option>
          <option value="live"    <?= ($s['paypal_mode'] ?? '') === 'live' ? 'selected' : '' ?>>Live (producción)</option>
        </select>
      </div>
    </div>
  </div>
</details>

<!-- SMTP -->
<details class="settings-section">
  <summary>📧 Email (SMTP)</summary>
  <div class="settings-body">
    <div class="form-grid">
      <div class="form-group"><label>Host SMTP</label><input type="text" name="smtp_host" value="<?= htmlspecialchars($s['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com"></div>
      <div class="form-group"><label>Puerto</label><input type="number" name="smtp_port" value="<?= htmlspecialchars($s['smtp_port'] ?? '587') ?>"></div>
      <div class="form-group"><label>Usuario</label><input type="text" name="smtp_user" value="<?= htmlspecialchars($s['smtp_user'] ?? '') ?>"></div>
      <div class="form-group">
        <label>Contraseña</label>
        <div class="pwd-wrap">
          <input type="password" name="smtp_pass" id="smtp_pass" value="<?= htmlspecialchars($s['smtp_pass'] ?? '') ?>">
          <button type="button" class="pwd-eye" data-target="smtp_pass" aria-label="Ver/ocultar"><?= eyeIcon() ?></button>
        </div>
      </div>
      <div class="form-group"><label>Nombre remitente</label><input type="text" name="smtp_from_name" value="<?= htmlspecialchars($s['smtp_from_name'] ?? 'RSGrup') ?>"></div>
      <div class="form-group"><label>Email remitente</label><input type="email" name="smtp_from_email" value="<?= htmlspecialchars($s['smtp_from_email'] ?? '') ?>"></div>
    </div>
  </div>
</details>

<!-- EVOLUTION API -->
<details class="settings-section">
  <summary>💬 Evolution API (WhatsApp)</summary>
  <div class="settings-body">
    <div class="form-grid">
      <div class="form-group"><label>URL Evolution API</label><input type="url" name="evolution_api_url" value="<?= htmlspecialchars($s['evolution_api_url'] ?? '') ?>" placeholder="https://api.tuservidor.com"></div>
      <div class="form-group">
        <label>Token</label>
        <div class="pwd-wrap">
          <input type="password" name="evolution_api_token" id="evolution_api_token" value="<?= htmlspecialchars($s['evolution_api_token'] ?? '') ?>">
          <button type="button" class="pwd-eye" data-target="evolution_api_token" aria-label="Ver/ocultar"><?= eyeIcon() ?></button>
        </div>
      </div>
      <div class="form-group"><label>Instancia</label><input type="text" name="evolution_instance" value="<?= htmlspecialchars($s['evolution_instance'] ?? '') ?>"></div>
    </div>
  </div>
</details>

<!-- WHATSAPP SOPORTE AL ALUMNO -->
<details class="settings-section">
  <summary>📱 WhatsApp de soporte al alumno</summary>
  <div class="settings-body">
    <p style="font-size:.875rem;color:var(--color-text-muted);margin-bottom:1rem">
      Si se configura un número, aparecerá un botón flotante de WhatsApp en todas las páginas de la aplicación
      para que los alumnos puedan contactar fácilmente con soporte.
    </p>
    <div class="form-grid">
      <div class="form-group">
        <label>Número de WhatsApp (formato internacional, sin + ni espacios)</label>
        <input type="text"
               name="whatsapp_support_number"
               value="<?= htmlspecialchars($s['whatsapp_support_number'] ?? '') ?>"
               placeholder="34600000000"
               pattern="[0-9]{7,15}"
               style="max-width:240px">
        <small style="color:var(--color-text-muted)">Ejemplo: <code>34600123456</code> para un número español. Deja en blanco para ocultar el botón.</small>
      </div>
    </div>
    <?php if (!empty($s['whatsapp_support_number'])): ?>
    <div style="margin-top:.75rem;display:flex;align-items:center;gap:.6rem;font-size:.875rem">
      <span style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:50%;background:#25d366;color:#fff;flex-shrink:0">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm.01 1.67c2.2 0 4.27.86 5.82 2.41a8.22 8.22 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.24 8.23-1.48 0-2.93-.4-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.32a8.19 8.19 0 0 1-1.26-4.37c.01-4.54 3.7-8.23 8.25-8.23zm-2.9 4.36c-.18 0-.46.07-.7.34-.24.27-.91.89-.91 2.17s.93 2.52 1.06 2.69c.13.18 1.83 2.79 4.43 3.91.62.27 1.1.43 1.48.55.62.2 1.19.17 1.63.1.5-.07 1.53-.63 1.75-1.23.22-.6.22-1.12.15-1.23-.06-.1-.24-.17-.5-.3-.27-.13-1.54-.76-1.78-.85-.24-.09-.41-.13-.58.13-.17.27-.65.85-.8 1.02-.14.18-.29.2-.54.07-.25-.14-1.05-.39-2-1.23-.74-.66-1.24-1.47-1.39-1.72-.14-.25-.01-.38.11-.51.11-.11.25-.29.38-.43.12-.14.16-.24.24-.41.08-.17.04-.31-.02-.44-.06-.13-.57-1.38-.79-1.89-.2-.48-.41-.42-.57-.43l-.48-.01z"/></svg>
      </span>
      <span>El botón flotante está <strong>activo</strong> con el número <code><?= htmlspecialchars($s['whatsapp_support_number']) ?></code></span>
    </div>
    <?php else: ?>
    <div style="margin-top:.75rem;font-size:.875rem;color:var(--color-text-muted)">
      ℹ️ El botón flotante está <strong>oculto</strong> (no hay número configurado).
    </div>
    <?php endif; ?>
  </div>
</details>

<!-- PLANTILLAS -->
<?php
$varsList = '<code>{{nombre}}</code> <code>{{apellidos}}</code> <code>{{email}}</code> '
          . '<code>{{entrega}}</code> <code>{{curso_titulo}}</code> <code>{{fecha}}</code> '
          . '<code>{{precio}}</code> <code>{{sitio}}</code>';
?>
<details class="settings-section">
  <summary>✉️ Plantillas de notificación</summary>
  <div class="settings-body">
    <div class="form-group">
      <label>Asunto email</label>
      <input type="text" name="email_template_subject"
             value="<?= htmlspecialchars($s['email_template_subject'] ?? 'Inscripción confirmada: {{entrega}}') ?>">
      <small>Variables: <?= $varsList ?></small>
    </div>
    <div class="form-group">
      <label>Cuerpo email (HTML)</label>
      <textarea name="email_template_body" class="wysiwyg" rows="8"><?= htmlspecialchars($s['email_template_body'] ?? $s['email_template'] ?? '') ?></textarea>
      <small>Variables: <?= $varsList ?></small>
    </div>
    <div class="form-group">
      <label>Plantilla WhatsApp (texto plano)</label>
      <textarea name="whatsapp_template" rows="4" style="font-family:monospace"><?= htmlspecialchars($s['whatsapp_template'] ?? '') ?></textarea>
      <small>Variables: <?= $varsList ?></small>
    </div>
  </div>
</details>

<!-- TÍTULO ALUMNOS -->
<details class="settings-section">
  <summary>🎓 Título de alumnos</summary>
  <div class="settings-body">

    <!-- ACCESO RÁPIDO A IMPRESIÓN MASIVA -->
    <div style="margin-bottom:1.5rem;padding:1rem 1.25rem;background:var(--color-surface-offset);border-radius:var(--radius-md);border:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
      <div>
        <p style="font-weight:600;margin-bottom:.2rem">🖨️ Impresión masiva de títulos</p>
        <p style="font-size:.875rem;color:var(--color-text-muted);margin:0">
          Selecciona alumnos y genera un PDF con todos sus títulos de una sola vez.
        </p>
      </div>
      <a href="<?= BASE_URL ?>/admin/titulos-masivos" class="btn btn-primary" style="white-space:nowrap">
        Ir a impresión masiva →
      </a>
    </div>

    <div class="form-group">
      <label>Imagen de fondo del título (PNG apaisado recomendado)</label>
      <input type="file" name="cert_bg" accept="image/png,image/jpeg" id="cert-bg-input">
      <?php
        $certBgUrl = '';
        if (!empty($s['cert_bg_path'])) {
            $certBgUrl = rtrim(BASE_URL, '/') . $s['cert_bg_path'];
        }
      ?>
      <div id="cert-bg-preview" style="margin-top:.75rem<?= $certBgUrl ? '' : ';display:none' ?>">
        <img id="cert-bg-thumb" src="<?= htmlspecialchars($certBgUrl) ?>" alt="Fondo título actual"
             style="max-width:320px;border-radius:6px;border:1px solid var(--color-border)">
        <p style="font-size:.8rem;color:var(--color-text-muted);margin-top:.3rem">Imagen actual guardada</p>
      </div>
    </div>

    <div class="form-grid">
      <div class="form-group"><label>Posición X (px)</label><input type="number" name="cert_name_x" value="<?= htmlspecialchars($s['cert_name_x'] ?? '400') ?>"></div>
      <div class="form-group"><label>Posición Y (px)</label><input type="number" name="cert_name_y" value="<?= htmlspecialchars($s['cert_name_y'] ?? '300') ?>"></div>
      <div class="form-group"><label>Tamaño fuente (px)</label><input type="number" name="cert_name_fontsize" value="<?= htmlspecialchars($s['cert_name_fontsize'] ?? $s['certificate_name_font_size'] ?? '36') ?>"></div>
      <div class="form-group">
        <label>Color texto</label>
        <div style="display:flex;align-items:center;gap:.5rem">
          <input type="color" id="pick-cert-color" name="cert_name_color"
                 value="<?= htmlspecialchars($s['cert_name_color'] ?? '#000000') ?>"
                 style="width:44px;height:36px;padding:2px;border-radius:6px;cursor:pointer;border:1px solid var(--color-border)">
          <input type="text" id="hex-cert-color"
                 value="<?= htmlspecialchars($s['cert_name_color'] ?? '#000000') ?>"
                 placeholder="#000000" maxlength="7" style="width:90px">
        </div>
      </div>
    </div>

    <div style="margin-top:1rem">
      <button type="button" class="btn btn-secondary" onclick="previewCertificate()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:.3rem"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Vista previa del título
      </button>
    </div>

    <div id="cert-preview-wrap" style="display:none;margin-top:1rem">
      <canvas id="cert-canvas" style="max-width:100%;border-radius:8px;border:1px solid var(--color-border);box-shadow:var(--shadow-md)"></canvas>
      <p style="font-size:.8rem;color:var(--color-text-muted);margin-top:.5rem">
        Vista previa con nombre de ejemplo · Ajusta coordenadas y pulsa de nuevo.
      </p>
    </div>

  </div>
</details>

<!-- BOTÓN SUBMIT -->
<div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid var(--color-border)">
  <button type="submit" class="btn btn-primary">💾 Guardar todos los ajustes</button>
</div>

</form>

<!-- TOKENS API -->
<details class="settings-section" style="margin-top:1.5rem">
  <summary>🔑 Tokens API</summary>
  <div class="settings-body">
    <table class="data-table" style="margin-bottom:1rem">
      <thead><tr><th>Etiqueta</th><th>Últimos caracteres</th><th>Creado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($apiTokens as $t): ?>
      <tr>
        <td><?= htmlspecialchars($t['label']) ?></td>
        <td><code>...<?= htmlspecialchars(substr($t['token'], -8)) ?></code></td>
        <td><?= date('d/m/Y', strtotime($t['created_at'])) ?></td>
        <td>
          <form method="POST" action="<?= BASE_URL ?>/admin/settings/token/<?= (int)$t['id'] ?>/eliminar" style="display:inline">
            <?= \Csrf::field() ?>
            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('&iquest;Eliminar token?')">Eliminar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($apiTokens)): ?>
        <tr><td colspan="4" class="empty-row">Sin tokens generados</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    <form method="POST" action="<?= BASE_URL ?>/admin/settings/token/crear" style="display:flex;gap:.75rem;align-items:flex-end">
      <?= \Csrf::field() ?>
      <div class="form-group" style="margin:0;flex:1">
        <label>Etiqueta del nuevo token</label>
        <input type="text" name="label" required placeholder="Mi aplicación">
      </div>
      <button type="submit" class="btn btn-primary">Generar token</button>
    </form>
  </div>
</details>

<script>
// ─ Ojos en campos password ────────────────────────────────────────────────────
document.querySelectorAll('.pwd-eye').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var inp = document.getElementById(this.dataset.target);
    if (!inp) return;
    var show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    this.innerHTML = show ? eyeClosed() : eyeOpen();
  });
});
function eyeOpen() {
  return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
}
function eyeClosed() {
  return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
}

// ─ Colores ─────────────────────────────────────────────────────────────────────
function syncColorPair(pickerId, hexId) {
  var picker = document.getElementById(pickerId);
  var hex    = document.getElementById(hexId);
  if (!picker || !hex) return;
  picker.addEventListener('input', function() { hex.value = this.value; });
  hex.addEventListener('input', function() {
    var v = this.value.trim();
    if (!v.startsWith('#')) v = '#' + v;
    if (/^#[0-9a-fA-F]{6}$/.test(v)) picker.value = v;
  });
}
syncColorPair('pick-brand',      'hex-brand');
syncColorPair('pick-cert-color', 'hex-cert-color');
document.getElementById('hex-cert-color')?.addEventListener('input', function() {
  var v = this.value.trim();
  if (!v.startsWith('#')) v = '#' + v;
  if (/^#[0-9a-fA-F]{6}$/.test(v)) document.getElementById('pick-cert-color').value = v;
});

// ─ Mostrar/ocultar días personalizados ───────────────────────────────────────
document.getElementById('exam-schedule-select')?.addEventListener('change', function() {
  var wrap = document.getElementById('exam-custom-days-wrap');
  if (wrap) wrap.style.display = this.value === 'custom_days' ? 'block' : 'none';
});

// ─ Preview de certificado ──────────────────────────────────────────────────
document.getElementById('cert-bg-input')?.addEventListener('change', function() {
  var file = this.files[0];
  if (!file) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    var thumb = document.getElementById('cert-bg-thumb');
    var wrap  = document.getElementById('cert-bg-preview');
    if (thumb) thumb.src = e.target.result;
    if (wrap)  wrap.style.display = '';
    if (document.getElementById('cert-preview-wrap').style.display !== 'none') previewCertificate(e.target.result);
  };
  reader.readAsDataURL(file);
});

function previewCertificate(overrideSrc) {
  var savedBg  = <?= json_encode(!empty($s['cert_bg_path']) ? rtrim(BASE_URL, '/') . $s['cert_bg_path'] : '') ?>;
  var bgSrc    = overrideSrc || savedBg || '';
  var posX     = parseInt(document.querySelector('[name=cert_name_x]').value)       || 400;
  var posY     = parseInt(document.querySelector('[name=cert_name_y]').value)       || 300;
  var fontSize = parseInt(document.querySelector('[name=cert_name_fontsize]').value) || 36;
  var color    = document.getElementById('pick-cert-color').value                  || '#000000';
  var wrap     = document.getElementById('cert-preview-wrap');
  var canvas   = document.getElementById('cert-canvas');
  var ctx      = canvas.getContext('2d');
  wrap.style.display = 'block';
  function drawName() {
    ctx.font = 'bold ' + fontSize + 'px Inter, sans-serif';
    ctx.fillStyle = color;
    ctx.textAlign = 'left';
    ctx.fillText('Alumno/a de Ejemplo', posX, posY);
  }
  if (bgSrc) {
    var img = new Image();
    if (!overrideSrc) img.crossOrigin = 'anonymous';
    img.onload = function() {
      canvas.width = img.naturalWidth; canvas.height = img.naturalHeight;
      ctx.drawImage(img, 0, 0); drawName();
    };
    img.onerror = function() { drawPlaceholder(ctx, canvas); drawName(); };
    img.src = bgSrc;
  } else {
    drawPlaceholder(ctx, canvas); drawName();
  }
}
function drawPlaceholder(ctx, canvas) {
  canvas.width = 1200; canvas.height = 600;
  ctx.fillStyle = '#f3f0ec'; ctx.fillRect(0, 0, 1200, 600);
  ctx.fillStyle = '#aaa'; ctx.font = '22px Inter,sans-serif'; ctx.textAlign = 'center';
  ctx.fillText('(sin imagen de fondo — sube un PNG en el campo de arriba)', 600, 300);
}
</script>

<?php
function eyeIcon(): string {
  return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
}
?>

<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>
