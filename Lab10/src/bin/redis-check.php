<?php
// bin/redis-check.php — проверка подключения к Redis.

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

require APP_PATH . '/autoload.php';
require APP_PATH . '/helpers.php';

$redis = App\Core\Cache::redis();

if ($redis === null) {
    echo "Redis недоступен (работаем без кеша).\n";
    exit(1);
}

try {
    $ping = $redis->ping();
    $info = $redis->info('server');
    echo "Redis отвечает: " . $ping . ", версия " . $info['redis_version'] . "\n";
} catch (Throwable $e) {
    echo "Ошибка при обращении к Redis: " . $e->getMessage() . "\n";
    exit(1);
}