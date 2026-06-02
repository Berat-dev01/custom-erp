<?php

namespace App\Erp\Database\Seeders;

use App\Erp\Models\Currency;
use App\Erp\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dolar',   'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro',         'symbol' => '€'],
            ['code' => 'GBP', 'name' => 'İngiliz Sterlini', 'symbol' => '£'],
            ['code' => 'CHF', 'name' => 'İsviçre Frangı', 'symbol' => '₣'],
        ];

        foreach ($currencies as $c) {
            Currency::firstOrCreate(['code' => $c['code']], array_merge($c, ['is_active' => true]));
        }

        // Demo kurları
        $rates = [
            ['from' => 'USD', 'rate' => 38.50],
            ['from' => 'EUR', 'rate' => 41.20],
            ['from' => 'GBP', 'rate' => 48.80],
            ['from' => 'CHF', 'rate' => 43.10],
        ];

        $today = today()->toDateString();

        foreach ($rates as $r) {
            ExchangeRate::firstOrCreate(
                ['from_currency' => $r['from'], 'to_currency' => 'TRY', 'rate_date' => $today],
                ['rate' => $r['rate'], 'source' => 'manual']
            );
        }
    }
}
