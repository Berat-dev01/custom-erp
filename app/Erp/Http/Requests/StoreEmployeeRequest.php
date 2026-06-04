<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.employees.create');
    }

    public function rules(): array
    {
        return [
            'first_name'      => ['required', 'string', 'max:100'],
            'last_name'       => ['required', 'string', 'max:100'],
            'email'           => ['required', 'email', 'max:255', 'unique:erp_employees,email'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'national_id'     => ['nullable', 'string', 'size:11'],
            'birth_date'      => ['nullable', 'date'],
            'gender'          => ['nullable', 'in:male,female,other'],
            'department_id'   => ['nullable', 'exists:erp_departments,id'],
            'position_id'     => ['nullable', 'exists:erp_positions,id'],
            'manager_id'      => ['nullable', 'exists:erp_employees,id'],
            'hire_date'       => ['required', 'date'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,intern'],
            'status'          => ['nullable', 'in:active,on_leave,terminated'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ];
    }
}
