<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create('tr_TR');

        return [
            'name'               => $faker->company().' Ltd.',
            'email'              => $faker->unique()->companyEmail(),
            'phone'              => $faker->phoneNumber(),
            'payment_terms_days' => 30,
            'status'             => 'active',
        ];
    }
}
