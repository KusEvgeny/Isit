<?php
declare(strict_types=1);
namespace App\Models;

class EquipmentRepository extends PgRepository // <-- убрали final
{
    protected string $table = 'equipment';
    protected string $columns = 'id, category_id, employee_id, inventory_number, title, status';
}