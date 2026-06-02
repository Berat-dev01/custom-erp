<?php

namespace App\Erp\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpApiToken extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'abilities'    => 'array',
            'last_used_at' => 'datetime',
            'expires_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
