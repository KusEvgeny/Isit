<?php
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
require APP_PATH . '/autoload.php';
require APP_PATH . '/helpers.php';

$pdo = App\Core\Database::connection();

// Генерируем правильный хеш для пароля 123456
$newHash = password_hash('123456', PASSWORD_DEFAULT);

// Обновляем пароль для всех сотрудников
$stmt = $pdo->prepare("UPDATE employees SET password_hash = :hash");
$stmt->execute(['hash' => $newHash]);

echo "Пароли для всех сотрудников обновлены!\n";
echo "Новый хеш: " . $newHash . "\n";