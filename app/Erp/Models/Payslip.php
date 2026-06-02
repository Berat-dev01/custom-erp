<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    protected $table = 'erp_payslips';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'basic_salary'     => 'decimal:2',
            'gross_salary'     => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_salary'       => 'decimal:2',
            'breakdown'        => 'array',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
