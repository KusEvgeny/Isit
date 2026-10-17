<?php
// routes/web.php — таблица маршрутов приложения.

use App\Controllers\HomeController;
use App\Controllers\EmployeeController;
use App\Controllers\EquipmentController;

$router->get('/', [HomeController::class, 'index']);

$router->get('/equipment', [EquipmentController::class, 'index']);
$router->get('/equipment/create', [EquipmentController::class, 'create']);
$router->post('/equipment', [EquipmentController::class, 'store']);
$router->get('/equipment/{id}', [EquipmentController::class, 'show']);

$router->get('/employees', [EmployeeController::class, 'index']);
$router->get('/employees/{id}', [EmployeeController::class, 'show']);