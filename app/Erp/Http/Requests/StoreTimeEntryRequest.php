<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.projects.create');
    }

    public function rules(): array
    {
        return [
            'task_id'     => ['nullable', 'exists:erp_project_tasks,id'],
            'employee_id' => ['required', 'exists:erp_employees,id'],
            'date'        => ['required', 'date'],
            'hours'       => ['required', 'numeric', 'min:0.25', 'max:24'],
            'description' => ['nullable', 'string', 'max:500'],
            'billable'    => ['boolean'],
        ];
    }
}
