/* RSGrup — app.js */
(function () {
  'use strict';

  /* ---- Lucide icons ---- */
  function initLucide() {
    if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
      lucide.createIcons();
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLucide);
  } else {
    initLucide();
  }

  /* ---- Theme toggle ---- */
  var root   = document.documentElement;
  var toggle = document.querySelector('[data-theme-toggle]');

  function prefersDark() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

  // Inicializar tema (antes del render para evitar flash)
  var savedTheme = null;
  try { savedTheme = sessionStorage.getItem('rsgrup-theme'); } catch (e) {}
  var theme = savedTheme || (prefersDark() ? 'dark' : 'light');
  root.setAttribute('data-theme', theme);

  function updateToggleIcon(t) {
    if (!toggle) return;
    toggle.setAttribute('aria-label', t === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
    toggle.innerHTML = t === 'dark'
      ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>'
      : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
  }

  document.addEventListener('DOMContentLoaded', function () {
    updateToggleIcon(root.getAttribute('data-theme') || 'light');

    if (toggle) {
      toggle.addEventListener('click', function () {
        var current = root.getAttribute('data-theme') || 'light';
        var next    = current === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        try { sessionStorage.setItem('rsgrup-theme', next); } catch (e) {}
        updateToggleIcon(next);
      });
    }

    /* ---- Re-init Lucide después del DOMContentLoaded (icono toggle recién insertado) ---- */
    initLucide();

    /* ---- Cerrar nav mobile al hacer click fuera ---- */
    document.addEventListener('click', function (e) {
      var nav    = document.querySelector('.header-nav');
      var togBtn = document.querySelector('.nav-toggle');
      if (!nav || !togBtn) return;
      if (!nav.contains(e.target) && !togBtn.contains(e.target)) {
        nav.classList.remove('open');
        togBtn.setAttribute('aria-expanded', 'false');
      }
    });

    /* ---- Flash auto-dismiss (3 s) ---- */
    var flashes = document.querySelectorAll('.flash');
    flashes.forEach(function (el) {
      setTimeout(function () {
        el.style.transition = 'opacity 0.4s';
        el.style.opacity    = '0';
        setTimeout(function () { el.remove(); }, 420);
      }, 3000);
    });

    /* ---- Confirm dialogs ---- */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        if (!window.confirm(el.dataset.confirm || '¿Estas seguro?')) {
          e.preventDefault();
        }
      });
    });
  });
})();
