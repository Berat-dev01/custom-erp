<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        static $counter = 1;

        return [
            'name'               => 'Tedarikçi Ltd. #'.$counter++,
            'email'              => 'tedarik'.($counter - 1).'@ornek.com',
            'phone'              => '0212 444 00 '.str_pad($counter - 1, 2, '0', STR_PAD_LEFT),
            'payment_terms_days' => 30,
            'status'             => 'active',
        ];
    }
}
