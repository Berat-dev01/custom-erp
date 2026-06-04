<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name'               => $this->faker->company().' Ltd.',
            'email'              => $this->faker->unique()->companyEmail(),
            'phone'              => $this->faker->phoneNumber(),
            'payment_terms_days' => 30,
            'status'             => 'active',
        ];
    }
}
