<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StorePayrollRunRequest;
use App\Erp\Models\Employee;
use App\Erp\Models\EmployeeSalary;
use App\Erp\Models\PayrollRun;
use App\Erp\Http\Requests\StoreEmployeeSalaryRequest;
use App\Erp\Services\Payroll\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class PayrollRunsController extends Controller
{
    public function __construct(private readonly PayrollService $service) {}

    public function index(Request $request)
    {
        Gate::authorize('erp.payroll.view');

        $runs = PayrollRun::withCount('payslips')
            ->orderByDesc('year')->orderByDesc('month')
            ->paginate(20);

        return view('erp::admin.payroll-runs.index', compact('runs'));
    }

    public function create()
    {
        Gate::authorize('erp.payroll.create');

        return view('erp::admin.payroll-runs.create');
    }

    public function store(StorePayrollRunRequest $request)
    {
        $run = $this->service->processPayrollRun(
            (int) $request->validated()['year'],
            (int) $request->validated()['month']
        );

        return redirect()->route('erp.payroll-runs.show', $run)
            ->with('success', __('Bordro hesaplandı.'));
    }

    public function show(PayrollRun $payrollRun)
    {
        Gate::authorize('erp.payroll.view');

        $payrollRun->load(['payslips.employee.department']);

        return view('erp::admin.payroll-runs.show', compact('payrollRun'));
    }

    public function approve(PayrollRun $payrollRun)
    {
        Gate::authorize('erp.payroll.approve');

        abort_if($payrollRun->status !== 'processed', 422, __('Sadece işlenmiş bordro onaylanabilir.'));

        $this->service->approveAndPay($payrollRun, Carbon::today());

        return redirect()->route('erp.payroll-runs.show', $payrollRun)
            ->with('success', __('Bordro onaylandı ve ödenmiş olarak işaretlendi.'));
    }

    // Çalışan maaş tanımlama
    public function salaryCreate(Employee $employee)
    {
        Gate::authorize('erp.payroll.create');

        $salaries = $employee->salaries()->orderByDesc('effective_from')->get();

        return view('erp::admin.payroll-runs.salary-create', compact('employee', 'salaries'));
    }

    public function salaryStore(StoreEmployeeSalaryRequest $request, Employee $employee)
    {
        $data = $request->validated();

        // Önceki aktif maaşın effective_to'sunu kapat
        EmployeeSalary::where('employee_id', $employee->id)
            ->whereNull('effective_to')
            ->where('effective_from', '<', $data['effective_from'])
            ->update(['effective_to' => Carbon::parse($data['effective_from'])->subDay()]);

        $employee->salaries()->create([
            'basic_salary'   => $data['basic_salary'],
            'currency'       => $data['currency'] ?? config('erp.currency', 'TRY'),
            'effective_from' => $data['effective_from'],
        ]);

        return redirect()->route('erp.employees.show', $employee)
            ->with('success', __('Maaş tanımı eklendi.'));
    }
}
