<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Cache;

final class CachedEquipmentRepository extends EquipmentRepository
{
    private const TTL_ITEM = 60;
    private const TTL_LIST = 60;

    public function find(int $id): ?array
    {
        return Cache::remember("equipment:item:$id", self::TTL_ITEM, fn(): ?array => parent::find($id));
    }

    public function all(): array
    {
        $key = 'equipment:list:v' . Cache::version('equipment:version');
        return Cache::remember($key, self::TTL_LIST, function () {
            // Берем только 100 записей, чтобы не упасть по памяти
            $stmt = $this->pdo()->query("SELECT {$this->columns} FROM {$this->table} ORDER BY id LIMIT 100");
            return $stmt->fetchAll();
        });
    }

    public function add(array $data): array
    {
        $item = parent::add($data);
        $this->invalidate();
        return $item;
    }

    public function update(int $id, array $data): ?array
    {
        $item = parent::update($id, $data);
        $this->invalidate($id);
        return $item;
    }

    public function delete(int $id): bool
    {
        $deleted = parent::delete($id);
        $this->invalidate($id);
        return $deleted;
    }

    private function invalidate(?int $id = null): void
    {
        Cache::bump('equipment:version');
        Cache::forget('report:summary');
        if ($id !== null) {
            Cache::forget("equipment:item:$id");
        }
    }
}