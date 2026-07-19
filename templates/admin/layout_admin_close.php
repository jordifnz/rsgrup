<?php // templates/admin/layout_admin_close.php ?>
    </div><!-- /.admin-content -->
  </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<script>
// ── Theme toggle ──────────────────────────────────────────────
(function(){
  var html       = document.documentElement;
  var btn        = document.getElementById('theme-toggle');
  var iconSun    = document.getElementById('icon-sun');
  var iconMoon   = document.getElementById('icon-moon');
  var labelEl    = document.getElementById('theme-label');

  function applyTheme(t) {
    html.setAttribute('data-theme', t);
    localStorage.setItem('rsgrup-theme', t);
    if (t === 'dark') {
      iconSun.style.display  = 'none';
      iconMoon.style.display = 'block';
      if (labelEl) labelEl.textContent = 'Tema claro';
    } else {
      iconSun.style.display  = 'block';
      iconMoon.style.display = 'none';
      if (labelEl) labelEl.textContent = 'Tema oscuro';
    }
  }

  // Inicializar icono según el tema ya aplicado (inline en <head>)
  applyTheme(html.getAttribute('data-theme') || 'light');

  if (btn) {
    btn.addEventListener('click', function(){
      var current = html.getAttribute('data-theme') || 'light';
      applyTheme(current === 'dark' ? 'light' : 'dark');
    });
  }
})();

// ── Lucide icons ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function(){
  if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
</body>
</html>
