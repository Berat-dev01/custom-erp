<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'erp_assets';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'purchase_date'  => 'date',
            'disposal_date'  => 'date',
            'purchase_price' => 'decimal:2',
            'current_value'  => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'location_id');
    }

    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(DepreciationEntry::class, 'asset_id');
    }

    public function totalDepreciated(): float
    {
        return (float) $this->depreciationEntries()->sum('amount');
    }

    public function monthlyDepreciationAmount(): float
    {
        if (! $this->category) {
            return 0;
        }

        $annualRate = (float) $this->category->depreciation_rate / 100;

        return round((float) $this->purchase_price * $annualRate / 12, 2);
    }
}
