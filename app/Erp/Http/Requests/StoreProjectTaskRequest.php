<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreProjectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.projects.create');
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:200'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'assignee_id'      => ['nullable', 'exists:erp_employees,id'],
            'status'           => ['required', 'in:todo,in_progress,review,done'],
            'priority'         => ['required', 'in:low,medium,high,urgent'],
            'due_date'         => ['nullable', 'date'],
            'estimated_hours'  => ['nullable', 'integer', 'min:0'],
        ];
    }
}
