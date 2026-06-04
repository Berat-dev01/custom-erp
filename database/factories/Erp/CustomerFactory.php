<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name'               => fake()->company(),
            'email'              => fake()->unique()->companyEmail(),
            'phone'              => fake()->phoneNumber(),
            'payment_terms_days' => 30,
            'credit_limit'       => fake()->randomFloat(2, 10000, 100000),
            'status'             => 'active',
        ];
    }
}
