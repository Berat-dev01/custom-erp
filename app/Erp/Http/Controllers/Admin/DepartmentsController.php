<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreDepartmentRequest;
use App\Erp\Http\Requests\UpdateDepartmentRequest;
use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use App\Erp\Services\Departments\DepartmentQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class DepartmentsController extends Controller
{
    public function __construct(private readonly DepartmentQuery $query) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Department::class);

        return view('erp::admin.departments.index', [
            'departments' => $this->query->paginate($request),
            'filters'     => $this->query->filters($request),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Department::class);

        return view('erp::admin.departments.form', $this->formData(new Department));
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Department::create($request->validated());

        return redirect()
            ->route('erp.departments.index')
            ->with('erp_status', __('Departman eklendi.'));
    }

    public function show(Department $department): View
    {
        Gate::authorize('view', $department);

        $department->load(['parent', 'manager', 'employees.position']);

        return view('erp::admin.departments.show', compact('department'));
    }

    public function edit(Department $department): View
    {
        Gate::authorize('update', $department);

        return view('erp::admin.departments.form', $this->formData($department));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return redirect()
            ->route('erp.departments.index')
            ->with('erp_status', __('Departman güncellendi.'));
    }

    public function destroy(Department $department): RedirectResponse
    {
        Gate::authorize('delete', $department);

        $department->delete();

        return redirect()
            ->route('erp.departments.index')
            ->with('erp_status', __('Departman silindi.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('erp.departments.delete');

        $validated = $request->validate([
            'record_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:erp_departments,id'],
        ]);

        $deleted = 0;
        Department::query()
            ->whereKey($validated['record_ids'])
            ->chunkById(200, function ($departments) use (&$deleted): void {
                foreach ($departments as $department) {
                    $department->delete();
                    $deleted++;
                }
            });

        return back()->with('erp_status', trans_choice(
            '{0} Hiçbiri silinemedi.|{1} :count departman silindi.|[2,*] :count departman silindi.',
            $deleted, ['count' => $deleted]
        ));
    }

    /** @return array<string, mixed> */
    private function formData(Department $department): array
    {
        return [
            'department' => $department,
            'parents'    => Department::query()->orderBy('name')->pluck('name', 'id'),
            'managers'   => Employee::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ];
    }
}
