<?php
// app/Models/EmployeeRepository.php — модель сотрудников.

declare(strict_types=1);

namespace App\Models;

final class EmployeeRepository
{
    private string $file;

    public function __construct()
    {
        $this->file = BASE_PATH . '/storage/employees.json';
    }

    public function all(): array
    {
        return json_decode(file_get_contents($this->file), true) ?? [];
    }

    public function find(int $id): ?array
    {
        foreach ($this->all() as $employee) {
            if ($employee['id'] === $id) {
                return $employee;
            }
        }
        return null;
    }
}