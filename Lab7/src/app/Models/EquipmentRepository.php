<?php
// app/Models/EquipmentRepository.php — модель оборудования.

declare(strict_types=1);

namespace App\Models;

final class EquipmentRepository extends JsonRepository
{
    protected string $name = 'equipment';

    // Метод для получения оборудования конкретного сотрудника
    public function byEmployee(int $employeeId): array
    {
        return array_values(array_filter(
            $this->all(),
            fn(array $item): bool => $item['employee_id'] === $employeeId
        ));
    }
}