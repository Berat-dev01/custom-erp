<?php

namespace Database\Seeders;

use App\Erp\Database\Seeders\ChartOfAccountsSeeder;
use App\Erp\Database\Seeders\ErpPermissionSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ErpPermissionSeeder::class,
            ChartOfAccountsSeeder::class,
            ErpSeeder::class,
        ]);
    }
}
