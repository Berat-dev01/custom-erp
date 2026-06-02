<?php

namespace App\Erp\Http\Requests;

use App\Erp\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        $supplier = $this->route('supplier');

        return $supplier instanceof Supplier
            ? Gate::allows('update', $supplier)
            : Gate::allows('erp.suppliers.update');
    }

    public function rules(): array
    {
        $supplier = $this->route('supplier');
        $id       = $supplier instanceof Supplier ? $supplier->id : null;

        return [
            'name'              => ['required', 'string', 'max:200'],
            'code'              => ['nullable', 'string', 'max:20', "unique:erp_suppliers,code,{$id}"],
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
