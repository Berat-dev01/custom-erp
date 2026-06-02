<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.products.create');
    }

    public function rules(): array
    {
        return [
            'sku'            => ['required', 'string', 'max:100', 'unique:erp_products,sku'],
            'name'           => ['required', 'string', 'max:255'],
            'barcode'        => ['nullable', 'string', 'max:100', 'unique:erp_products,barcode'],
            'category_id'    => ['nullable', 'exists:erp_product_categories,id'],
            'unit_id'        => ['required', 'exists:erp_units,id'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price'     => ['required', 'numeric', 'min:0'],
            'tax_rate'       => ['required', 'numeric', 'min:0', 'max:100'],
            'type'           => ['required', 'in:product,service,consumable'],
            'track_stock'    => ['boolean'],
            'reorder_point'  => ['nullable', 'numeric', 'min:0'],
            'is_active'      => ['boolean'],
        ];
    }
}
