<?php

namespace Database\Seeders;

use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ErpPermissionSeeder::class);

        $admin = User::factory()->create([
            'name'      => 'ERP Admin',
            'email'     => 'admin@erp.test',
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);

        $admin->assignRole('erp_admin');
    }
}
