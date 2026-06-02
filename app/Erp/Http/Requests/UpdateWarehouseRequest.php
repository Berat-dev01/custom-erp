<?php

namespace App\Erp\Http\Requests;

use App\Erp\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wh = $this->route('warehouse');

        return $wh instanceof Warehouse
            ? Gate::allows('update', $wh)
            : Gate::allows('erp.warehouses.update');
    }

    public function rules(): array
    {
        $wh = $this->route('warehouse');
        $id = $wh instanceof Warehouse ? $wh->id : null;

        return [
            'name'       => ['required', 'string', 'max:100'],
            'code'       => ['required', 'string', 'max:20', "unique:erp_warehouses,code,{$id}"],
            'address'    => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
            'is_active'  => ['boolean'],
        ];
    }
}
