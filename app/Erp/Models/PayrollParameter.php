<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollParameter extends Model
{
    protected $table = 'erp_payroll_parameters';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'minimum_wage'                   => 'decimal:2',
            'sgk_worker_rate'                => 'decimal:4',
            'sgk_employer_rate'              => 'decimal:4',
            'unemployment_worker_rate'       => 'decimal:4',
            'unemployment_employer_rate'     => 'decimal:4',
            'stamp_tax_rate'                 => 'decimal:5',
            'income_tax_brackets'            => 'array',
            'agi_single'                     => 'decimal:2',
            'agi_married_spouse_not_working' => 'decimal:2',
        ];
    }

    public static function forYear(int $year): ?self
    {
        return self::where('year', $year)->first()
            ?? self::orderByDesc('year')->first();
    }
}
