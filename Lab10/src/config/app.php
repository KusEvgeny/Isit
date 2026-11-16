<?php
// config/app.php — настройки приложения. Файл возвращает массив.

return [
    'name'         => 'Учёт офисного оборудования',
    'debug'        => true,
    'token_ttl'    => 7200,
    'cors_origins' => [],

    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 5432,               // или 5434, если у тебя Docker на другом порту
        'name'     => 'taskapp',
        'user'     => 'postgres',
        'password' => 'secret',
    ],

    // Настройки кеша
    'cache' => [
        'enabled' => true,                // Включаем кеш
        'host'    => '127.0.0.1',
        'port'    => 6379,
        'prefix'  => 'office:',
        'timeout' => 0.5,
    ],
];