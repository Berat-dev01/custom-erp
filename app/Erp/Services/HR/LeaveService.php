<?php

namespace App\Erp\Services\HR;

use App\Erp\Models\Employee;
use App\Erp\Models\LeaveBalance;
use App\Erp\Models\LeaveRequest;
use App\Erp\Models\LeaveType;
use App\Erp\Models\PublicHoliday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    /**
     * Türk iş hukuku: <1y=14gün, 1-5y=14gün, 5-15y=20gün, >15y=26gün
     */
    public function calculateEntitlement(Employee $employee, int $year): float
    {
        $hireDate   = Carbon::parse($employee->hire_date);
        $yearStart  = Carbon::create($year, 1, 1);
        $yearsWorked = $hireDate->diffInYears($yearStart);

        if ($yearsWorked < 1) {
            return 0;
        }

        return match (true) {
            $yearsWorked >= 15 => 26.0,
            $yearsWorked >= 5  => 20.0,
            default            => 14.0,
        };
    }

    /**
     * İki tarih arasındaki iş günü sayısı (hafta sonu ve resmi tatil hariç).
     */
    public function calculateWorkDays(Carbon $start, Carbon $end): float
    {
        $holidays = PublicHoliday::whereBetween('date', [
            $start->toDateString(),
            $end->toDateString(),
        ])->pluck('date')->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'));

        $days    = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if (! $current->isWeekend() && ! $holidays->contains($current->format('Y-m-d'))) {
                $days++;
            }
            $current->addDay();
        }

        return (float) $days;
    }

    /**
     * İzin talebini onayla: bakiyeden düş, attendance kayıtla.
     */
    public function approveLeaveRequest(LeaveRequest $leaveRequest, Employee $approver): void
    {
        abort_if(! $leaveRequest->isPending(), 422, __('Bu talep zaten işlenmiş.'));

        DB::transaction(function () use ($leaveRequest, $approver): void {
            $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', $leaveRequest->start_date->year)
                ->first();

            if ($balance) {
                $balance->increment('used_days', $leaveRequest->days);
            }

            $leaveRequest->update([
                'status'      => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);
        });
    }

    public function rejectLeaveRequest(LeaveRequest $leaveRequest, Employee $approver, string $reason = ''): void
    {
        abort_if(! $leaveRequest->isPending(), 422, __('Bu talep zaten işlenmiş.'));

        $leaveRequest->update([
            'status'           => 'rejected',
            'approved_by'      => $approver->id,
            'approved_at'      => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Çalışan için belirli yıl+tip izin bakiyesi alır veya oluşturur.
     */
    public function getOrCreateBalance(Employee $employee, LeaveType $leaveType, int $year): LeaveBalance
    {
        return LeaveBalance::firstOrCreate(
            ['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => $year],
            ['entitled_days' => $leaveType->days_per_year > 0 ? $leaveType->days_per_year : $this->calculateEntitlement($employee, $year)]
        );
    }

    /**
     * Yeni yıl başında devir eden izinleri bir sonraki yıla aktar.
     */
    public function carryOverBalances(int $fromYear, int $toYear): int
    {
        $count = 0;

        LeaveBalance::where('year', $fromYear)
            ->whereHas('leaveType', fn ($q) => $q->where('carry_over', true))
            ->with('leaveType', 'employee')
            ->chunkById(200, function ($balances) use ($toYear, &$count): void {
                foreach ($balances as $balance) {
                    $remaining = $balance->remainingDays();
                    if ($remaining <= 0) {
                        continue;
                    }

                    $carryDays = min($remaining, (float) $balance->leaveType->max_carry_over_days);

                    LeaveBalance::where([
                        'employee_id'   => $balance->employee_id,
                        'leave_type_id' => $balance->leave_type_id,
                        'year'          => $toYear,
                    ])->increment('carried_over_days', $carryDays);

                    $count++;
                }
            });

        return $count;
    }

    /**
     * Belirtilen tarih aralığında çalışanın onaylı izin talebiyle çakışma var mı?
     */
    public function hasConflict(Employee $employee, Carbon $start, Carbon $end, ?int $excludeId = null): bool
    {
        return LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where(fn ($q) => $q
                ->whereBetween('start_date', [$start, $end])
                ->orWhereBetween('end_date', [$start, $end])
                ->orWhere(fn ($q2) => $q2->where('start_date', '<=', $start)->where('end_date', '>=', $end))
            )
            ->exists();
    }
}
