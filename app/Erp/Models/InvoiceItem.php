<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $table = 'erp_invoice_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quantity'      => 'decimal:3',
            'unit_price'    => 'decimal:2',
            'tax_rate'      => 'decimal:2',
            'discount_rate' => 'decimal:2',
            'line_total'    => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
