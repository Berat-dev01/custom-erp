<?php

namespace App\Erp\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $table = 'erp_payroll_runs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'pay_date'         => 'date',
            'total_gross'      => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net'        => 'decimal:2',
        ];
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'payroll_run_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function periodLabel(): string
    {
        $months = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
        ];

        return ($months[$this->month] ?? $this->month) . ' ' . $this->year;
    }
}
