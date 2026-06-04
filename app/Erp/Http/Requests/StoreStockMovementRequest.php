<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.stock_movements.create');
    }

    public function rules(): array
    {
        return [
            'product_id'   => ['required', 'exists:erp_products,id'],
            'warehouse_id' => ['required', 'exists:erp_warehouses,id'],
            'type'         => ['required', 'in:in,out,adjustment'],
            'quantity'     => ['required', 'numeric', 'not_in:0'],
            'unit_cost'    => ['nullable', 'numeric', 'min:0'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ];
    }
}
