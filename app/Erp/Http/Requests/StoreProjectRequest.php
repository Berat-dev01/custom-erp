<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.projects.create');
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:200'],
            'code'        => ['required', 'string', 'max:20', 'unique:erp_projects,code'],
            'description' => ['nullable', 'string', 'max:2000'],
            'customer_id' => ['nullable', 'exists:erp_customers,id'],
            'manager_id'  => ['nullable', 'exists:erp_employees,id'],
            'status'      => ['required', 'in:planning,active,on_hold,completed,cancelled'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget'      => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
