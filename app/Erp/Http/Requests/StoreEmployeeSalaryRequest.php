<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreEmployeeSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.payroll.create');
    }

    public function rules(): array
    {
        return [
            'basic_salary'   => ['required', 'numeric', 'min:0'],
            'currency'       => ['nullable', 'string', 'size:3'],
            'effective_from' => ['required', 'date'],
        ];
    }
}
