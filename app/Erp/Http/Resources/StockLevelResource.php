<?php

namespace App\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockLevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'warehouse_id'        => $this->warehouse_id,
            'warehouse_name'      => $this->whenLoaded('warehouse', fn () => $this->warehouse->name),
            'quantity'            => (float) $this->quantity,
            'reserved_quantity'   => (float) $this->reserved_quantity,
            'available_quantity'  => max(0, (float) $this->quantity - (float) $this->reserved_quantity),
        ];
    }
}
