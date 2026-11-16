<?php
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
require APP_PATH . '/autoload.php';
require APP_PATH . '/helpers.php';

$pdo = App\Core\Database::connection();

// Генерируем ПРАВИЛЬНЫЙ хеш для пароля 123456
$newHash = password_hash('123456', PASSWORD_DEFAULT);

// Записываем его в базу
$stmt = $pdo->prepare("UPDATE employees SET password_hash = :hash WHERE email = 'anna@example.com'");
$stmt->execute(['hash' => $newHash]);

// Сразу же проверяем, что записалось
$stmt = $pdo->query("SELECT password_hash FROM employees WHERE email = 'anna@example.com'");
$savedHash = $stmt->fetchColumn();

echo "Новый хеш: " . $savedHash . "<br>";
echo "Проверка: " . (password_verify('123456', $savedHash) ? "ПОДХОДИТ" : "НЕ ПОДХОДИТ");