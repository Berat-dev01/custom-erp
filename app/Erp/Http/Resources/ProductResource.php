<?php

namespace App\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'sku'            => $this->sku,
            'name'           => $this->name,
            'barcode'        => $this->barcode,
            'type'           => $this->type,
            'purchase_price' => (float) $this->purchase_price,
            'sale_price'     => (float) $this->sale_price,
            'tax_rate'       => (float) $this->tax_rate,
            'track_stock'    => (bool) $this->track_stock,
            'reorder_point'  => (float) $this->reorder_point,
            'is_active'      => (bool) $this->is_active,
            'category'       => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
            'unit'           => $this->whenLoaded('unit', fn () => [
                'id'           => $this->unit->id,
                'name'         => $this->unit->name,
                'abbreviation' => $this->unit->abbreviation,
            ]),
            'total_stock'    => $this->whenLoaded('stockLevels', fn () => $this->totalStock()),
        ];
    }
}
