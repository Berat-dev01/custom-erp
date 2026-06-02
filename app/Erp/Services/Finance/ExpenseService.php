<?php

namespace App\Erp\Services\Finance;

use App\Erp\Models\Expense;

class ExpenseService
{
    public function thisMonth(): float
    {
        return (float) Expense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');
    }

    public function lastMonths(int $count = 12): array
    {
        $months = collect(range($count - 1, 0))->map(fn (int $i) => now()->startOfMonth()->subMonths($i));

        return $months->map(function ($month) {
            return [
                'label'  => $month->translatedFormat('M Y'),
                'amount' => (float) Expense::whereYear('expense_date', $month->year)
                    ->whereMonth('expense_date', $month->month)
                    ->sum('amount'),
            ];
        })->all();
    }
}
