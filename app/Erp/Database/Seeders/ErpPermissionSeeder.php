<?php

namespace App\Erp\Database\Seeders;

use App\Erp\Services\Authorization\ErpPermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ErpPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = app(ErpPermissionCatalog::class);
        $guard   = $catalog->guardName();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($catalog->permissions() as $permission) {
            Permission::query()->firstOrCreate([
                'name'       => $permission,
                'guard_name' => $guard,
            ]);
        }

        foreach ($catalog->roles() as $roleKey => $roleConfig) {
            $role = Role::query()->firstOrCreate([
                'name'       => $roleConfig['name'],
                'guard_name' => $guard,
            ]);

            $role->syncPermissions($catalog->permissionsForRole($roleKey));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
