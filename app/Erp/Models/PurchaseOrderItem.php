<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $table = 'erp_purchase_order_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quantity'          => 'decimal:3',
            'received_quantity' => 'decimal:3',
            'unit_price'        => 'decimal:2',
            'tax_rate'          => 'decimal:2',
            'discount_rate'     => 'decimal:2',
            'line_total'        => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function pendingQuantity(): float
    {
        return max(0, (float) $this->quantity - (float) $this->received_quantity);
    }
}
