<?php

namespace App\Erp\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'erp_invoices';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'issue_date'      => 'date',
            'due_date'        => 'date',
            'subtotal'        => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount'      => 'decimal:2',
            'total'           => 'decimal:2',
            'paid_amount'     => 'decimal:2',
            'efatura_sent_at' => 'datetime',
        ];
    }

    public function invoiceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function remainingAmount(): float
    {
        return max(0, (float) $this->total - (float) $this->paid_amount);
    }

    public function isOverdue(): bool
    {
        return ! in_array($this->status, ['paid', 'cancelled'])
            && $this->due_date->isPast();
    }
}
