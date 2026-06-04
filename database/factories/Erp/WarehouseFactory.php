<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'name'       => $this->faker->city().' Depo',
            'code'       => strtoupper($this->faker->unique()->lexify('WH-???')),
            'is_default' => false,
            'is_active'  => true,
        ];
    }
}
