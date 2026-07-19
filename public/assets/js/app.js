'use strict';

// ── Theme toggle ──
(function(){
  const root = document.documentElement;
  const btn  = document.querySelector('[data-theme-toggle]');
  let theme  = localStorage.getItem('rsgrup_theme') ||
               (matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light');

  const icons = {
    dark:  '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
    light: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>'
  };

  function applyTheme(t) {
    root.setAttribute('data-theme', t);
    if (btn) btn.innerHTML = icons[t];
  }

  applyTheme(theme);

  if (btn) {
    btn.addEventListener('click', () => {
      theme = theme === 'dark' ? 'light' : 'dark';
      try { localStorage.setItem('rsgrup_theme', theme); } catch(e){}
      applyTheme(theme);
    });
  }
})();

// ── Toggle password visibility ──
// Uso: onclick="togglePassword('input-id')"
// El botón que lo llama debe tener un <i data-lucide="eye"> o data-lucide="eye-off" dentro.
function togglePassword(inputId) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const isPassword = input.type === 'password';
  input.type = isPassword ? 'text' : 'password';

  // Actualizar icono Lucide si está disponible
  const btn  = input.closest('.input-with-toggle')?.querySelector('.btn-icon');
  if (btn) {
    const icon = btn.querySelector('i[data-lucide]');
    if (icon) {
      icon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
      if (typeof lucide !== 'undefined') lucide.createIcons();
    }
  }
  // Devolver foco al input para buena UX
  input.focus();
}

// ── Modal helpers ──
function openModal(id) {
  const el = document.getElementById(id);
  if (el) { el.hidden = false; document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) { el.hidden = true; document.body.style.overflow = ''; }
}
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal:not([hidden])').forEach(m => {
      m.hidden = true; document.body.style.overflow = '';
    });
  }
});

// ── Active sidebar link ──
(function(){
  const path = window.location.pathname;
  document.querySelectorAll('.sidebar-link').forEach(a => {
    if (a.getAttribute('href') === path ||
        (a.getAttribute('href') !== '/admin' && path.startsWith(a.getAttribute('href')))) {
      a.classList.add('active');
    }
  });
})();

// ── TinyMCE init (loaded from CDN in admin pages) ──
function initTinyMCE(selector) {
  if (typeof tinymce === 'undefined') return;
  tinymce.init({
    selector: selector || '.wysiwyg-editor',
    plugins: 'lists link image code table',
    toolbar: 'undo redo | formatselect | bold italic | bullist numlist | link image | code',
    menubar: false,
    height: 300,
    skin: document.documentElement.getAttribute('data-theme') === 'dark' ? 'oxide-dark' : 'oxide',
    content_css: document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'default',
    promotion: false,
    branding: false,
  });
}
document.addEventListener('DOMContentLoaded', () => initTinyMCE());

// ── Exam editor (admin) — dynamic questions/answers ──
(function(){
  const examForm = document.getElementById('exam-builder-form');
  if (!examForm) return;

  let qIndex = parseInt(examForm.dataset.initialQuestions || '0');

  function addQuestion(data) {
    const qi = qIndex++;
    const div = document.createElement('div');
    div.className = 'question-editor';
    div.dataset.qi = qi;
    div.innerHTML = `
      <div class="question-editor-header">
        <strong>Pregunta ${qi + 1}</strong>
        <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.question-editor').remove();">Eliminar</button>
      </div>
      <div class="form-group">
        <label>Enunciado</label>
        <input type="text" name="questions[${qi}][title]" value="${data?.title||''}" required>
      </div>
      <div class="form-group">
        <label>Descripción adicional (opcional, WYSIWYG)</label>
        <textarea name="questions[${qi}][description]" class="wysiwyg-editor" rows="3">${data?.description||''}</textarea>
      </div>
      <div class="form-group">
        <label>Tipo de respuesta</label>
        <select name="questions[${qi}][type]" onchange="updateAnswerInputs(this,${qi})">
          <option value="radio" ${(data?.answer_type==='radio'||!data)?'selected':''}>Única (radio)</option>
          <option value="checkbox" ${data?.answer_type==='checkbox'?'selected':''}>Múltiple (checkbox)</option>
        </select>
      </div>
      <div class="answers-list" id="answers-${qi}">
        <p class="text-sm text-muted">Respuestas:</p>
      </div>
      <button type="button" class="btn btn-sm" onclick="addAnswer(${qi})">+ Añadir respuesta</button>
    `;
    document.getElementById('questions-container').appendChild(div);
    initTinyMCE(`[name="questions[${qi}][description]"]`);
    if (data?.answers?.length) {
      data.answers.forEach(a => addAnswer(qi, a));
    } else {
      addAnswer(qi); addAnswer(qi);
    }
  }

  window.addAnswer = function(qi, data) {
    const list = document.getElementById('answers-' + qi);
    if (!list) return;
    const typeEl = document.querySelector(`[name="questions[${qi}][type]"]`);
    const type = typeEl ? typeEl.value : 'radio';
    const ai = list.querySelectorAll('.answer-row').length;
    const row = document.createElement('div');
    row.className = 'answer-row';
    row.innerHTML = `
      <label class="checkbox-label" title="Respuesta correcta">
        <input type="checkbox" name="questions[${qi}][answers][${ai}][correct]" value="1" ${data?.is_correct?'checked':''}>
        ✓
      </label>
      <input type="text" name="questions[${qi}][answers][${ai}][text]" placeholder="Texto de la respuesta" value="${data?.answer_text||''}" required>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.answer-row').remove()">✕</button>
    `;
    list.appendChild(row);
  };

  window.updateAnswerInputs = function(sel, qi) {
    // Visual only — actual type stored in hidden field
  };

  const addQBtn = document.getElementById('add-question-btn');
  if (addQBtn) addQBtn.addEventListener('click', () => addQuestion());

  const existing = examForm.dataset.questions;
  if (existing) {
    try { JSON.parse(existing).forEach(addQuestion); } catch(e){}
  } else if (qIndex === 0) {
    addQuestion();
  }
})();

// ── Avatar preview ──
const avatarInput = document.getElementById('avatar');
if (avatarInput) {
  avatarInput.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.querySelector('.avatar-img') || document.querySelector('.avatar-initials');
      if (img) {
        const newImg = document.createElement('img');
        newImg.src = e.target.result;
        newImg.className = 'avatar-img';
        newImg.width = 80; newImg.height = 80;
        newImg.alt = 'Avatar';
        img.replaceWith(newImg);
      }
    };
    reader.readAsDataURL(file);
  });
}

// ── Flash auto-dismiss ──
document.querySelectorAll('.alert, .flash').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity 0.4s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 400);
  }, 4000);
});
