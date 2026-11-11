<?php
// app/Controllers/Api/EmployeeApiController.php — REST API ресурса «сотрудники».

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\EmployeeRepository;
use App\Models\EquipmentRepository;

final class EmployeeApiController
{
    private EmployeeRepository $employees;
    private EquipmentRepository $equipment;

    public function __construct()
    {
        $this->employees = new EmployeeRepository();
        $this->equipment = new EquipmentRepository();
    }

    // GET /api/employees
    public function index(): void
    {
        json_response($this->employees->all());
    }

    // GET /api/employees/2
    public function show(string $id): void
    {
        $employee = $this->employees->find((int) $id);
        if ($employee === null) {
            json_error('Сотрудник не найден', 404);
        }
        json_response($employee);
    }

    // GET /api/employees/2/equipment — вложенный ресурс
    public function equipment(string $id): void
    {
        if ($this->employees->find((int) $id) === null) {
            json_error('Сотрудник не найден', 404);
        }
        json_response($this->equipment->byEmployee((int) $id));
    }

    // POST /api/employees
    public function store(): void
    {
        $data = json_input();
        $errors = $this->validate($data);
        if ($errors) {
            json_error('Ошибка проверки данных', 422, $errors);
        }
        
        $employee = $this->employees->add($this->clean($data));
        header('Location: /api/employees/' . $employee['id']);
        json_response($employee, 201);
    }

    // PUT или PATCH /api/employees/2
    public function update(string $id): void
    {
        $employee = $this->employees->find((int) $id);
        if ($employee === null) {
            json_error('Сотрудник не найден', 404);
        }
        
        $data = array_merge($employee, json_input());
        $errors = $this->validate($data);
        if ($errors) {
            json_error('Ошибка проверки данных', 422, $errors);
        }
        
        $updated = $this->employees->update($employee['id'], $this->clean($data));
        json_response($updated);
    }

    // DELETE /api/employees/2
    public function destroy(string $id): void
    {
        if ($this->employees->find((int) $id) === null) {
            json_error('Сотрудник не найден', 404);
        }
        if (!empty($this->equipment->byEmployee((int) $id))) {
            json_error('Нельзя удалить сотрудника: за ним закреплено оборудование', 409);
        }
        
        $this->employees->delete((int) $id);
        no_content();
    }

    private function clean(array $data): array
    {
        return [
            'name'     => trim($data['name']),
            'position' => trim($data['position'] ?? ''),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (!is_string($data['name'] ?? null) || mb_strlen(trim($data['name'])) < 2) {
            $errors['name'] = 'Имя — не короче 2 символов';
        }
        return $errors;
    }
}