<?php
// public/index.php — единая точка входа (front controller).
// Веб-сервер отправляет сюда ВСЕ запросы, а дальше работает наш маршрутизатор.
// Расставьте по порядку то, что описано в TODO. Сейчас файл только показывает заглушку.

declare(strict_types=1);


define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

require_once APP_PATH . '/autoload.php';
require_once APP_PATH . '/helpers.php';

$router = new App\Core\Router();

require BASE_PATH . '/routes/web.php';

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Throwable $e) {
    http_response_code(500);
    view('errors/500', ['message' => $e->getMessage()]);
}

