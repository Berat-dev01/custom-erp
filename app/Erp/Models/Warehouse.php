<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'erp_warehouses';

    protected static function newFactory(): \Database\Factories\Erp\WarehouseFactory
    {
        return \Database\Factories\Erp\WarehouseFactory::new();
    }

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active'  => 'boolean',
        ];
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'warehouse_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'warehouse_id');
    }
}
