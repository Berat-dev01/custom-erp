<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        static $counter = 1;

        return [
            'name'               => 'Müşteri A.Ş. #'.$counter++,
            'email'              => 'musteri'.($counter - 1).'@ornek.com',
            'phone'              => '0212 555 00 '.str_pad($counter - 1, 2, '0', STR_PAD_LEFT),
            'payment_terms_days' => 30,
            'credit_limit'       => 50000.00,
            'status'             => 'active',
        ];
    }
}
