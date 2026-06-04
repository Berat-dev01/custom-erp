<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Erp\Models\LeaveRequest;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): \Database\Factories\Erp\EmployeeFactory
    {
        return \Database\Factories\Erp\EmployeeFactory::new();
    }

    protected $table = 'erp_employees';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'birth_date'       => 'date',
            'hire_date'        => 'date',
            'termination_date' => 'date',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id');
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class, 'employee_id');
    }

    public function currentSalary(): ?EmployeeSalary
    {
        return $this->salaries()
            ->where('effective_from', '<=', today())
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', today()))
            ->orderByDesc('effective_from')
            ->first();
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'employee_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id');
    }
}
