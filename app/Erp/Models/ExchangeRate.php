<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $table = 'erp_exchange_rates';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'rate'      => 'decimal:6',
            'rate_date' => 'date',
        ];
    }
}
