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
        static $counter = 1;

        return [
            'name'          => 'Pozisyon '.$counter++,
            'department_id' => Department::factory(),
            'level'         => 'mid',
            'is_active'     => true,
        ];
    }
}
