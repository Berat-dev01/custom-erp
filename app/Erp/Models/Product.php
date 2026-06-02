<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): \Database\Factories\Erp\ProductFactory
    {
        return \Database\Factories\Erp\ProductFactory::new();
    }

    protected $table = 'erp_products';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price'     => 'decimal:2',
            'tax_rate'       => 'decimal:2',
            'reorder_point'  => 'decimal:3',
            'track_stock'    => 'boolean',
            'is_active'      => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'product_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    public function availableQuantity(int $warehouseId): float
    {
        $level = $this->stockLevels()->where('warehouse_id', $warehouseId)->first();

        if (! $level) {
            return 0;
        }

        return max(0, (float) $level->quantity - (float) $level->reserved_quantity);
    }

    public function totalStock(): float
    {
        return (float) $this->stockLevels()->sum('quantity');
    }
}
