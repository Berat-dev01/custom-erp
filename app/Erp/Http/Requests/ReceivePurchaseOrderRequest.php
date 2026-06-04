<?php

namespace App\Erp\Http\Requests;

use App\Erp\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $po = $this->route('purchase_order');

        return $po instanceof PurchaseOrder
            ? Gate::allows('receive', $po)
            : Gate::allows('erp.purchase_orders.receive');
    }

    public function rules(): array
    {
        return [
            'items'            => ['required', 'array'],
            'items.*'          => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
