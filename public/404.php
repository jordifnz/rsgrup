<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/session.php';
startSession();
http_response_code(404);
$metaTitle = 'Página no encontrada';
$robots    = 'noindex,nofollow';
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($metaTitle) ?> — RSGrup</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<main class="error-page">
  <div class="error-page__inner">
    <div class="error-page__code">404</div>
    <h1 class="error-page__title">Página no encontrada</h1>
    <p class="error-page__desc">La página que buscas no existe o ha sido movida.</p>
    <a href="<?= BASE_URL ?>/dashboard" class="btn btn-primary">Ir al inicio</a>
  </div>
</main>
<script src="<?= BASE_URL ?>/assets/js/app.js" defer></script>
</body>
</html>
