<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use App\Erp\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        static $counter = 1;

        return [
            'employee_number' => 'EMP-'.str_pad($counter++, 5, '0', STR_PAD_LEFT),
            'first_name'      => 'Ahmet',
            'last_name'       => 'Yılmaz',
            'email'           => 'personel'.($counter - 1).'@erp.test',
            'hire_date'       => '2023-01-15',
            'employment_type' => 'full_time',
            'status'          => 'active',
            'department_id'   => Department::factory(),
            'position_id'     => Position::factory(),
        ];
    }

    public function terminated(): static
    {
        return $this->state(fn () => [
            'status'           => 'terminated',
            'termination_date' => '2024-06-30',
        ]);
    }
}
