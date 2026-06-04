<?php

namespace Tests\Feature\Erp;

use App\Erp\Database\Seeders\ErpPermissionSeeder;
use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use App\Erp\Models\PayrollParameter;
use App\Erp\Models\Position;
use App\Erp\Services\Payroll\TurkishPayrollCalculator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpPayrollLegalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TurkishPayrollCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ErpPermissionSeeder::class);
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('erp_admin');
        $this->calculator = app(TurkishPayrollCalculator::class);

        // 2026 parametreleri
        PayrollParameter::create([
            'year'                        => 2026,
            'minimum_wage'                => 22104.97,
            'sgk_worker_rate'             => 0.14,
            'sgk_employer_rate'           => 0.155,
            'unemployment_worker_rate'    => 0.01,
            'unemployment_employer_rate'  => 0.02,
            'stamp_tax_rate'              => 0.00759,
            'income_tax_brackets'         => [
                ['limit' => 110000,  'rate' => 0.15],
                ['limit' => 230000,  'rate' => 0.20],
                ['limit' => 580000,  'rate' => 0.27],
                ['limit' => 3000000, 'rate' => 0.35],
                ['limit' => null,    'rate' => 0.40],
            ],
            'agi_single'                        => 500.00,
            'agi_married_spouse_not_working'     => 750.00,
        ]);
    }

    public function test_sgk_deduction_calculated_correctly(): void
    {
        $result = $this->calculator->calculate(12000, 2026, 1);

        $this->assertEquals(1680.00, $result['sgk_worker']);        // 12000 * 0.14
        $this->assertEquals(120.00,  $result['unemployment_worker']); // 12000 * 0.01
    }

    public function test_net_salary_less_than_gross(): void
    {
        $result = $this->calculator->calculate(12000, 2026, 1);

        $this->assertLessThan($result['gross'], $result['net']);
        $this->assertGreaterThan(0, $result['net']);
    }

    public function test_employer_cost_includes_sgk_employer(): void
    {
        $result = $this->calculator->calculate(12000, 2026, 1);

        $expectedSgkEmployer = round(12000 * 0.155, 2);
        $this->assertEquals($expectedSgkEmployer, $result['sgk_employer']);
        $this->assertGreaterThan(12000, $result['employer_cost']);
    }

    public function test_agi_applied_for_single(): void
    {
        $result = $this->calculator->calculate(12000, 2026, 1, 0, 'single');

        $this->assertEquals(500.00, $result['agi']);
    }

    public function test_agi_higher_for_married(): void
    {
        $single  = $this->calculator->calculate(12000, 2026, 1, 0, 'single');
        $married = $this->calculator->calculate(12000, 2026, 1, 0, 'married');

        $this->assertGreaterThan($single['agi'], $married['agi']);
    }

    public function test_stamp_tax_calculated(): void
    {
        $result = $this->calculator->calculate(12000, 2026, 1);

        $expectedStamp = round(12000 * 0.00759, 2);
        $this->assertEquals($expectedStamp, $result['stamp_tax']);
    }

    public function test_cumulative_tax_bracket_increases_higher_earner(): void
    {
        $low  = $this->calculator->calculate(10000, 2026, 6, 60000);
        $high = $this->calculator->calculate(50000, 2026, 6, 300000);

        // Yüksek kümülatif kazanç → daha yüksek marjinal vergi oranı
        $lowRate  = $low['income_tax']  / $low['income_tax_base'];
        $highRate = $high['income_tax'] / $high['income_tax_base'];

        $this->assertGreaterThan($lowRate, $highRate);
    }

    public function test_employer_cost_method_consistent(): void
    {
        $result      = $this->calculator->calculate(12000, 2026, 1);
        $directCost  = $this->calculator->employerCost(12000, 2026);

        $this->assertEquals($result['employer_cost'], $directCost);
    }

    public function test_fallback_calculation_when_no_parameter(): void
    {
        // 2099 parametresi yok — fallback devreye girmeli
        $result = $this->calculator->calculate(10000, 2099, 1);

        $this->assertArrayHasKey('net', $result);
        $this->assertGreaterThan(0, $result['net']);
        $this->assertLessThan(10000, $result['net']);
    }

    public function test_payroll_run_page_accessible(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('erp.payroll-runs.index'))
            ->assertOk();
    }
}
