/* RSGrup — app.js */
(function () {
  'use strict';

  /* ================================================================
     MODALS
     Uso: openModal('modal-id') / closeModal('modal-id')
     El elemento modal debe tener el atributo [hidden] y clase .modal
  ================================================================ */
  window.openModal = function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.removeAttribute('hidden');
    el.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    // Focus en el primer campo interactivo del modal
    setTimeout(function () {
      var first = el.querySelector('input:not([type=hidden]), select, textarea, button:not(.modal-close)');
      if (first) first.focus();
    }, 50);
  };

  window.closeModal = function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.setAttribute('hidden', '');
    el.classList.remove('is-open');
    document.body.style.overflow = '';
  };

  // Cerrar con Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal.is-open').forEach(function (m) {
        closeModal(m.id);
      });
    }
  });

  /* ================================================================
     TINYMCE — inicialización centralizada
     Se aplica a todos los elementos con clase .wysiwyg-editor
     Callback window.onTinyMceReady(editorId) notifica a los modales
  ================================================================ */
  function initTinyMCE() {
    if (typeof tinymce === 'undefined') return;

    // Destruir instancias antiguas antes de re-inicializar (evita duplicados)
    if (tinymce.editors && tinymce.editors.length) {
      tinymce.remove('.wysiwyg-editor');
    }

    tinymce.init({
      selector: '.wysiwyg-editor',
      language: 'es',
      language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@23.10.9/langs6/es.js',
      plugins: 'lists link image code table',
      toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image | code',
      menubar: false,
      branding: false,
      promotion: false,
      height: 260,
      skin: document.documentElement.getAttribute('data-theme') === 'dark' ? 'oxide-dark' : 'oxide',
      content_css: document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'default',
      setup: function (editor) {
        editor.on('init', function () {
          // Notificar a los templates que TinyMCE está listo para este editor
          if (typeof window.onTinyMceReady === 'function') {
            window.onTinyMceReady(editor.id);
          }
        });
      }
    });
  }

  /* ================================================================
     LUCIDE ICONS
  ================================================================ */
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

  /* ================================================================
     THEME TOGGLE
  ================================================================ */
  var root   = document.documentElement;
  var toggle = document.querySelector('[data-theme-toggle]');

  function prefersDark() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

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

    initLucide();

    /* ---- TinyMCE: inicializar si hay editores en la página ---- */
    if (document.querySelector('.wysiwyg-editor')) {
      initTinyMCE();
    }

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
    document.querySelectorAll('.flash').forEach(function (el) {
      setTimeout(function () {
        el.style.transition = 'opacity 0.4s';
        el.style.opacity    = '0';
        setTimeout(function () { el.remove(); }, 420);
      }, 3000);
    });

    /* ---- Confirm dialogs ---- */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        if (!window.confirm(el.dataset.confirm || '\u00bfEstas seguro?')) {
          e.preventDefault();
        }
      });
    });
  });
})();
