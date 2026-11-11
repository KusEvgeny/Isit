<?php
use App\Controllers\HomeController;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\EquipmentApiController;

$router->get('/', [HomeController::class, 'index']);

$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->get('/api/auth/me', [AuthController::class, 'me']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);

$router->get('/api/equipment', [EquipmentApiController::class, 'index']);
$router->post('/api/equipment', [EquipmentApiController::class, 'store']);
$router->get('/api/equipment/{id}', [EquipmentApiController::class, 'show']);
$router->put('/api/equipment/{id}', [EquipmentApiController::class, 'update']);
$router->patch('/api/equipment/{id}', [EquipmentApiController::class, 'update']);
$router->delete('/api/equipment/{id}', [EquipmentApiController::class, 'destroy']);