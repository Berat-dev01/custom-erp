<?php

namespace Database\Factories\Erp;

use App\Erp\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        static $counter = 1;

        return [
            'name'      => 'Departman '.$counter,
            'code'      => 'DEP'.str_pad($counter++, 3, '0', STR_PAD_LEFT),
            'is_active' => true,
        ];
    }
}
