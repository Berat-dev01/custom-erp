<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'erp_customers';

    protected static function newFactory(): \Database\Factories\Erp\CustomerFactory
    {
        return \Database\Factories\Erp\CustomerFactory::new();
    }

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
        ];
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'invoiceable_id')
            ->where('invoiceable_type', 'erp_customer');
    }
}
