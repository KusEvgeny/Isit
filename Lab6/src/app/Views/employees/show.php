<?php
// Шаблон просмотра проекта. Переменные: $project (проект), $tasks (задачи этого проекта)
// Шаблон карточки сотрудника. Переменные: $employee, $items
$statusNames = [
    'in_use'    => 'В работе',
    'in_repair' => 'На ремонте',
    'archived'  => 'В архиве',
];
?>
<p><a href="/employees">← Ко всем сотрудникам</a></p>

<div class="card">
  <h1><?= e($employee['name']) ?></h1>
  <p><?= e($employee['position']) ?></p>
</div>

<h2>Оборудование сотрудника (<?= count($items) ?>)</h2>

<?php if (count($items) === 0): ?>
  <p class="empty">За сотрудником не закреплено оборудование.</p>
<?php else: ?>
  <table>
    <thead>
      <tr><th>№</th><th>Номер</th><th>Название</th><th>Статус</th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= e($item['id']) ?></td>
          <td><?= e($item['number']) ?></td>
          <td><a href="/equipment/<?= e($item['id']) ?>"><?= e($item['title']) ?></a></td>
          <td><span class="badge badge-<?= e($item['status']) ?>"><?= e($statusNames[$item['status']] ?? $item['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>