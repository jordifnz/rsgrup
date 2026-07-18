<?php
// Base layout - open. Variables expected: $metaTitle, $robots (optional)
$metaTitle = $metaTitle ?? APP_NAME;
$robots    = $robots    ?? 'noindex,nofollow';
$user      = $_SESSION['user'] ?? null;
$isAdmin   = ($user['role'] ?? '') === ROLE_ADMIN;
$baseUrl   = BASE_URL;
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="<?= htmlspecialchars($robots) ?>">
  <title><?= htmlspecialchars($metaTitle) ?> – <?= APP_NAME ?></title>
  <?php if (!empty($metaDescription)): ?>
  <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
  <?php endif; ?>
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <!-- CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <!-- Lucide icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body>
  <a class="sr-only" href="#main-content">Saltar al contenido</a>
  <?php include BASE_PATH . '/templates/partials/header.php'; ?>
  <main id="main-content" class="main-content">
