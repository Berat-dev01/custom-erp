<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create('tr_TR');

        return [
            'name'               => $faker->company(),
            'email'              => $faker->unique()->companyEmail(),
            'phone'              => $faker->phoneNumber(),
            'payment_terms_days' => 30,
            'credit_limit'       => $faker->randomFloat(2, 10000, 100000),
            'status'             => 'active',
        ];
    }
}
