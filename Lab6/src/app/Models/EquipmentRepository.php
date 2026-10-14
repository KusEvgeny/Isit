<?php
// app/Models/EquipmentRepository.php — модель оборудования. Единственное место, которое знает, где и как хранится оборудование.
// Сейчас задачи лежат в файле storage/tasks.json. В следующих работах файл заменит база данных,
// а контроллеры и шаблоны при этом менять не придётся: они работают только с методами этого класса.

declare(strict_types=1);

namespace App\Models;

final class EquipmentRepository
{
    private string $file;

    public function __construct()
    {
        $this->file = BASE_PATH . '/storage/equipment.json';
    }

    // Всё оборудование
    public function all(): array
    {
        return json_decode(file_get_contents($this->file), true) ?? [];
    }

    // Оборудование по id или null, если такого нет
    public function find(int $id): ?array
    {
        foreach ($this->all() as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }

    // Оборудование одного сотрудника
    public function byEmployee(int $employeeId): array
    {
        return array_values(array_filter(
            $this->all(),
            fn(array $item): bool => $item['employee_id'] === $employeeId
        ));
    }

    // Добавляет оборудование, присваивает ему новый номер и сохраняет файл.
    public function add(array $data): array
    {
        $items = $this->all();
        $ids = array_column($items, 'id');
        $item = ['id' => ($ids ? max($ids) : 0) + 1] + $data;
        $items[] = $item;

        file_put_contents(
            $this->file,
            json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );
        return $item;
    }
}