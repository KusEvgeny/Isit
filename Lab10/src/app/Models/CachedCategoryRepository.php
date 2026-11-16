<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Cache;

final class CachedCategoryRepository extends CategoryRepository
{
    public function all(): array
    {
        // TODO 3.1  Кешируем справочник категорий на 1 час (3600 сек)
        return Cache::remember('categories:all', 3600, function () {
            return parent::all();
        });
    }
}