<?php
// app/Controllers/EmployeeController.php — контроллер сотрудников.

declare(strict_types=1);

namespace App\Controllers;

use App\Models\EmployeeRepository;
use App\Models\EquipmentRepository;

final class EmployeeController
{
    // GET /employees
    public function index(): void
    {
        $employees = (new EmployeeRepository())->all();

        view('employees/index', [
            'title'     => 'Сотрудники',
            'employees' => $employees,
        ]);
    }

    // GET /employees/{id}
    public function show(string $id): void
    {
        $employee = (new EmployeeRepository())->find((int) $id);

        if (!$employee) {
            notFound();
        }

        $items = (new EquipmentRepository())->byEmployee($employee['id']);

        view('employees/show', [
            'title'    => $employee['name'],
            'employee' => $employee,
            'items'    => $items,
        ]);
    }
}