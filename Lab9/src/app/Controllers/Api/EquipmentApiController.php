<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Models\EquipmentRepository;

final class EquipmentApiController
{
    private EquipmentRepository $equipment;

    public function __construct()
    {
        $this->equipment = new EquipmentRepository();
    }

    public function index(): void
    {
        json_response($this->equipment->all());
    }

    public function show(string $id): void
    {
        $item = $this->equipment->find((int) $id);
        if ($item === null) {
            json_error('Оборудование не найдено', 404);
        }
        json_response($item);
    }

    public function store(): void
    {
        $data = json_input();
        $data += ['status' => 'in_use', 'employee_id' => null];
        
        $errors = [];
        if (empty($data['title'])) $errors['title'] = 'Введите название';
        if (empty($data['inventory_number'])) $errors['inventory_number'] = 'Введите инвентарный номер';
        if (empty($data['category_id'])) $errors['category_id'] = 'Выберите категорию';

        if ($errors) {
            json_error('Ошибка проверки данных', 422, $errors);
        }

        $item = $this->equipment->add([
            'category_id'      => (int) $data['category_id'],
            'employee_id'      => !empty($data['employee_id']) ? (int) $data['employee_id'] : null,
            'inventory_number' => trim($data['inventory_number']),
            'title'            => trim($data['title']),
            'status'           => $data['status']
        ]);
        
        header('Location: /api/equipment/' . $item['id']);
        json_response($item, 201);
    }

    public function update(string $id): void
    {
        $item = $this->equipment->find((int) $id);
        if ($item === null) {
            json_error('Оборудование не найдено', 404);
        }
        
        $data = array_merge($item, json_input());
        
        $updated = $this->equipment->update($item['id'], [
            'category_id'      => (int) $data['category_id'],
            'employee_id'      => !empty($data['employee_id']) ? (int) $data['employee_id'] : null,
            'inventory_number' => trim($data['inventory_number']),
            'title'            => trim($data['title']),
            'status'           => $data['status']
        ]);
        
        json_response($updated);
    }

    public function destroy(string $id): void
    {
        require_role('manager');
        if (!$this->equipment->delete((int) $id)) {
            json_error('Оборудование не найдено', 404);
        }
        no_content();
    }
}