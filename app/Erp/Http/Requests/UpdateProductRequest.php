<?php

namespace App\Erp\Http\Requests;

use App\Erp\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            ? Gate::allows('update', $product)
            : Gate::allows('erp.products.update');
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $id      = $product instanceof Product ? $product->id : null;

        return [
            'sku'            => ['required', 'string', 'max:100', "unique:erp_products,sku,{$id}"],
            'name'           => ['required', 'string', 'max:255'],
            'barcode'        => ['nullable', 'string', 'max:100', "unique:erp_products,barcode,{$id}"],
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
