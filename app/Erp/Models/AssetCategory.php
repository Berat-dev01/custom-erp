<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    protected $table = 'erp_asset_categories';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'depreciation_rate' => 'decimal:2',
        ];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'category_id');
    }
}
