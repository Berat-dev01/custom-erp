<?php

namespace Database\Seeders;

use App\Erp\Database\Seeders\ErpPermissionSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ErpPermissionSeeder::class,
            ErpSeeder::class,
        ]);
    }
}
