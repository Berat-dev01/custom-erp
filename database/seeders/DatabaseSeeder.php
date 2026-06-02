<?php

namespace Database\Seeders;

use App\Erp\Database\Seeders\ChartOfAccountsSeeder;
use App\Erp\Database\Seeders\CurrencySeeder;
use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Database\Seeders\PayrollParametersSeeder;
use App\Erp\Database\Seeders\PublicHolidaySeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ErpPermissionSeeder::class,
            ChartOfAccountsSeeder::class,
            PublicHolidaySeeder::class,
            PayrollParametersSeeder::class,
            CurrencySeeder::class,
            ErpSeeder::class,
        ]);
    }
}
