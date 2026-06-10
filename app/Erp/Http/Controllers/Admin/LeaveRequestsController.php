<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Employee;
use App\Erp\Models\LeaveRequest;
use App\Erp\Models\LeaveType;
use App\Erp\Services\HR\LeaveRequestQuery;
use App\Erp\Services\HR\LeaveService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class LeaveRequestsController extends Controller
{
    public function __construct(
        private LeaveService $leaveService,
        private LeaveRequestQuery $query,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('erp.leave.view');

        return view('erp::admin.leave-requests.index', [
            'requests'     => $this->query->paginate($request),
            'filters'      => $this->query->filters($request),
            'employees'    => Employee::query()->where('status', 'active')->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'leaveTypes'   => LeaveType::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'pendingCount' => LeaveRequest::where('status', 'pending')->count(),
        ]);
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

        $leaveRequest = LeaveRequest::create([
            ...$data,
            'days'   => $days,
            'status' => 'pending',
        ]);

        app(\App\Erp\Services\Notification\NotificationService::class)
            ->notifyLeaveRequest($leaveRequest->load('employee'), 'submitted');

        return redirect()->route('erp.leave-requests.index')
            ->with('erp_status', __('İzin talebi oluşturuldu.'));
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        Gate::authorize('erp.leave.approve');

        $approver = Employee::where('email', $request->user()->email)->first()
            ?? Employee::first();

        abort_if(! $approver, 422, __('Onaylayan çalışan bulunamadı.'));

        $this->leaveService->approveLeaveRequest($leaveRequest, $approver);

        return back()->with('erp_status', __('İzin talebi onaylandı.'));
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

        return back()->with('erp_status', __('İzin talebi reddedildi.'));
    }

    public function cancel(LeaveRequest $leaveRequest)
    {
        Gate::authorize('erp.leave.view');

        abort_if(! $leaveRequest->isPending(), 422, __('Sadece bekleyen talepler iptal edilebilir.'));

        $leaveRequest->update(['status' => 'cancelled']);

        return back()->with('erp_status', __('İzin talebi iptal edildi.'));
    }
}
