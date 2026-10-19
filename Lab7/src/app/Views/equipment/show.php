<?php
// Шаблон карточки оборудования. Переменные: $item, $employee
$statusNames = [
    'in_use'    => 'В работе',
    'in_repair' => 'На ремонте',
    'archived'  => 'В архиве',
];
$categoryNames = [
    'computer'   => 'Компьютер',
    'monitor'    => 'Монитор',
    'printer'    => 'Принтер',
    'peripheral' => 'Периферия',
];
?>
<p><a href="/equipment">← К списку оборудования</a></p>

<div class="card">
  <h1><?= e($item['title']) ?></h1>

  <table class="details">
    <tr><td>Номер</td><td><?= e($item['number']) ?></td></tr>
    <tr><td>Категория</td><td><?= e($categoryNames[$item['category']] ?? $item['category']) ?></td></tr>
    <tr><td>Статус</td><td><span class="badge badge-<?= e($item['status']) ?>"><?= e($statusNames[$item['status']] ?? $item['status']) ?></span></td></tr>
    <tr>
      <td>Сотрудник</td>
      <td>
        <?php if ($employee): ?>
          <a href="/employees/<?= e($employee['id']) ?>"><?= e($employee['name']) ?></a>
        <?php else: ?>
          —
        <?php endif; ?>
      </td>
    </tr>
  </table>
</div>