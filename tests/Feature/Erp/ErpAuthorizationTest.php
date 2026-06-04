<?php

namespace Tests\Feature\Erp;

use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;
    private User $hrUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ErpPermissionSeeder::class);

        $this->admin  = User::factory()->create(['is_active' => true]);
        $this->viewer = User::factory()->create(['is_active' => true]);
        $this->hrUser = User::factory()->create(['is_active' => true]);

        $this->admin->assignRole('erp_admin');
        $this->viewer->assignRole('erp_viewer');
        $this->hrUser->assignRole('erp_hr');
    }

    public function test_unauthenticated_redirected_from_erp(): void
    {
        $this->get(route('erp.dashboard'))->assertRedirect();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('erp.dashboard'))
            ->assertOk();
    }

    public function test_viewer_can_access_dashboard(): void
    {
        $this->actingAs($this->viewer, 'admin')
            ->get(route('erp.dashboard'))
            ->assertOk();
    }

    public function test_admin_can_create_employee(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('erp.employees.create'))
            ->assertOk();
    }

    public function test_viewer_cannot_create_employee(): void
    {
        $this->actingAs($this->viewer, 'admin')
            ->get(route('erp.employees.create'))
            ->assertForbidden();
    }

    public function test_hr_can_create_employee(): void
    {
        $this->actingAs($this->hrUser, 'admin')
            ->get(route('erp.employees.create'))
            ->assertOk();
    }

    public function test_viewer_cannot_create_department(): void
    {
        $this->actingAs($this->viewer, 'admin')
            ->get(route('erp.departments.create'))
            ->assertForbidden();
    }

    public function test_viewer_cannot_access_products_create(): void
    {
        $this->actingAs($this->viewer, 'admin')
            ->get(route('erp.products.create'))
            ->assertForbidden();
    }

    public function test_viewer_cannot_delete_employee(): void
    {
        $dept = Department::factory()->create();
        $employee = Employee::factory()->create(['department_id' => $dept->id, 'position_id' => null]);

        $this->actingAs($this->viewer, 'admin')
            ->delete(route('erp.employees.destroy', $employee))
            ->assertForbidden();
    }

    public function test_inactive_user_cannot_access_erp(): void
    {
        $inactiveUser = User::factory()->create(['is_active' => false]);
        $inactiveUser->assignRole('erp_admin');

        $this->actingAs($inactiveUser, 'admin')
            ->get(route('erp.dashboard'))
            ->assertRedirect();
    }

    public function test_finance_role_cannot_access_payroll(): void
    {
        $finance = User::factory()->create(['is_active' => true]);
        $finance->assignRole('erp_finance');

        $this->actingAs($finance, 'admin')
            ->get(route('erp.payroll-runs.index'))
            ->assertForbidden();
    }
}
