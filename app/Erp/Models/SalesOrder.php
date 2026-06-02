<?php

namespace App\Erp\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'erp_sales_orders';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'order_date'               => 'date',
            'requested_delivery_date'  => 'date',
            'actual_delivery_date'     => 'date',
            'subtotal'                 => 'decimal:2',
            'discount_amount'          => 'decimal:2',
            'tax_amount'               => 'decimal:2',
            'total'                    => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeDelivered(): bool
    {
        return in_array($this->status, ['confirmed', 'picking', 'shipped']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'confirmed']);
    }
}
