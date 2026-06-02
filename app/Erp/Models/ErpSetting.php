<?php

namespace App\Erp\Models;

use Illuminate\Database\Eloquent\Model;

class ErpSetting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'setup_completed' => 'boolean',
            'default_tax_rate' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'company_name'       => '',
            'currency'           => 'TRY',
            'currency_symbol'    => '₺',
            'default_tax_rate'   => 20.00,
            'invoice_prefix'     => 'INV',
            'invoice_next_number'=> 1,
            'setup_completed'    => false,
        ]);
    }
}
