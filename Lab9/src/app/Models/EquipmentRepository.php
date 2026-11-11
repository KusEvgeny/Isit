<?php
declare(strict_types=1);
namespace App\Models;

final class EquipmentRepository extends PgRepository
{
    protected string $table = 'equipment';
    protected string $columns = 'id, category_id, employee_id, inventory_number, title, status';
}