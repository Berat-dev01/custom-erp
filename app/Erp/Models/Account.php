<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $table = 'erp_accounts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active'          => 'boolean',
            'allow_manual_entry' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }

    public function isDebitNormal(): bool
    {
        return $this->normal_balance === 'debit';
    }

    public function balance(?string $from = null, ?string $to = null): float
    {
        $query = $this->journalLines()
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'));

        if ($from) {
            $query->whereHas('journalEntry', fn ($q) => $q->where('entry_date', '>=', $from));
        }
        if ($to) {
            $query->whereHas('journalEntry', fn ($q) => $q->where('entry_date', '<=', $to));
        }

        $debit  = (float) $query->sum('debit');
        $credit = (float) $query->sum('credit');

        return $this->isDebitNormal() ? ($debit - $credit) : ($credit - $debit);
    }
}
