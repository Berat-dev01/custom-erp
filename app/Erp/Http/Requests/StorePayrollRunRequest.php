<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StorePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.payroll.create');
    }

    public function rules(): array
    {
        return [
            'year'  => ['required', 'integer', 'min:2020', 'max:2099'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
