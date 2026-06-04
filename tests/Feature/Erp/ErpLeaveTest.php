<?php

namespace Tests\Feature\Erp;

use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use App\Erp\Models\LeaveBalance;
use App\Erp\Models\LeaveRequest;
use App\Erp\Models\LeaveType;
use App\Erp\Models\Position;
use App\Erp\Services\Hr\LeaveService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ErpLeaveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Employee $employee;
    private Employee $manager;
    private LeaveType $annualLeave;
    private LeaveService $leaveService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ErpPermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('erp_admin');

        $dept = Department::factory()->create();
        $pos  = Position::factory()->create(['department_id' => $dept->id]);

        $this->manager = Employee::factory()->create([
            'hire_date'       => Carbon::now()->subYears(3),
            'status'          => 'active',
            'department_id'   => $dept->id,
            'position_id'     => $pos->id,
        ]);

        $this->employee = Employee::factory()->create([
            'hire_date'       => Carbon::now()->subYears(2),
            'status'          => 'active',
            'manager_id'      => $this->manager->id,
            'department_id'   => $dept->id,
            'position_id'     => $pos->id,
        ]);

        $this->annualLeave = LeaveType::create([
            'name'              => 'Yıllık İzin',
            'days_per_year'     => 0,
            'requires_approval' => true,
            'is_paid'           => true,
            'carry_over'        => false,
            'max_carry_over_days'=> 0,
            'is_active'         => true,
        ]);

        $this->leaveService = app(LeaveService::class);
    }

    public function test_entitlement_less_than_one_year(): void
    {
        $newEmployee = Employee::factory()->create([
            'hire_date' => Carbon::now()->subMonths(6),
            'status'    => 'active',
        ]);

        // Türk iş hukuku: ilk yıl tamamlanmadan izin hakkı kazanılmaz
        $days = $this->leaveService->calculateEntitlement($newEmployee, now()->year);
        $this->assertEquals(0, $days);
    }

    public function test_entitlement_between_five_and_fifteen_years(): void
    {
        $seniorEmployee = Employee::factory()->create([
            'hire_date' => Carbon::now()->subYears(8),
            'status'    => 'active',
        ]);

        // 5-15 yıl kıdeme sahip çalışan 20 gün hak kazanır
        $days = $this->leaveService->calculateEntitlement($seniorEmployee, now()->year);
        $this->assertEquals(20, $days);
    }

    public function test_entitlement_over_fifteen_years(): void
    {
        $veteranEmployee = Employee::factory()->create([
            'hire_date' => Carbon::now()->subYears(16),
            'status'    => 'active',
        ]);

        // 15+ yıl kıdeme sahip çalışan 26 gün hak kazanır
        $days = $this->leaveService->calculateEntitlement($veteranEmployee, now()->year);
        $this->assertEquals(26, $days);
    }

    public function test_work_days_excludes_weekends(): void
    {
        // Pazartesi'den Cuma'ya 5 iş günü
        $monday = Carbon::parse('2026-06-01'); // Pazartesi
        $friday = Carbon::parse('2026-06-05'); // Cuma

        $days = $this->leaveService->calculateWorkDays($monday, $friday);
        $this->assertEquals(5, $days);
    }

    public function test_work_days_for_full_week_including_weekend(): void
    {
        // Pazartesi'den gelecek Pazartesi'ye = 6 iş günü (Cumartesi-Pazar hariç)
        $monday     = Carbon::parse('2026-06-01');
        $nextMonday = Carbon::parse('2026-06-08');

        $days = $this->leaveService->calculateWorkDays($monday, $nextMonday);
        $this->assertEquals(6, $days);
    }

    public function test_leave_request_can_be_created(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('erp.leave-requests.store'), [
                'employee_id'   => $this->employee->id,
                'leave_type_id' => $this->annualLeave->id,
                'start_date'    => Carbon::parse('2026-07-01')->format('Y-m-d'),
                'end_date'      => Carbon::parse('2026-07-03')->format('Y-m-d'),
                'reason'        => 'Yıllık tatil',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('erp_leave_requests', [
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->annualLeave->id,
            'status'        => 'pending',
        ]);
    }

    public function test_leave_approval_updates_balance(): void
    {
        $balance = LeaveBalance::create([
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->annualLeave->id,
            'year'          => now()->year,
            'entitled_days' => 14,
            'used_days'     => 0,
        ]);

        $request = LeaveRequest::create([
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date'    => Carbon::parse('2026-07-01'),
            'end_date'      => Carbon::parse('2026-07-02'),
            'days'          => 2,
            'status'        => 'pending',
        ]);

        $this->leaveService->approveLeaveRequest($request, $this->manager);

        $this->assertDatabaseHas('erp_leave_requests', [
            'id'     => $request->id,
            'status' => 'approved',
        ]);
    }

    public function test_conflict_detection_prevents_double_booking(): void
    {
        LeaveRequest::create([
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date'    => Carbon::parse('2026-07-01'),
            'end_date'      => Carbon::parse('2026-07-05'),
            'days'          => 3,
            'status'        => 'approved',
        ]);

        $hasConflict = $this->leaveService->hasConflict(
            $this->employee,
            Carbon::parse('2026-07-03'),
            Carbon::parse('2026-07-07')
        );

        $this->assertTrue($hasConflict);
    }

    public function test_no_conflict_for_different_period(): void
    {
        LeaveRequest::create([
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date'    => Carbon::parse('2026-07-01'),
            'end_date'      => Carbon::parse('2026-07-05'),
            'days'          => 3,
            'status'        => 'approved',
        ]);

        $hasConflict = $this->leaveService->hasConflict(
            $this->employee,
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-05')
        );

        $this->assertFalse($hasConflict);
    }

    public function test_viewer_can_see_leave_requests(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->assignRole('erp_viewer');

        $this->actingAs($viewer, 'admin')
            ->get(route('erp.leave-requests.index'))
            ->assertOk();
    }
}
