<?php
// app/Controllers/Api/EquipmentApiController.php — REST API ресурса «оборудование».

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\EquipmentRepository;
use App\Models\EmployeeRepository;

final class EquipmentApiController
{
    private EquipmentRepository $equipment;
    private EmployeeRepository $employees;

    public function __construct()
    {
        $this->equipment = new EquipmentRepository();
        $this->employees = new EmployeeRepository();
    }

    // GET /api/equipment
    public function index(): void
    {
        $items = $this->equipment->all();
        
        $status = $_GET['status'] ?? '';
        if ($status !== '') {
            $items = array_values(array_filter($items, fn(array $i): bool => $i['status'] === $status));
        }
        
        $employeeId = $_GET['employee_id'] ?? '';
        if ($employeeId !== '') {
            $items = array_values(array_filter($items, fn(array $i): bool => $i['employee_id'] === (int) $employeeId));
        }
        
        json_response($items);
    }

    // GET /api/equipment/5
    public function show(string $id): void
    {
        $item = $this->equipment->find((int) $id);
        if ($item === null) {
            json_error('Оборудование не найдено', 404);
        }
        json_response($item);
    }

    // POST /api/equipment
    public function store(): void
    {
        $data = json_input();
        $data += ['employee_id' => 0, 'status' => 'in_use', 'category' => 'computer'];
        
        $errors = $this->validate($data);
        if ($errors) {
            json_error('Ошибка проверки данных', 422, $errors);
        }
        
        $item = $this->equipment->add($this->clean($data));
        header('Location: /api/equipment/' . $item['id']);
        json_response($item, 201);
    }

    // PUT или PATCH /api/equipment/5
    public function update(string $id): void
    {
        $item = $this->equipment->find((int) $id);
        if ($item === null) {
            json_error('Оборудование не найдено', 404);
        }
        
        $data = array_merge($item, json_input());
        $errors = $this->validate($data);
        if ($errors) {
            json_error('Ошибка проверки данных', 422, $errors);
        }
        
        $updated = $this->equipment->update($item['id'], $this->clean($data));
        json_response($updated);
    }

    // DELETE /api/equipment/5
    public function destroy(string $id): void
    {
        if (!$this->equipment->delete((int) $id)) {
            json_error('Оборудование не найдено', 404);
        }
        no_content();
    }

    private function clean(array $data): array
    {
        return [
            'employee_id' => (int) $data['employee_id'],
            'number'      => trim($data['number']),
            'title'       => trim($data['title']),
            'category'    => $data['category'],
            'status'      => $data['status'],
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (!is_string($data['title'] ?? null) || mb_strlen(trim($data['title'])) < 3) {
            $errors['title'] = 'Название — не короче 3 символов';
        }
        if (!is_string($data['number'] ?? null) || trim($data['number']) === '') {
            $errors['number'] = 'Укажите инвентарный номер';
        }
        if (!in_array($data['category'] ?? null, ['computer', 'monitor', 'printer', 'peripheral'], true)) {
            $errors['category'] = 'Категория: computer, monitor, printer или peripheral';
        }
        if (!in_array($data['status'] ?? null, ['in_use', 'in_repair', 'archived'], true)) {
            $errors['status'] = 'Статус: in_use, in_repair или archived';
        }
        if (!empty($data['employee_id'])) {
            if ($this->employees->find((int) $data['employee_id']) === null) {
                $errors['employee_id'] = 'Сотрудника с таким номером нет';
            }
        }
        return $errors;
    }
}