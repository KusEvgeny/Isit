<?php
declare(strict_types=1);
namespace App\Models;

final class CategoryRepository extends PgRepository
{
    protected string $table = 'categories';
    protected string $columns = 'id, title';
}