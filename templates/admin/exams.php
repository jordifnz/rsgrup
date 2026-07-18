<?php
$metaTitle = 'Exámenes';
include BASE_PATH . '/templates/admin/layout_admin.php';
?>
<div class="section-header">
  <h1>Gestión de Exámenes</h1>
  <a href="<?= BASE_URL ?>/admin/examenes/nuevo" class="btn btn-primary">+ Nuevo examen</a>
</div>
<table class="data-table">
  <thead><tr><th>ID</th><th>Título</th><th>Preguntas</th><th>Entrega vinculada</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($exams as $e): ?>
  <tr>
    <td><?= $e['id'] ?></td>
    <td><?= htmlspecialchars($e['title']) ?></td>
    <td><?= (int)($e['question_count'] ?? 0) ?></td>
    <td><?= htmlspecialchars($e['delivery_title'] ?? '—') ?></td>
    <td class="actions">
      <a href="<?= BASE_URL ?>/admin/examenes/<?= $e['id'] ?>/editar" class="btn btn-sm">Editar</a>
      <form action="<?= BASE_URL ?>/admin/examenes/<?= $e['id'] ?>/eliminar" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
        <?= Csrf::field() ?><button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php include BASE_PATH . '/templates/admin/layout_admin_close.php'; ?>