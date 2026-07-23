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

  // ── Sidebar colapso (desktop) ──────────────────────────
  var toggleBtn = document.getElementById('sidebar-toggle');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function(){
      var collapsed = html.classList.toggle('sidebar-collapsed');
      try { localStorage.setItem('rsgrup-sidebar-collapsed', collapsed ? '1' : '0'); } catch(e) {}
    });
  }

  // ── Sidebar drawer (móvil) ─────────────────────────────
  var hamburger = document.getElementById('btn-hamburger');
  var sidebar   = document.getElementById('admin-sidebar');
  var overlay   = document.getElementById('sidebar-overlay');

  function openMobileSidebar() {
    if (sidebar)  sidebar.classList.add('mobile-open');
    if (overlay)  overlay.classList.add('visible');
    document.body.style.overflow = 'hidden';
  }
  function closeMobileSidebar() {
    if (sidebar)  sidebar.classList.remove('mobile-open');
    if (overlay)  overlay.classList.remove('visible');
    document.body.style.overflow = '';
  }

  if (hamburger) hamburger.addEventListener('click', openMobileSidebar);
  if (overlay)   overlay.addEventListener('click',   closeMobileSidebar);

  // Cerrar sidebar móvil al navegar
  if (sidebar) {
    sidebar.querySelectorAll('.sidebar-link').forEach(function(link) {
      link.addEventListener('click', function(){
        if (window.innerWidth < 768) closeMobileSidebar();
      });
    });
  }

  // ── Iconos Lucide ──────────────────────────────────────
  // Se llama directamente (sin DOMContentLoaded) porque este script
  // se ejecuta al final del body y el DOM ya está construido.
  // Lucide se carga sin defer, así que está disponible aquí.
  function initLucide() {
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    } else {
      // Fallback: si por algún motivo aún no cargó, esperar al load
      window.addEventListener('load', function() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
      });
    }
  }
  initLucide();

  // ── Flash messages ─────────────────────────────────────
  document.querySelectorAll('.flash').forEach(function(el){
    setTimeout(function(){
      el.style.transition='opacity 0.4s'; el.style.opacity='0';
      setTimeout(function(){ el.remove(); }, 420);
    }, 3000);
  });

})();
</script>
</body>
</html>
