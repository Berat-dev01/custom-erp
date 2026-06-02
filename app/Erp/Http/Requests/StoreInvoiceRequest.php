<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.invoices.create');
    }

    public function rules(): array
    {
        return [
            'type'                  => ['required', 'in:sale,purchase,credit_note'],
            'invoiceable_type'      => ['nullable', 'string'],
            'invoiceable_id'        => ['nullable', 'integer'],
            'issue_date'            => ['required', 'date'],
            'due_date'              => ['required', 'date', 'after_or_equal:issue_date'],
            'currency'              => ['nullable', 'string', 'size:3'],
            'discount_amount'       => ['nullable', 'numeric', 'min:0'],
            'reference'             => ['nullable', 'string', 'max:100'],
            'notes'                 => ['nullable', 'string', 'max:1000'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.description'   => ['required', 'string', 'max:255'],
            'items.*.product_id'    => ['nullable', 'exists:erp_products,id'],
            'items.*.quantity'      => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'    => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
