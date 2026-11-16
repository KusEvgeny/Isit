<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

require APP_PATH . '/autoload.php';
require APP_PATH . '/helpers.php';

use App\Core\Cache;

// Функция, которая "долго" (1 секунду) получает данные из базы
$slowLoad = function () {
    sleep(1); // имитация долгого запроса к БД
    return ['id' => 1, 'title' => 'Компьютер Lenovo', 'status' => 'in_use'];
};

echo "1. Первый вызов (должен быть MISS):\n";
$start = microtime(true);
$data1 = Cache::remember('demo:slow', 30, $slowLoad);
$time1 = round(microtime(true) - $start, 3);
echo "Время: {$time1} сек, Статус: " . Cache::status() . "\n";
print_r($data1);

echo "\n2. Второй вызов (должен быть HIT):\n";
$start = microtime(true);
$data2 = Cache::remember('demo:slow', 30, $slowLoad);
$time2 = round(microtime(true) - $start, 3);
echo "Время: {$time2} сек, Статус: " . Cache::status() . "\n";
print_r($data2);

echo "\n3. Третий вызов (должен быть HIT):\n";
$start = microtime(true);
$data3 = Cache::remember('demo:slow', 30, $slowLoad);
$time3 = round(microtime(true) - $start, 3);
echo "Время: {$time3} сек, Статус: " . Cache::status() . "\n";