<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name'      => fake()->unique()->words(2, true).' Dept',
            'code'      => strtoupper(fake()->unique()->lexify('???')),
            'is_active' => true,
        ];
    }
}
