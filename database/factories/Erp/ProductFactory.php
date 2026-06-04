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
            'name'           => 'Ürün '.$counter,
            'unit_id'        => Unit::factory(),
            'purchase_price' => 100.00,
            'sale_price'     => 150.00,
            'tax_rate'       => 20.00,
            'type'           => 'product',
            'track_stock'    => true,
            'reorder_point'  => 10.00,
            'is_active'      => true,
        ];
    }
}
