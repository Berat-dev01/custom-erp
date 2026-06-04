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
            'name'       => fake()->city().' Depo',
            'code'       => strtoupper(fake()->unique()->lexify('WH-???')),
            'is_default' => false,
            'is_active'  => true,
        ];
    }
}
