<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $table = 'erp_leave_balances';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'entitled_days'     => 'decimal:1',
            'used_days'         => 'decimal:1',
            'carried_over_days' => 'decimal:1',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function remainingDays(): float
    {
        return max(0, (float) $this->entitled_days + (float) $this->carried_over_days - (float) $this->used_days);
    }
}
