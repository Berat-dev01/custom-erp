<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.purchase_orders.create');
    }

    public function rules(): array
    {
        return [
            'supplier_id'      => ['required', 'exists:erp_suppliers,id'],
            'warehouse_id'     => ['required', 'exists:erp_warehouses,id'],
            'order_date'       => ['required', 'date'],
            'expected_date'    => ['nullable', 'date', 'after_or_equal:order_date'],
            'currency'         => ['nullable', 'string', 'size:3'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['required', 'exists:erp_products,id'],
            'items.*.quantity'     => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'   => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate'     => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_rate'=> ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
