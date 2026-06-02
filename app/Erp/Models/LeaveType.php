<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $table = 'erp_leave_types';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'requires_approval'  => 'boolean',
            'is_paid'            => 'boolean',
            'carry_over'         => 'boolean',
            'is_active'          => 'boolean',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'leave_type_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'leave_type_id');
    }
}
