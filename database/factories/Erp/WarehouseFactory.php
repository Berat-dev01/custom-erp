<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        static $counter = 1;

        return [
            'name'       => 'Depo '.$counter,
            'code'       => 'WH-'.str_pad($counter++, 3, '0', STR_PAD_LEFT),
            'is_default' => false,
            'is_active'  => true,
        ];
    }
}
