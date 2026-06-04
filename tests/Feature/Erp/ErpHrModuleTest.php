<?php

namespace Tests\Feature\Erp;

use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use App\Erp\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpHrModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ErpPermissionSeeder::class);

        $this->admin  = User::factory()->create(['is_active' => true]);
        $this->viewer = User::factory()->create(['is_active' => true]);

        $this->admin->assignRole('erp_admin');
        $this->viewer->assignRole('erp_viewer');
    }

    public function test_employee_list_is_paginated(): void
    {
        Employee::factory()->count(5)->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('erp.employees.index'))
            ->assertOk()
            ->assertViewHas('employees');
    }

    public function test_viewer_can_see_employee_list(): void
    {
        $this->actingAs($this->viewer, 'admin')
            ->get(route('erp.employees.index'))
            ->assertOk();
    }

    public function test_admin_can_create_employee(): void
    {
        $dept = Department::factory()->create();
        $pos  = Position::factory()->create(['department_id' => $dept->id]);

        $payload = [
            'first_name'      => 'Ali',
            'last_name'       => 'Yılmaz',
            'email'           => 'ali@test.com',
            'hire_date'       => now()->format('Y-m-d'),
            'employment_type' => 'full_time',
            'department_id'   => $dept->id,
            'position_id'     => $pos->id,
        ];

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.employees.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('erp_employees', ['email' => 'ali@test.com']);
    }

    public function test_employee_number_auto_generated(): void
    {
        $dept = Department::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.employees.store'), [
                'first_name'      => 'Test',
                'last_name'       => 'User',
                'email'           => 'test@example.com',
                'hire_date'       => now()->format('Y-m-d'),
                'employment_type' => 'full_time',
                'department_id'   => $dept->id,
            ]);

        $employee = Employee::where('email', 'test@example.com')->first();
        $this->assertNotNull($employee);
        $this->assertStringStartsWith('EMP-', $employee->employee_number);
    }

    public function test_viewer_cannot_create_employee(): void
    {
        $this->actingAs($this->viewer, 'admin')
            ->post(route('erp.employees.store'), [
                'first_name' => 'Test',
                'last_name'  => 'User',
                'email'      => 'test@example.com',
            ])
            ->assertForbidden();
    }

    public function test_validation_requires_email(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.employees.store'), [
                'first_name'      => 'Ali',
                'last_name'       => 'Yılmaz',
                'hire_date'       => now()->format('Y-m-d'),
                'employment_type' => 'full_time',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_soft_delete_employee(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->delete(route('erp.employees.destroy', $employee))
            ->assertRedirect();

        $this->assertSoftDeleted('erp_employees', ['id' => $employee->id]);
    }

    public function test_department_can_be_created(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.departments.store'), [
                'name'      => 'Yazılım',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('erp_departments', ['name' => 'Yazılım']);
    }
}
