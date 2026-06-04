<?php

namespace App\Erp\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'erp_purchase_orders';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'order_date'    => 'date',
            'expected_date' => 'date',
            'received_date' => 'date',
            'subtotal'      => 'decimal:2',
            'tax_amount'    => 'decimal:2',
            'total'         => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isFullyReceived(): bool
    {
        return $this->items->every(
            fn ($item) => (float) $item->received_quantity >= (float) $item->quantity
        );
    }
}
