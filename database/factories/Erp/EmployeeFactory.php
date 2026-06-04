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
        $faker = \Faker\Factory::create('tr_TR');

        return [
            'employee_number' => 'EMP-'.str_pad($counter++, 5, '0', STR_PAD_LEFT),
            'first_name'      => $faker->firstName(),
            'last_name'       => $faker->lastName(),
            'email'           => $faker->unique()->safeEmail(),
            'hire_date'       => $faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'employment_type' => 'full_time',
            'status'          => 'active',
            'department_id'   => Department::factory(),
            'position_id'     => Position::factory(),
        ];
    }

    public function terminated(): static
    {
        return $this->state(function () {
            $faker = \Faker\Factory::create('tr_TR');

            return [
                'status'           => 'terminated',
                'termination_date' => now()->subDays($faker->numberBetween(1, 30))->format('Y-m-d'),
            ];
        });
    }
}
