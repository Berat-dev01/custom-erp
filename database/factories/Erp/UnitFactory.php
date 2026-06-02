<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'name'         => fake()->unique()->word(),
            'abbreviation' => strtolower(fake()->unique()->lexify('??')),
        ];
    }
}
