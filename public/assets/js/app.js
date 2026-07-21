/* RSGrup — app.js */
(function () {
  'use strict';

  /* ================================================================
     MODALS
     openModal(id)  — quita [hidden], bloquea scroll, foco al primer campo
     closeModal(id) — destruye TinyMCE dentro, restaura [hidden]
  ================================================================ */
  window.openModal = function (id) {
    var el = document.getElementById(id);
    if (!el) { console.warn('openModal: no element #' + id); return; }
    el.removeAttribute('hidden');
    el.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    setTimeout(function () {
      var first = el.querySelector('input:not([type=hidden]), select');
      if (first) first.focus();
    }, 60);
  };

  window.closeModal = function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    if (window.tinymce) {
      el.querySelectorAll('textarea').forEach(function (ta) {
        if (ta.id) { var ed = tinymce.get(ta.id); if (ed) ed.remove(); }
      });
    }
    el.setAttribute('hidden', '');
    el.classList.remove('is-open');
    document.body.style.overflow = '';
  };

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal.is-open').forEach(function (m) {
        window.closeModal(m.id);
      });
    }
  });

  /* ================================================================
     TINYMCE
     initEditorInModal(editorId, content)
     — Llama DESPUÉS de openModal(), cuando el textarea ya es visible.
     — Destruye instancia previa, crea nueva, setea contenido en init.
  ================================================================ */
  window.initEditorInModal = function (editorId, content) {
    if (typeof tinymce === 'undefined') {
      var ta = document.getElementById(editorId);
      if (ta) ta.value = content || '';
      return;
    }
    var existing = tinymce.get(editorId);
    if (existing) existing.remove();

    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    tinymce.init({
      selector     : '#' + editorId,
      language     : 'es',
      language_url : 'https://cdn.jsdelivr.net/npm/tinymce-i18n@23.10.9/langs6/es.js',
      plugins      : 'lists link code table',
      toolbar      : 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | code',
      menubar      : false,
      branding     : false,
      promotion    : false,
      height       : 260,
      skin         : isDark ? 'oxide-dark' : 'oxide',
      content_css  : isDark ? 'dark'       : 'default',
      setup: function (editor) {
        editor.on('init', function () {
          editor.setContent(content || '');
        });
      }
    });
  };

  /* ================================================================
     HELPERS
  ================================================================ */
  window.setSelectVal = function (selectId, val) {
    var el = document.getElementById(selectId);
    if (!el) return;
    for (var i = 0; i < el.options.length; i++) {
      if (String(el.options[i].value) === String(val)) { el.selectedIndex = i; return; }
    }
    el.selectedIndex = 0;
  };

  /* ================================================================
     THEME TOGGLE
  ================================================================ */
  (function () {
    var html = document.documentElement;
    var btn  = document.getElementById('theme-toggle');
    var sunEl  = document.getElementById('icon-sun');
    var moonEl = document.getElementById('icon-moon');
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
    if (btn) btn.addEventListener('click', function () {
      applyTheme((html.getAttribute('data-theme') || 'dark') === 'dark' ? 'light' : 'dark');
    });
  })();

  /* ================================================================
     DOM READY
  ================================================================ */
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    document.querySelectorAll('.flash').forEach(function (el) {
      setTimeout(function () {
        el.style.transition = 'opacity 0.4s';
        el.style.opacity    = '0';
        setTimeout(function () { el.remove(); }, 420);
      }, 3000);
    });

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        if (!window.confirm(el.dataset.confirm || '\u00bfEstás seguro?')) e.preventDefault();
      });
    });

    document.querySelectorAll('.toggle-password').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var inp = document.querySelector(btn.dataset.target);
        if (inp) inp.type = inp.type === 'password' ? 'text' : 'password';
      });
    });
  });

})();
