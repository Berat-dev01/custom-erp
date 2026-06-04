<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Product;
use App\Erp\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        static $counter = 1;

        return [
            'sku'            => 'SKU-'.str_pad($counter++, 5, '0', STR_PAD_LEFT),
            'name'           => fake()->unique()->words(3, true),
            'unit_id'        => Unit::factory(),
            'purchase_price' => fake()->randomFloat(2, 10, 500),
            'sale_price'     => fake()->randomFloat(2, 20, 1000),
            'tax_rate'       => 20.00,
            'type'           => 'product',
            'track_stock'    => true,
            'reorder_point'  => fake()->randomFloat(2, 5, 50),
            'is_active'      => true,
        ];
    }
}
