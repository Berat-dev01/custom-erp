<?php

namespace App\Erp\Services\Payroll;

use App\Erp\Models\PayrollParameter;

class TurkishPayrollCalculator
{
    /**
     * Brüt maaştan net maaşı hesapla (Türkiye yasal hesaplaması).
     *
     * @return array{
     *   gross: float,
     *   sgk_worker: float,
     *   unemployment_worker: float,
     *   income_tax_base: float,
     *   income_tax: float,
     *   stamp_tax: float,
     *   agi: float,
     *   net: float,
     *   payment: float,
     *   employer_cost: float,
     *   sgk_employer: float,
     *   unemployment_employer: float,
     * }
     */
    public function calculate(
        float  $grossSalary,
        int    $year,
        int    $month,
        float  $cumulativeGrossYTD = 0.0,
        string $maritalStatus      = 'single',
        int    $dependentChildren  = 0,
    ): array {
        $params = PayrollParameter::forYear($year);

        if (! $params) {
            return $this->fallbackCalculation($grossSalary);
        }

        $sgkWorker          = round($grossSalary * (float) $params->sgk_worker_rate, 2);
        $unemploymentWorker = round($grossSalary * (float) $params->unemployment_worker_rate, 2);

        $incomeTaxBase = $grossSalary - $sgkWorker - $unemploymentWorker;

        // Kümülatif gelir vergisi dilim hesabı
        $cumulativePrev  = max(0, $cumulativeGrossYTD - $grossSalary);
        $incomeTaxPrev   = $this->calculateCumulativeTax((float) $cumulativePrev, $params->income_tax_brackets);
        $incomeTaxCurrent= $this->calculateCumulativeTax((float) ($cumulativePrev + $incomeTaxBase), $params->income_tax_brackets);
        $incomeTax       = round(max(0, $incomeTaxCurrent - $incomeTaxPrev), 2);

        // Damga vergisi (net maaş üzerinden)
        $netBeforeStamp = $grossSalary - $sgkWorker - $unemploymentWorker - $incomeTax;
        $stampTax       = round($grossSalary * (float) $params->stamp_tax_rate, 2);

        // AGİ
        $agi = $this->calculateAgi($params, $maritalStatus, $dependentChildren);

        $net     = round($netBeforeStamp - $stampTax + $agi, 2);
        $payment = $net;

        // İşveren maliyeti
        $sgkEmployer          = round($grossSalary * (float) $params->sgk_employer_rate, 2);
        $unemploymentEmployer = round($grossSalary * (float) $params->unemployment_employer_rate, 2);
        $employerCost         = round($grossSalary + $sgkEmployer + $unemploymentEmployer, 2);

        return [
            'gross'                => $grossSalary,
            'sgk_worker'           => $sgkWorker,
            'unemployment_worker'  => $unemploymentWorker,
            'income_tax_base'      => $incomeTaxBase,
            'income_tax'           => $incomeTax,
            'stamp_tax'            => $stampTax,
            'agi'                  => $agi,
            'net'                  => $net,
            'payment'              => $payment,
            'employer_cost'        => $employerCost,
            'sgk_employer'         => $sgkEmployer,
            'unemployment_employer'=> $unemploymentEmployer,
        ];
    }

    public function employerCost(float $grossSalary, int $year): float
    {
        $params = PayrollParameter::forYear($year);

        if (! $params) {
            return round($grossSalary * 1.205, 2);
        }

        return round(
            $grossSalary
            * (1 + (float) $params->sgk_employer_rate + (float) $params->unemployment_employer_rate),
            2
        );
    }

    private function calculateCumulativeTax(float $cumulativeBase, array $brackets): float
    {
        $tax       = 0.0;
        $remaining = $cumulativeBase;

        foreach ($brackets as $bracket) {
            if ($remaining <= 0) {
                break;
            }

            $limit = $bracket['limit'] ?? PHP_FLOAT_MAX;
            $rate  = (float) ($bracket['rate'] ?? 0);
            $chunk = min($remaining, $limit);
            $tax  += $chunk * $rate;
            $remaining -= $chunk;
        }

        return round($tax, 2);
    }

    private function calculateAgi(PayrollParameter $params, string $maritalStatus, int $children): float
    {
        $base = (float) $params->agi_single;

        if ($maritalStatus === 'married') {
            $base = (float) $params->agi_married_spouse_not_working;
        }

        return round($base, 2);
    }

    /**
     * Parametre tablosu yokken kaba hesap.
     */
    private function fallbackCalculation(float $gross): array
    {
        $sgk       = round($gross * 0.14, 2);
        $isizlik   = round($gross * 0.01, 2);
        $matrah    = $gross - $sgk - $isizlik;
        $gv        = round($matrah * 0.15, 2);
        $damga     = round($gross * 0.00759, 2);
        $net       = round($gross - $sgk - $isizlik - $gv - $damga, 2);

        return [
            'gross'                => $gross,
            'sgk_worker'           => $sgk,
            'unemployment_worker'  => $isizlik,
            'income_tax_base'      => $matrah,
            'income_tax'           => $gv,
            'stamp_tax'            => $damga,
            'agi'                  => 0.0,
            'net'                  => $net,
            'payment'              => $net,
            'employer_cost'        => round($gross * 1.205, 2),
            'sgk_employer'         => round($gross * 0.155, 2),
            'unemployment_employer'=> round($gross * 0.02, 2),
        ];
    }
}
