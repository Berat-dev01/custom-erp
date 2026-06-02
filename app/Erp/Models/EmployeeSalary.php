<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalary extends Model
{
    protected $table = 'erp_employee_salaries';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'basic_salary'   => 'decimal:2',
            'effective_from' => 'date',
            'effective_to'   => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
