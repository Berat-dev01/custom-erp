<?php

namespace App\Erp\Http\Requests;

use App\Erp\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $asset = $this->route('asset');

        return $asset instanceof Asset
            ? Gate::allows('update', $asset)
            : Gate::allows('erp.assets.update');
    }

    public function rules(): array
    {
        $asset = $this->route('asset');
        $id    = $asset instanceof Asset ? $asset->id : null;

        return [
            'name'          => ['required', 'string', 'max:200'],
            'asset_code'    => ['required', 'string', 'max:50', "unique:erp_assets,asset_code,{$id}"],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'category_id'   => ['required', 'exists:erp_asset_categories,id'],
            'assigned_to'   => ['nullable', 'exists:erp_employees,id'],
            'location_id'   => ['nullable', 'exists:erp_warehouses,id'],
            'purchase_date' => ['required', 'date'],
            'purchase_price'=> ['required', 'numeric', 'min:0'],
            'current_value' => ['required', 'numeric', 'min:0'],
            'status'        => ['required', 'in:active,in_repair,disposed'],
            'disposal_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ];
    }
}
