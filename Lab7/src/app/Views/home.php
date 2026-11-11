<?php
// Шаблон главной. Переменные: $total, $byStatus, $employeesCount
?>
<h1>Главная</h1>

<div class="stats">
  <div class="card">
    <div class="stat-value"><?= e($total) ?></div>
    <div class="stat-label">всего оборудования</div>
  </div>
  <div class="card">
    <div class="stat-value"><?= e($byStatus['in_use']) ?></div>
    <div class="stat-label">в работе</div>
  </div>
  <div class="card">
    <div class="stat-value"><?= e($byStatus['in_repair']) ?></div>
    <div class="stat-label">на ремонте</div>
  </div>
  <div class="card">
    <div class="stat-value"><?= e($byStatus['archived']) ?></div>
    <div class="stat-label">в архиве</div>
  </div>
</div>

<p>В системе <strong><?= e($employeesCount) ?></strong> сотрудников. Перейдите к <a href="/equipment">списку оборудования</a> или <a href="/employees">сотрудникам</a>.</p>