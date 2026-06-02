<?php

namespace App\Erp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('erp.assets.create');
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:200'],
            'asset_code'    => ['required', 'string', 'max:50', 'unique:erp_assets,asset_code'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'category_id'   => ['required', 'exists:erp_asset_categories,id'],
            'assigned_to'   => ['nullable', 'exists:erp_employees,id'],
            'location_id'   => ['nullable', 'exists:erp_warehouses,id'],
            'purchase_date' => ['required', 'date'],
            'purchase_price'=> ['required', 'numeric', 'min:0'],
            'current_value' => ['required', 'numeric', 'min:0'],
            'status'        => ['required', 'in:active,in_repair,disposed'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ];
    }
}
