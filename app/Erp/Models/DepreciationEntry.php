<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepreciationEntry extends Model
{
    protected $table = 'erp_depreciation_entries';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount'          => 'decimal:2',
            'book_value_after' => 'decimal:2',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
