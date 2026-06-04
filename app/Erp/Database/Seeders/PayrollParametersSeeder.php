<?php

namespace App\Erp\Database\Seeders;

use App\Erp\Models\PayrollParameter;
use Illuminate\Database\Seeder;

class PayrollParametersSeeder extends Seeder
{
    public function run(): void
    {
        // 2025 parametreleri (2026 için güncellenebilir)
        PayrollParameter::firstOrCreate(['year' => 2025], [
            'year'                           => 2025,
            'minimum_wage'                   => 22104.67,
            'sgk_worker_rate'                => 0.1400,
            'sgk_employer_rate'              => 0.1550,
            'unemployment_worker_rate'       => 0.0100,
            'unemployment_employer_rate'     => 0.0200,
            'stamp_tax_rate'                 => 0.00759,
            'income_tax_brackets'            => [
                ['limit' => 158_000,   'rate' => 0.15],
                ['limit' => 330_000,   'rate' => 0.20],
                ['limit' => 800_000,   'rate' => 0.27],
                ['limit' => 4_300_000, 'rate' => 0.35],
                ['limit' => PHP_INT_MAX, 'rate' => 0.40],
            ],
            'agi_single'                     => 1850.39,
            'agi_married_spouse_not_working' => 2312.99,
        ]);

        // 2026 tahmini parametreler (asgari ücret açıklandığında güncellenecek)
        PayrollParameter::firstOrCreate(['year' => 2026], [
            'year'                           => 2026,
            'minimum_wage'                   => 26005.50,
            'sgk_worker_rate'                => 0.1400,
            'sgk_employer_rate'              => 0.1550,
            'unemployment_worker_rate'       => 0.0100,
            'unemployment_employer_rate'     => 0.0200,
            'stamp_tax_rate'                 => 0.00759,
            'income_tax_brackets'            => [
                ['limit' => 185_000,   'rate' => 0.15],
                ['limit' => 390_000,   'rate' => 0.20],
                ['limit' => 950_000,   'rate' => 0.27],
                ['limit' => 5_000_000, 'rate' => 0.35],
                ['limit' => PHP_INT_MAX, 'rate' => 0.40],
            ],
            'agi_single'                     => 2167.13,
            'agi_married_spouse_not_working' => 2708.91,
        ]);
    }
}
