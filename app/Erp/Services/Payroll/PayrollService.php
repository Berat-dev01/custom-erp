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

        // Temel hesaplama (Türkiye iş kanunu temel çerçevesi)
        $sgkWorkerRate        = 0.14;
        $unemploymentWorkerRate = 0.01;
        $stampTaxRate         = 0.00759;

        $sgkDeduction         = $basic * $sgkWorkerRate;
        $unemploymentDeduction= $basic * $unemploymentWorkerRate;
        $incomeTaxBase        = $basic - $sgkDeduction - $unemploymentDeduction;
        $incomeTax            = $this->calculateIncomeTax($incomeTaxBase);
        $stampTax             = $basic * $stampTaxRate;

        $totalDeductions = $sgkDeduction + $unemploymentDeduction + $incomeTax + $stampTax;
        $gross           = $basic;
        $net             = $gross - $totalDeductions;

        $breakdown = [
            'basic_salary'          => $basic,
            'gross_salary'          => $gross,
            'sgk_worker'            => round($sgkDeduction, 2),
            'unemployment_worker'   => round($unemploymentDeduction, 2),
            'income_tax_base'       => round($incomeTaxBase, 2),
            'income_tax'            => round($incomeTax, 2),
            'stamp_tax'             => round($stampTax, 2),
            'total_deductions'      => round($totalDeductions, 2),
            'net_salary'            => round($net, 2),
        ];

        return Payslip::updateOrCreate(
            ['payroll_run_id' => $run->id, 'employee_id' => $employee->id],
            [
                'basic_salary'     => $basic,
                'gross_salary'     => $gross,
                'total_deductions' => round($totalDeductions, 2),
                'net_salary'       => round($net, 2),
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

    private function calculateIncomeTax(float $base): float
    {
        // 2026 Türkiye gelir vergisi dilimleri (örnek)
        $brackets = [
            ['limit' => 110000,  'rate' => 0.15],
            ['limit' => 230000,  'rate' => 0.20],
            ['limit' => 870000,  'rate' => 0.27],
            ['limit' => 3000000, 'rate' => 0.35],
            ['limit' => PHP_INT_MAX, 'rate' => 0.40],
        ];

        // Aylık matrah → yıllık projeksiyonu basit temsil (gerçek hesap kümülatif olmalı)
        $annualBase = $base * 12;
        $annualTax  = 0;
        $prev       = 0;

        foreach ($brackets as $bracket) {
            if ($annualBase <= $prev) {
                break;
            }

            $taxable    = min($annualBase, $bracket['limit']) - $prev;
            $annualTax += $taxable * $bracket['rate'];
            $prev       = $bracket['limit'];
        }

        return $annualTax / 12;
    }
}
