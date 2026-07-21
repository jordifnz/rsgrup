<?php // templates/admin/layout_admin_close.php ?>
    </div><!-- /.admin-content -->
  </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<!-- TinyMCE desde jsDelivr (sin API key) -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>

<script>
(function(){
  var html    = document.documentElement;
  var btn     = document.getElementById('theme-toggle');
  var sunEl   = document.getElementById('icon-sun');
  var moonEl  = document.getElementById('icon-moon');
  var labelEl = document.getElementById('theme-label');

  function applyTheme(t) {
    html.setAttribute('data-theme', t);
    try { localStorage.setItem('rsgrup-theme', t); } catch(e) {}
    if (t === 'dark') {
      if (sunEl)   sunEl.style.display  = 'none';
      if (moonEl)  moonEl.style.display = 'block';
      if (labelEl) labelEl.textContent  = 'Tema claro';
    } else {
      if (sunEl)   sunEl.style.display  = 'block';
      if (moonEl)  moonEl.style.display = 'none';
      if (labelEl) labelEl.textContent  = 'Tema oscuro';
    }
  }
  applyTheme(html.getAttribute('data-theme') || 'dark');
  if (btn) btn.addEventListener('click', function(){
    applyTheme((html.getAttribute('data-theme')||'dark')==='dark' ? 'light' : 'dark');
  });

  document.addEventListener('DOMContentLoaded', function(){
    if (typeof lucide !== 'undefined') lucide.createIcons();
    document.querySelectorAll('.flash').forEach(function(el){
      setTimeout(function(){
        el.style.transition='opacity 0.4s'; el.style.opacity='0';
        setTimeout(function(){ el.remove(); }, 420);
      }, 3000);
    });
  });
})();
</script>
</body>
</html>
