<?php

namespace App\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'invoice_number'  => $this->invoice_number,
            'type'            => $this->type,
            'status'          => $this->status,
            'issue_date'      => $this->issue_date?->toDateString(),
            'due_date'        => $this->due_date?->toDateString(),
            'subtotal'        => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount'      => (float) $this->tax_amount,
            'total'           => (float) $this->total,
            'paid_amount'     => (float) $this->paid_amount,
            'remaining_amount'=> $this->remainingAmount(),
            'currency'        => $this->currency,
            'reference'       => $this->reference,
            'notes'           => $this->notes,
            'items'           => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id'            => $item->id,
                'description'   => $item->description,
                'quantity'      => (float) $item->quantity,
                'unit_price'    => (float) $item->unit_price,
                'tax_rate'      => (float) $item->tax_rate,
                'discount_rate' => (float) $item->discount_rate,
                'line_total'    => (float) $item->line_total,
            ])),
            'payments'        => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($p) => [
                'id'           => $p->id,
                'amount'       => (float) $p->amount,
                'payment_date' => $p->payment_date?->toDateString(),
                'method'       => $p->method,
            ])),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
