<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Attendance;
use App\Erp\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('erp.attendance.view');

        $year  = (int) $request->get('year',  now()->year);
        $month = (int) $request->get('month', now()->month);

        $employees = Employee::where('status', 'active')
            ->with(['attendance' => fn ($q) => $q->whereYear('date', $year)->whereMonth('date', $month)])
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        return view('erp::admin.attendance.index', compact('employees', 'year', 'month', 'daysInMonth'));
    }

    public function store(Request $request)
    {
        Gate::authorize('erp.attendance.manage');

        $data = $request->validate([
            'employee_id'    => ['required', 'exists:erp_employees,id'],
            'date'           => ['required', 'date'],
            'status'         => ['required', 'in:present,absent,on_leave,holiday,half_day'],
            'check_in'       => ['nullable', 'date_format:H:i'],
            'check_out'      => ['nullable', 'date_format:H:i', 'after:check_in'],
            'overtime_hours' => ['nullable', 'numeric', 'min:0', 'max:12'],
        ]);

        $workHours = null;
        if ($data['check_in'] && ($data['check_out'] ?? null)) {
            $in  = Carbon::createFromFormat('H:i', $data['check_in']);
            $out = Carbon::createFromFormat('H:i', $data['check_out']);
            $workHours = round($in->diffInMinutes($out) / 60, 2);
        }

        Attendance::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'date' => $data['date']],
            [...$data, 'work_hours' => $workHours]
        );

        return back()->with('success', __('Devam kaydı güncellendi.'));
    }

    public function monthlyReport(Request $request, Employee $employee)
    {
        Gate::authorize('erp.attendance.view');

        $year  = (int) $request->get('year',  now()->year);
        $month = (int) $request->get('month', now()->month);

        $records = Attendance::where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        $summary = [
            'present'    => $records->where('status', 'present')->count(),
            'absent'     => $records->where('status', 'absent')->count(),
            'on_leave'   => $records->where('status', 'on_leave')->count(),
            'total_hours'=> $records->sum('work_hours'),
            'overtime'   => $records->sum('overtime_hours'),
        ];

        return view('erp::admin.attendance.monthly-report', compact('employee', 'records', 'summary', 'year', 'month'));
    }
}
