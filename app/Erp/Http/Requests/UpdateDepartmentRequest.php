<?php

namespace App\Erp\Http\Requests;

use App\Erp\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dept = $this->route('department');

        return $dept instanceof Department
            ? Gate::allows('update', $dept)
            : Gate::allows('erp.departments.update');
    }

    public function rules(): array
    {
        $dept = $this->route('department');
        $id   = $dept instanceof Department ? $dept->id : null;

        return [
            'name'       => ['required', 'string', 'max:100'],
            'code'       => ['nullable', 'string', 'max:20', "unique:erp_departments,code,{$id}"],
            'parent_id'  => ['nullable', 'exists:erp_departments,id', "different:{$id}"],
            'manager_id' => ['nullable', 'exists:erp_employees,id'],
            'is_active'  => ['boolean'],
        ];
    }
}
