<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.sales_orders.create');
    }

    public function rules(): array
    {
        return [
            'customer_id'              => ['required', 'exists:erp_customers,id'],
            'warehouse_id'             => ['required', 'exists:erp_warehouses,id'],
            'order_date'               => ['required', 'date'],
            'requested_delivery_date'  => ['nullable', 'date', 'after_or_equal:order_date'],
            'discount_amount'          => ['nullable', 'numeric', 'min:0'],
            'notes'                    => ['nullable', 'string', 'max:1000'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.product_id'       => ['required', 'exists:erp_products,id'],
            'items.*.quantity'         => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'       => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_rate'    => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
