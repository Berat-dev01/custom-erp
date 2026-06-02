<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Employee;
use App\Erp\Models\LeaveRequest;
use App\Erp\Models\LeaveType;
use App\Erp\Services\HR\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class LeaveRequestsController extends Controller
{
    public function __construct(private LeaveService $leaveService) {}

    public function index(Request $request)
    {
        Gate::authorize('erp.leave.view');

        $query = LeaveRequest::with(['employee.department', 'leaveType'])
            ->when($request->get('status'),      fn ($q, $v) => $q->where('status', $v))
            ->when($request->get('employee_id'), fn ($q, $v) => $q->where('employee_id', $v))
            ->when($request->get('leave_type_id'), fn ($q, $v) => $q->where('leave_type_id', $v))
            ->latest();

        $requests    = $query->paginate(25)->withQueryString();
        $employees   = Employee::where('status', 'active')->orderBy('last_name')->get();
        $leaveTypes  = LeaveType::where('is_active', true)->orderBy('name')->get();
        $pendingCount = LeaveRequest::where('status', 'pending')->count();

        return view('erp::admin.leave-requests.index', compact('requests', 'employees', 'leaveTypes', 'pendingCount'));
    }

    public function create()
    {
        Gate::authorize('erp.leave.create');

        $employees  = Employee::where('status', 'active')->orderBy('last_name')->get();
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

        return view('erp::admin.leave-requests.create', compact('employees', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        Gate::authorize('erp.leave.create');

        $data = $request->validate([
            'employee_id'   => ['required', 'exists:erp_employees,id'],
            'leave_type_id' => ['required', 'exists:erp_leave_types,id'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'reason'        => ['nullable', 'string', 'max:500'],
        ]);

        $employee   = Employee::findOrFail($data['employee_id']);
        $start      = Carbon::parse($data['start_date']);
        $end        = Carbon::parse($data['end_date']);

        if ($this->leaveService->hasConflict($employee, $start, $end)) {
            return back()->withErrors(['start_date' => __('Bu tarihler için onaylı/bekleyen izin talebi mevcut.')])->withInput();
        }

        $days = $this->leaveService->calculateWorkDays($start, $end);

        if ($days <= 0) {
            return back()->withErrors(['start_date' => __('Seçilen tarih aralığında iş günü yok.')])->withInput();
        }

        LeaveRequest::create([
            ...$data,
            'days'   => $days,
            'status' => 'pending',
        ]);

        return redirect()->route('erp.leave-requests.index')
            ->with('success', __('İzin talebi oluşturuldu.'));
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        Gate::authorize('erp.leave.approve');

        $approver = Employee::where('email', $request->user()->email)->first()
            ?? Employee::first();

        abort_if(! $approver, 422, __('Onaylayan çalışan bulunamadı.'));

        $this->leaveService->approveLeaveRequest($leaveRequest, $approver);

        return back()->with('success', __('İzin talebi onaylandı.'));
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        Gate::authorize('erp.leave.approve');

        $data = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $approver = Employee::where('email', $request->user()->email)->first()
            ?? Employee::first();

        abort_if(! $approver, 422, __('Onaylayan çalışan bulunamadı.'));

        $this->leaveService->rejectLeaveRequest($leaveRequest, $approver, $data['rejection_reason'] ?? '');

        return back()->with('success', __('İzin talebi reddedildi.'));
    }

    public function cancel(LeaveRequest $leaveRequest)
    {
        Gate::authorize('erp.leave.view');

        abort_if(! $leaveRequest->isPending(), 422, __('Sadece bekleyen talepler iptal edilebilir.'));

        $leaveRequest->update(['status' => 'cancelled']);

        return back()->with('success', __('İzin talebi iptal edildi.'));
    }
}
