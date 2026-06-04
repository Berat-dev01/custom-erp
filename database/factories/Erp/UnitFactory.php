<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        static $counter = 1;

        return [
            'name'         => 'Birim '.$counter,
            'abbreviation' => 'br'.str_pad($counter++, 2, '0', STR_PAD_LEFT),
        ];
    }
}
