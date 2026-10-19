<?php
// Шаблон списка сотрудников. Переменная: $employees
?>

<h1>Сотрудники</h1>

<div class="grid">
  <?php foreach ($employees as $employee): ?>
    <div class="card">
      <h3><a href="/employees/<?= e($employee['id']) ?>"><?= e($employee['name']) ?></a></h3>
      <p><?= e($employee['position']) ?></p>
    </div>
  <?php endforeach; ?>
</div>