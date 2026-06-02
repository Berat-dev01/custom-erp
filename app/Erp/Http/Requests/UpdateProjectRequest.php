<?php

namespace App\Erp\Http\Requests;

use App\Erp\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            ? Gate::allows('update', $project)
            : Gate::allows('erp.projects.update');
    }

    public function rules(): array
    {
        $project = $this->route('project');
        $id      = $project instanceof Project ? $project->id : null;

        return [
            'name'        => ['required', 'string', 'max:200'],
            'code'        => ['required', 'string', 'max:20', "unique:erp_projects,code,{$id}"],
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
