<?php

namespace App\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'po_number'     => $this->po_number,
            'status'        => $this->status,
            'order_date'    => $this->order_date?->toDateString(),
            'expected_date' => $this->expected_date?->toDateString(),
            'received_date' => $this->received_date?->toDateString(),
            'subtotal'      => (float) $this->subtotal,
            'tax_amount'    => (float) $this->tax_amount,
            'total'         => (float) $this->total,
            'currency'      => $this->currency,
            'notes'         => $this->notes,
            'supplier'      => $this->whenLoaded('supplier', fn () => [
                'id'   => $this->supplier->id,
                'name' => $this->supplier->name,
            ]),
            'warehouse'     => $this->whenLoaded('warehouse', fn () => [
                'id'   => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ]),
            'items'         => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id'                => $item->id,
                'product_id'        => $item->product_id,
                'quantity'          => (float) $item->quantity,
                'received_quantity' => (float) $item->received_quantity,
                'unit_price'        => (float) $item->unit_price,
                'tax_rate'          => (float) $item->tax_rate,
                'line_total'        => (float) $item->line_total,
            ])),
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
