<?php // templates/admin/layout_admin_close.php ?>
    </div><!-- /.admin-content -->
  </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<!-- Modal genérico reutilizable -->
<div id="modal-overlay" class="modal-overlay" style="display:none" onclick="if(event.target===this)closeModal()">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal()" aria-label="Cerrar">&times;</button>
    <div id="modal-body"></div>
  </div>
</div>

<script>
// ── openModal / closeModal ─────────────────────────────────────
function openModal(id) {
  var tpl = document.getElementById(id);
  if (!tpl) return;
  document.getElementById('modal-body').innerHTML = tpl.innerHTML;
  var overlay = document.getElementById('modal-overlay');
  overlay.style.display = 'flex';
  document.body.style.overflow = 'hidden';
  // Re-init TinyMCE dentro del modal si hay textareas wysiwyg
  if (typeof tinymce !== 'undefined') {
    tinymce.remove('.wysiwyg');
    tinymce.init(window._tinymceConfig || { selector: '.wysiwyg', height: 250, menubar: false,
      plugins: 'lists link', toolbar: 'bold italic underline | bullist numlist | link' });
  }
  // Ejecutar scripts del template
  var scripts = document.getElementById('modal-body').querySelectorAll('script');
  scripts.forEach(function(s){ eval(s.textContent); });
}
function closeModal() {
  document.getElementById('modal-overlay').style.display = 'none';
  document.body.style.overflow = '';
  if (typeof tinymce !== 'undefined') tinymce.remove('.wysiwyg');
}

// ── Theme toggle ──────────────────────────────────────────────
(function(){
  var html    = document.documentElement;
  var btn     = document.getElementById('theme-toggle');
  var iconSun = document.getElementById('icon-sun');
  var iconMoon= document.getElementById('icon-moon');
  var labelEl = document.getElementById('theme-label');

  function applyTheme(t) {
    html.setAttribute('data-theme', t);
    localStorage.setItem('rsgrup-theme', t);
    if (t === 'dark') {
      if(iconSun)  iconSun.style.display  = 'none';
      if(iconMoon) iconMoon.style.display = 'block';
      if(labelEl)  labelEl.textContent    = 'Tema claro';
    } else {
      if(iconSun)  iconSun.style.display  = 'block';
      if(iconMoon) iconMoon.style.display = 'none';
      if(labelEl)  labelEl.textContent    = 'Tema oscuro';
    }
  }
  applyTheme(html.getAttribute('data-theme') || 'light');
  if (btn) {
    btn.addEventListener('click', function(){
      applyTheme((html.getAttribute('data-theme')||'light')==='dark'?'light':'dark');
    });
  }
})();

// ── Lucide icons ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function(){
  if (typeof lucide !== 'undefined') lucide.createIcons();
});

// ── Toggle password visibility ────────────────────────────────
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.toggle-password').forEach(function(btn){
    btn.addEventListener('click', function(){
      var inp = document.querySelector(btn.dataset.target);
      if (!inp) return;
      inp.type = inp.type === 'password' ? 'text' : 'password';
      btn.querySelector('svg.eye-open').style.display  = inp.type === 'text'    ? 'none'  : 'block';
      btn.querySelector('svg.eye-close').style.display = inp.type === 'password'? 'none'  : 'block';
    });
  });
});
</script>
</body>
</html>
