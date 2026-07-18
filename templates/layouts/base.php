<?php
// templates/layouts/base.php
// Variables esperadas: $metaTitle (string), $robots (string, opcional)
if (!defined('BASE_PATH')) { header('Location: /'); exit; }
$robots = $robots ?? 'noindex,nofollow';
$metaTitle = ($metaTitle ?? 'RSGrup') . ' | RSGrup';
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($metaTitle) ?></title>
  <meta name="robots" content="<?= htmlspecialchars($robots) ?>">
  <meta name="description" content="RSGrup - Plataforma de formación online">
  <!-- Fonts -->
  <link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700&f[]=cabinet-grotesk@400,500,700,800&display=swap" rel="stylesheet">
  <!-- Icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <!-- TinyMCE CDN (solo se carga cuando se necesita) -->
  <!-- CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<?php include BASE_PATH . '/templates/partials/header.php'; ?>
<main id="main-content" class="main-content">
<?php // El contenido de cada página va aquí ?>