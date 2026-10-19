<?php
// Шаблон формы создания оборудования.
// Переменные: $employees (список сотрудников), $errors (ошибки проверки), $old (прежние значения полей)
?>

<h1>Новое оборудование</h1>

<!-- action — адрес, на который браузер отправит данные; method="post" — способом POST -->
<form action="/equipment" method="post" class="card form">

  <label for="title">Название</label>
  <input id="title" type="text" name="title" value="<?= e($old['title'] ?? '') ?>">
  <?php if (isset($errors['title'])): ?><div class="error"><?= e($errors['title']) ?></div><?php endif; ?>

  <label for="number">Номер</label>
  <input id="number" type="text" name="number" value="<?= e($old['number'] ?? '') ?>">
  <?php if (isset($errors['number'])): ?><div class="error"><?= e($errors['number']) ?></div><?php endif; ?>

  <label for="category">Категория</label>
  <select id="category" name="category">
    <?php foreach (['computer' => 'Компьютер', 'monitor' => 'Монитор', 'printer' => 'Принтер', 'peripheral' => 'Периферия'] as $code => $name): ?>
      <option value="<?= e($code) ?>" <?= ($old['category'] ?? 'computer') === $code ? 'selected' : '' ?>><?= e($name) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if (isset($errors['category'])): ?><div class="error"><?= e($errors['category']) ?></div><?php endif; ?>

  <label for="status">Статус</label>
  <select id="status" name="status">
    <?php foreach (['in_use' => 'В работе', 'in_repair' => 'На ремонте', 'archived' => 'В архиве'] as $code => $name): ?>
      <option value="<?= e($code) ?>" <?= ($old['status'] ?? 'in_use') === $code ? 'selected' : '' ?>><?= e($name) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if (isset($errors['status'])): ?><div class="error"><?= e($errors['status']) ?></div><?php endif; ?>

  <label for="employee_id">Сотрудник</label>
  <select id="employee_id" name="employee_id">
    <option value="0">Выберите сотрудника…</option>
    <?php foreach ($employees as $employee): ?>
      <option value="<?= e($employee['id']) ?>" <?= ((int)($old['employee_id'] ?? 0)) === $employee['id'] ? 'selected' : '' ?>>
        <?= e($employee['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <?php if (isset($errors['employee_id'])): ?><div class="error"><?= e($errors['employee_id']) ?></div><?php endif; ?>

  <p><button type="submit" class="btn">Создать</button> <a href="/equipment">Отмена</a></p>
</form>