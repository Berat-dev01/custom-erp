<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.suppliers.create');
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:200'],
            'code'              => ['nullable', 'string', 'max:20', 'unique:erp_suppliers,code'],
            'email'             => ['nullable', 'email', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:30'],
            'tax_number'        => ['nullable', 'string', 'max:30'],
            'address'           => ['nullable', 'string', 'max:500'],
            'contact_person'    => ['nullable', 'string', 'max:100'],
            'status'            => ['nullable', 'in:active,inactive'],
            'credit_limit'      => ['nullable', 'numeric', 'min:0'],
            'payment_terms_days'=> ['nullable', 'integer', 'min:0'],
        ];
    }
}
