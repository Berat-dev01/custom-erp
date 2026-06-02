<?php

namespace App\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'so_number'                => $this->so_number,
            'status'                   => $this->status,
            'order_date'               => $this->order_date?->toDateString(),
            'requested_delivery_date'  => $this->requested_delivery_date?->toDateString(),
            'actual_delivery_date'     => $this->actual_delivery_date?->toDateString(),
            'subtotal'                 => (float) $this->subtotal,
            'discount_amount'          => (float) $this->discount_amount,
            'tax_amount'               => (float) $this->tax_amount,
            'total'                    => (float) $this->total,
            'notes'                    => $this->notes,
            'customer'                 => $this->whenLoaded('customer', fn () => [
                'id'   => $this->customer->id,
                'name' => $this->customer->name,
            ]),
            'warehouse'                => $this->whenLoaded('warehouse', fn () => [
                'id'   => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ]),
            'items'                    => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id'            => $item->id,
                'product_id'    => $item->product_id,
                'quantity'      => (float) $item->quantity,
                'unit_price'    => (float) $item->unit_price,
                'tax_rate'      => (float) $item->tax_rate,
                'discount_rate' => (float) $item->discount_rate,
                'line_total'    => (float) $item->line_total,
            ])),
            'created_at'               => $this->created_at?->toIso8601String(),
        ];
    }
}
