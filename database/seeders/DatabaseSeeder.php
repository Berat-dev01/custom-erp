<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ErpDemoSeeder içinde permissions + chart of accounts + public holidays
        // + payroll params + currencies + 50 çalışan + 500 ürün + 200 fatura zaten var.
        $this->call([
            ErpDemoSeeder::class,
            ErpFullDemoSeeder::class,
        ]);
    }
}
