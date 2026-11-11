<?php
// app/Models/EmployeeRepository.php

declare(strict_types=1);

namespace App\Models;

final class EmployeeRepository extends JsonRepository
{
    protected string $name = 'employees';
}