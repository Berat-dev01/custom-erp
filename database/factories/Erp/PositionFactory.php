<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Department;
use App\Erp\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'name'          => $this->faker->jobTitle(),
            'department_id' => Department::factory(),
            'level'         => $this->faker->randomElement(['intern', 'junior', 'mid', 'senior', 'lead', 'manager']),
            'is_active'     => true,
        ];
    }
}
