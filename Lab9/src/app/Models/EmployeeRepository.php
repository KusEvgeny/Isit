<?php
declare(strict_types=1);
namespace App\Models;

final class EmployeeRepository extends PgRepository
{
    protected string $table = 'employees';
    protected string $columns = 'id, name, email, role, password_hash';

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo()->prepare("SELECT {$this->columns} FROM {$this->table} WHERE lower(email) = lower(:email)");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }
}