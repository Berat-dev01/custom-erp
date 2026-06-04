<?php

namespace App\Erp\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Check extends Model
{
    protected $table = 'erp_checks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'issue_date' => 'date',
            'due_date'   => 'date',
        ];
    }

    public function party(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return ! in_array($this->status, ['cashed', 'cancelled'])
            && $this->due_date->isPast();
    }

    public function daysUntilDue(): int
    {
        return (int) now()->diffInDays($this->due_date, false);
    }
}
