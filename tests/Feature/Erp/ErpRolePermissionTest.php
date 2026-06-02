<?php

namespace Tests\Feature\Erp;

use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Services\Authorization\ErpPermissionCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ErpRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;
    private ErpPermissionCatalog $catalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ErpPermissionSeeder::class);

        $this->admin  = User::factory()->create(['is_active' => true]);
        $this->viewer = User::factory()->create(['is_active' => true]);

        $this->admin->assignRole('erp_admin');
        $this->viewer->assignRole('erp_viewer');

        $this->catalog = app(ErpPermissionCatalog::class);
    }

    public function test_erp_admin_has_all_permissions(): void
    {
        $adminRole = Role::findByName('erp_admin', $this->catalog->guardName());
        $this->assertGreaterThan(0, $adminRole->permissions->count());
    }

    public function test_erp_viewer_has_only_view_permissions(): void
    {
        $viewerRole = Role::findByName('erp_viewer', $this->catalog->guardName());

        foreach ($viewerRole->permissions as $permission) {
            $this->assertStringContainsString('.view', $permission->name);
        }
    }

    public function test_admin_can_access_roles_management(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('erp.roles.index'))
            ->assertOk();
    }

    public function test_viewer_cannot_access_roles_management(): void
    {
        $this->actingAs($this->viewer, 'admin')
            ->get(route('erp.roles.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_custom_role(): void
    {
        $permissionName = 'erp.employees.view';
        $permission     = Permission::findByName($permissionName, $this->catalog->guardName());

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.roles.store'), [
                'name'        => 'erp_custom_role',
                'permissions' => [$permissionName],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('roles', ['name' => 'erp_custom_role']);
    }

    public function test_user_assigned_to_role_gains_permissions(): void
    {
        $newUser = User::factory()->create(['is_active' => true]);
        $newUser->assignRole('erp_hr');

        $this->assertTrue($newUser->hasRole('erp_hr'));
        $this->assertTrue($newUser->hasPermissionTo('erp.employees.view', $this->catalog->guardName()));
    }

    public function test_user_without_role_denied_access(): void
    {
        $noRole = User::factory()->create(['is_active' => true]);

        $this->actingAs($noRole, 'admin')
            ->get(route('erp.employees.index'))
            ->assertForbidden();
    }

    public function test_admin_can_assign_role_to_user(): void
    {
        $targetUser = User::factory()->create(['is_active' => true]);
        $guard      = $this->catalog->guardName();

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.roles.assign', $targetUser), ['role' => 'erp_viewer'])
            ->assertRedirect();

        $this->assertTrue($targetUser->fresh()->hasRole('erp_viewer', $guard));
    }

    public function test_admin_can_remove_role_from_user(): void
    {
        $targetUser = User::factory()->create(['is_active' => true]);
        $guard      = $this->catalog->guardName();
        $targetUser->assignRole(Role::findByName('erp_viewer', $guard));

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.roles.remove', $targetUser), ['role' => 'erp_viewer'])
            ->assertRedirect();

        $this->assertFalse($targetUser->fresh()->hasRole('erp_viewer', $guard));
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->delete(route('erp.roles.destroy', Role::findByName('erp_admin', $this->catalog->guardName())))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['name' => 'erp_admin']);
    }

    public function test_permission_catalog_lists_all_permissions(): void
    {
        $permissions = $this->catalog->permissions();
        $this->assertNotEmpty($permissions);

        foreach ($permissions as $permission) {
            $this->assertStringStartsWith('erp.', $permission);
        }
    }
}
