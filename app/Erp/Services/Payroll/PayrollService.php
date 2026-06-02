<?php

namespace App\Erp\Services\Payroll;

use App\Erp\Models\Employee;
use App\Erp\Models\Payslip;
use App\Erp\Models\PayrollRun;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class PayrollService
{
    public function __construct(private TurkishPayrollCalculator $calculator) {}

    public function processPayrollRun(int $year, int $month): PayrollRun
    {
        $existing = PayrollRun::where('year', $year)->where('month', $month)->first();

        abort_if(
            $existing && in_array($existing->status, ['approved', 'paid']),
            422,
            __('Bu dönem için bordro zaten onaylanmış.')
        );

        return DB::transaction(function () use ($year, $month, $existing): PayrollRun {
            $run = $existing ?? PayrollRun::create([
                'year'       => $year,
                'month'      => $month,
                'status'     => 'draft',
                'created_by' => auth()->id(),
            ]);

            if ($existing && $existing->status === 'processed') {
                $run->payslips()->delete();
            }

            $employees = Employee::where('status', 'active')->get();

            $totalGross      = 0;
            $totalDeductions = 0;
            $totalNet        = 0;

            foreach ($employees as $employee) {
                $payslip = $this->calculatePayslip($employee, $run);

                $totalGross      += (float) $payslip->gross_salary;
                $totalDeductions += (float) $payslip->total_deductions;
                $totalNet        += (float) $payslip->net_salary;
            }

            $run->update([
                'status'           => 'processed',
                'total_gross'      => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net'        => $totalNet,
            ]);

            return $run;
        });
    }

    public function calculatePayslip(Employee $employee, PayrollRun $run): Payslip
    {
        $salary = $employee->currentSalary();
        $basic  = $salary ? (float) $salary->basic_salary : 0;

        // Kümülatif YTD (bu yılın bordrosundaki brüt toplamı)
        $cumulativeYTD = Payslip::whereHas('payrollRun', fn ($q) => $q
                ->where('year', $run->year)
                ->where('month', '<', $run->month)
            )
            ->where('employee_id', $employee->id)
            ->sum('gross_salary');

        $result = $this->calculator->calculate(
            grossSalary:       $basic,
            year:              $run->year,
            month:             $run->month,
            cumulativeGrossYTD: (float) $cumulativeYTD + $basic,
        );

        $totalDeductions = $result['sgk_worker'] + $result['unemployment_worker']
            + $result['income_tax'] + $result['stamp_tax'];

        $breakdown = array_merge($result, [
            'basic_salary'     => $basic,
            'total_deductions' => round($totalDeductions, 2),
        ]);

        return Payslip::updateOrCreate(
            ['payroll_run_id' => $run->id, 'employee_id' => $employee->id],
            [
                'basic_salary'     => $basic,
                'gross_salary'     => $result['gross'],
                'total_deductions' => round($totalDeductions, 2),
                'net_salary'       => $result['payment'],
                'breakdown'        => $breakdown,
                'status'           => 'draft',
            ]
        );
    }

    public function approveAndPay(PayrollRun $run, Carbon $payDate): void
    {
        abort_if($run->status !== 'processed', 422, __('Sadece işlenmiş bordro onaylanabilir.'));

        DB::transaction(function () use ($run, $payDate): void {
            $run->payslips()->update(['status' => 'paid']);

            $run->update([
                'status'   => 'paid',
                'pay_date' => $payDate,
            ]);

            app(\App\Erp\Services\Accounting\AccountingService::class)->postPayroll($run->fresh());
        });
    }

    public function generatePayslipPdf(Payslip $payslip): string
    {
        try {
            $payslip->loadMissing(['employee.department', 'employee.position', 'payrollRun']);

            $html = View::make('erp::admin.payslips.pdf', compact('payslip'))->render();

            $options = new Options();
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return $dompdf->output();
        } catch (\Throwable $e) {
            Log::error('Payslip PDF generation failed', [
                'payslip_id' => $payslip->id,
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }

}
