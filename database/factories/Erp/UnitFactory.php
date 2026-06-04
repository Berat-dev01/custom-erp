<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create('tr_TR');

        return [
            'name'         => $faker->unique()->word(),
            'abbreviation' => strtolower($faker->unique()->lexify('??')),
        ];
    }
}
