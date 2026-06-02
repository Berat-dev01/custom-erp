<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreEmployeeRequest;
use App\Erp\Http\Requests\UpdateEmployeeRequest;
use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use App\Erp\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class EmployeesController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Employee::class);

        $query = Employee::query()->with(['department', 'position']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%");
            });
        }

        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $employees   = $query->latest()->paginate(20)->withQueryString();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('erp::admin.employees.index', compact('employees', 'departments'));
    }

    public function create()
    {
        Gate::authorize('create', Employee::class);

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $positions   = Position::where('is_active', true)->orderBy('name')->get();
        $managers    = Employee::where('status', 'active')->orderBy('first_name')->get();

        return view('erp::admin.employees.create', compact('departments', 'positions', 'managers'));
    }

    public function store(StoreEmployeeRequest $request)
    {
        $data = $request->validated();
        $data['employee_number'] = $this->generateEmployeeNumber();

        Employee::create($data);

        return redirect()->route('erp.employees.index')
            ->with('success', __('Çalışan başarıyla eklendi.'));
    }

    public function show(Employee $employee)
    {
        Gate::authorize('view', $employee);

        $employee->load(['department', 'position', 'manager', 'documents']);

        return view('erp::admin.employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        Gate::authorize('update', $employee);

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $positions   = Position::where('is_active', true)->orderBy('name')->get();
        $managers    = Employee::where('status', 'active')
            ->where('id', '!=', $employee->id)
            ->orderBy('first_name')->get();

        return view('erp::admin.employees.edit', compact('employee', 'departments', 'positions', 'managers'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $employee->update($request->validated());

        return redirect()->route('erp.employees.show', $employee)
            ->with('success', __('Çalışan güncellendi.'));
    }

    public function destroy(Employee $employee)
    {
        Gate::authorize('delete', $employee);

        $employee->delete();

        return redirect()->route('erp.employees.index')
            ->with('success', __('Çalışan silindi.'));
    }

    private function generateEmployeeNumber(): string
    {
        $last = Employee::withTrashed()->max('id') ?? 0;

        return 'EMP-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
    }
}
