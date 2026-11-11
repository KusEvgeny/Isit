<?php
// Шаблон списка оборудования. Переменные: $items (массив), $status (выбранный фильтр)

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
<h1>Оборудование</h1>

<p class="filters">
  Показать:
  <a href="/equipment" class="<?= $status === '' ? 'active' : '' ?>">все</a>
  <?php foreach ($statusNames as $code => $name): ?>
    <a href="/equipment?status=<?= e($code) ?>" class="<?= $status === $code ? 'active' : '' ?>"><?= e($name) ?></a>
  <?php endforeach; ?>
</p>

<?php if (count($items) === 0): ?>
  <p class="empty">Оборудование не найдено.</p>
<?php else: ?>
  <table>
    <thead>
      <tr><th>№</th><th>Номер</th><th>Название</th><th>Категория</th><th>Статус</th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= e($item['id']) ?></td>
          <td><?= e($item['number']) ?></td>
          <td><a href="/equipment/<?= e($item['id']) ?>"><?= e($item['title']) ?></a></td>
          <td><?= e($categoryNames[$item['category']] ?? $item['category']) ?></td>
          <td><span class="badge badge-<?= e($item['status']) ?>"><?= e($statusNames[$item['status']] ?? $item['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>