<?php

namespace App\Erp\Http\Requests;

use App\Erp\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $position = $this->route('position');

        return $position instanceof Position
            ? Gate::allows('update', $position)
            : Gate::allows('erp.positions.update');
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
