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
            'name'         => $this->faker->unique()->word(),
            'abbreviation' => strtolower($this->faker->unique()->lexify('??')),
        ];
    }
}
