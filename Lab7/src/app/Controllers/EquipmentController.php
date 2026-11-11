<?php
// app/Controllers/EquipmentController.php — контроллер оборудования.

declare(strict_types=1);

namespace App\Controllers;

use App\Models\EquipmentRepository;
use App\Models\EmployeeRepository;

final class EquipmentController
{
    // GET /equipment
    public function index(): void
    {
        $status = $_GET['status'] ?? '';

        $items = (new EquipmentRepository())->all();

        if ($status !== '') {
            $items = array_values(array_filter(
                $items,
                fn(array $item): bool => ($item['status'] ?? '') === $status
            ));
        }

        view('equipment/index', [
            'title'  => 'Оборудование',
            'items'  => $items,
            'status' => $status,
        ]);
    }

    // GET /equipment/create
    public function create(): void
    {
        $employees = (new EmployeeRepository())->all();

        view('equipment/create', [
            'title'     => 'Новое оборудование',
            'employees' => $employees,
        ]);
    }

    // POST /equipment
    public function store(): void
    {
        $title      = trim($_POST['title'] ?? '');
        $number     = trim($_POST['number'] ?? '');
        $category   = $_POST['category'] ?? 'computer';
        $status     = $_POST['status'] ?? 'in_use';
        $employeeId = (int) ($_POST['employee_id'] ?? 0);

        $errors = [];

        if (mb_strlen($title) < 3) {
            $errors['title'] = 'Название — не короче 3 символов';
        }

        if ($number === '') {
            $errors['number'] = 'Укажите инвентарный номер';
        }

        if ($errors) {
            http_response_code(422);

            $employees = (new EmployeeRepository())->all();

            view('equipment/create', [
                'title'     => 'Новое оборудование',
                'employees' => $employees,
                'errors'    => $errors,
                'old'       => $_POST,
            ]);
            return;
        }

        (new EquipmentRepository())->add([
            'employee_id' => $employeeId,
            'number'      => $number,
            'title'       => $title,
            'category'    => $category,
            'status'      => $status,
        ]);

        redirect('/equipment');
    }

    // GET /equipment/{id}
    public function show(string $id): void
    {
        $item = (new EquipmentRepository())->find((int) $id);

        if (!$item) {
            notFound();
        }

        $employee = null;
        if (!empty($item['employee_id'])) {
            $employee = (new EmployeeRepository())->find((int) $item['employee_id']);
        }

        view('equipment/show', [
            'title'    => $item['title'],
            'item'     => $item,
            'employee' => $employee,
        ]);
    }
}