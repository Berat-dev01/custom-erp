<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.departments.create');
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100'],
            'code'       => ['nullable', 'string', 'max:20', 'unique:erp_departments,code'],
            'parent_id'  => ['nullable', 'exists:erp_departments,id'],
            'manager_id' => ['nullable', 'exists:erp_employees,id'],
            'is_active'  => ['boolean'],
        ];
    }
}
