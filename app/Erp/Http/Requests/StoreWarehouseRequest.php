<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.warehouses.create');
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100'],
            'code'       => ['required', 'string', 'max:20', 'unique:erp_warehouses,code'],
            'address'    => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
            'is_active'  => ['boolean'],
        ];
    }
}
