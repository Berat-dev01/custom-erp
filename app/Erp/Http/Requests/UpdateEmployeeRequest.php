<?php

namespace App\Erp\Http\Requests;

use App\Erp\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee
            ? Gate::allows('update', $employee)
            : Gate::allows('erp.employees.update');
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $id = $employee instanceof Employee ? $employee->id : null;

        return [
            'first_name'      => ['required', 'string', 'max:100'],
            'last_name'       => ['required', 'string', 'max:100'],
            'email'           => ['required', 'email', 'max:255', "unique:erp_employees,email,{$id}"],
            'phone'           => ['nullable', 'string', 'max:20'],
            'national_id'     => ['nullable', 'string', 'size:11'],
            'birth_date'      => ['nullable', 'date'],
            'gender'          => ['nullable', 'in:male,female,other'],
            'department_id'   => ['nullable', 'exists:erp_departments,id'],
            'position_id'     => ['nullable', 'exists:erp_positions,id'],
            'manager_id'      => ['nullable', 'exists:erp_employees,id'],
            'hire_date'       => ['required', 'date'],
            'termination_date'=> ['nullable', 'date', 'after_or_equal:hire_date'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,intern'],
            'status'          => ['required', 'in:active,on_leave,terminated'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ];
    }
}
