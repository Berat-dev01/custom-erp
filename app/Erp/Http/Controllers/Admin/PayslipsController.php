<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Payslip;
use App\Erp\Services\Payroll\PayrollService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class PayslipsController extends Controller
{
    public function __construct(private readonly PayrollService $service) {}

    public function show(Payslip $payslip)
    {
        Gate::authorize('erp.payroll.view');

        $payslip->load(['employee.department', 'employee.position', 'payrollRun']);

        return view('erp::admin.payslips.show', compact('payslip'));
    }

    public function pdf(Payslip $payslip)
    {
        Gate::authorize('erp.payroll.view');

        $pdf = $this->service->generatePayslipPdf($payslip);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="payslip-' . $payslip->id . '.pdf"',
        ]);
    }
}
