<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'erp_attendance';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date'           => 'date',
            'work_hours'     => 'decimal:2',
            'overtime_hours' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
