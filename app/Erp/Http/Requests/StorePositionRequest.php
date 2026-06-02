<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.positions.create');
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100'],
            'department_id' => ['required', 'exists:erp_departments,id'],
            'level'         => ['required', 'in:intern,junior,mid,senior,lead,manager,director,executive'],
            'is_active'     => ['boolean'],
        ];
    }
}
