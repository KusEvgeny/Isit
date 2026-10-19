<?php
// routes/web.php — таблица маршрутов приложения.

use App\Controllers\HomeController;
use App\Controllers\EmployeeController;
use App\Controllers\EquipmentController;
use App\Controllers\Api\CategoryApiController;
use App\Controllers\Api\EquipmentApiController;
use App\Controllers\Api\EmployeeApiController;


$router->get('/', [HomeController::class, 'index']);

$router->get('/equipment', [EquipmentController::class, 'index']);
$router->get('/equipment/create', [EquipmentController::class, 'create']);
$router->post('/equipment', [EquipmentController::class, 'store']);
$router->get('/equipment/{id}', [EquipmentController::class, 'show']);

$router->get('/employees', [EmployeeController::class, 'index']);
$router->get('/employees/{id}', [EmployeeController::class, 'show']);

$router->get('/api/categories', [CategoryApiController::class, 'index']);
$router->post('/api/categories', [CategoryApiController::class, 'store']);
$router->get('/api/categories/{id}', [CategoryApiController::class, 'show']);
$router->put('/api/categories/{id}', [CategoryApiController::class, 'update']);
$router->patch('/api/categories/{id}', [CategoryApiController::class, 'update']);
$router->delete('/api/categories/{id}', [CategoryApiController::class, 'destroy']);

// API Оборудования
$router->get('/api/equipment', [EquipmentApiController::class, 'index']);
$router->post('/api/equipment', [EquipmentApiController::class, 'store']);
$router->get('/api/equipment/{id}', [EquipmentApiController::class, 'show']);
$router->put('/api/equipment/{id}', [EquipmentApiController::class, 'update']);
$router->patch('/api/equipment/{id}', [EquipmentApiController::class, 'update']);
$router->delete('/api/equipment/{id}', [EquipmentApiController::class, 'destroy']);

// API Сотрудников
$router->get('/api/employees', [EmployeeApiController::class, 'index']);
$router->post('/api/employees', [EmployeeApiController::class, 'store']);
$router->get('/api/employees/{id}', [EmployeeApiController::class, 'show']);
$router->put('/api/employees/{id}', [EmployeeApiController::class, 'update']);
$router->patch('/api/employees/{id}', [EmployeeApiController::class, 'update']);
$router->delete('/api/employees/{id}', [EmployeeApiController::class, 'destroy']);

// Вложенный ресурс: оборудование сотрудника
$router->get('/api/employees/{id}/equipment', [EmployeeApiController::class, 'equipment']);