    </main>
  </div>
  <?php include BASE_PATH . '/templates/partials/whatsapp_float.php'; ?>
  <script src="<?= BASE_URL ?>/assets/js/app.js" defer></script>
  <!-- TinyMCE -->
  <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    if (typeof tinymce !== 'undefined') {
      tinymce.init({
        selector: '.wysiwyg-editor',
        language: 'es',
        height: 250,
        menubar: false,
        plugins: 'lists link table code',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link table | code',
        skin: document.documentElement.dataset.theme === 'dark' ? 'oxide-dark' : 'oxide',
        content_css: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'default',
      });
    }
  });
  </script>
</body>
</html>
