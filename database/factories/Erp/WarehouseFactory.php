<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create('tr_TR');

        return [
            'name'       => $faker->city().' Depo',
            'code'       => strtoupper($faker->unique()->lexify('WH-???')),
            'is_default' => false,
            'is_active'  => true,
        ];
    }
}
