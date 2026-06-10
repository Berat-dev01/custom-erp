<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreEmployeeRequest;
use App\Erp\Http\Requests\UpdateEmployeeRequest;
use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use App\Erp\Models\Position;
use App\Erp\Services\Employees\EmployeeQuery;
use App\Erp\Support\ErpExportSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class EmployeesController extends Controller
{
    public function __construct(private readonly EmployeeQuery $employees) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Employee::class);

        return view('erp::admin.employees.index', [
            'employees'     => $this->employees->paginate($request),
            'filters'       => $this->employees->filters($request),
            'departments'   => Department::query()->orderBy('name')->pluck('name', 'id'),
            'exportColumns' => ErpExportSchema::columns('employees'),
            'exportFormats' => ErpExportSchema::formats('employees'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Employee::class);

        return view('erp::admin.employees.form', $this->formData(new Employee));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($request->validated());

        return redirect()
            ->route('erp.employees.show', $employee)
            ->with('erp_status', __('Çalışan eklendi.'));
    }

    public function show(Employee $employee): View
    {
        Gate::authorize('view', $employee);

        $employee->load(['department', 'position', 'manager', 'documents', 'salaries']);

        return view('erp::admin.employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        Gate::authorize('update', $employee);

        return view('erp::admin.employees.form', $this->formData($employee));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()
            ->route('erp.employees.show', $employee)
            ->with('erp_status', __('Çalışan güncellendi.'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        Gate::authorize('delete', $employee);

        $employee->delete();

        return redirect()
            ->route('erp.employees.index')
            ->with('erp_status', __('Çalışan silindi.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('erp.employees.delete');

        $validated = $request->validate([
            'record_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:erp_employees,id'],
        ]);

        $deleted = 0;
        Employee::query()
            ->whereKey($validated['record_ids'])
            ->chunkById(200, function ($employees) use (&$deleted): void {
                foreach ($employees as $employee) {
                    $employee->delete();
                    $deleted++;
                }
            });

        return back()->with('erp_status', trans_choice(
            '{0} Hiçbiri silinemedi.|{1} :count çalışan silindi.|[2,*] :count çalışan silindi.',
            $deleted, ['count' => $deleted]
        ));
    }

    /** @return array<string, mixed> */
    private function formData(Employee $employee): array
    {
        return [
            'employee'    => $employee,
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'positions'   => Position::query()->orderBy('title')->pluck('title', 'id'),
            'managers'    => Employee::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ];
    }
}
