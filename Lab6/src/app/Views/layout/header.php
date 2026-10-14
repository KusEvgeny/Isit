<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Страница') ?> — <?= e(config('name')) ?></title>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
  <header class="topbar">
    <a class="brand" href="/"><?= e(config('name')) ?></a>
    <nav>
      <a href="/">Главная</a>
      <a href="/equipment">Оборудование</a>
      <a href="/employees">Сотрудники</a>
      <a href="/equipment/create" class="btn-small">+ Новое оборудование</a>
    </nav>
  </header>
  <main class="container">
