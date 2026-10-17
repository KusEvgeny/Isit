<?php
// app/Controllers/HomeController.php — контроллер главной страницы.

declare(strict_types=1);

namespace App\Controllers;

use App\Models\EmployeeRepository;
use App\Models\EquipmentRepository;

final class HomeController
{
    // GET /
    public function index(): void
    {
        $items     = (new EquipmentRepository())->all();
        $employees = (new EmployeeRepository())->all();

        $byStatus = ['in_use' => 0, 'in_repair' => 0, 'archived' => 0];
        foreach ($items as $item) {
            if (isset($byStatus[$item['status']])) {
                $byStatus[$item['status']]++;
            }
        }

        view('home', [
            'title'          => 'Главная',
            'total'          => count($items),
            'byStatus'       => $byStatus,
            'employeesCount' => count($employees),
        ]);
    }
}