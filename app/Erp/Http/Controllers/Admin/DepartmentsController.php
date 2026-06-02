<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreDepartmentRequest;
use App\Erp\Http\Requests\UpdateDepartmentRequest;
use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class DepartmentsController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Department::class);

        $departments = Department::with(['parent', 'manager'])
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('erp::admin.departments.index', compact('departments'));
    }

    public function create()
    {
        Gate::authorize('create', Department::class);

        $parents  = Department::where('is_active', true)->orderBy('name')->get();
        $managers = Employee::where('status', 'active')->orderBy('first_name')->get();

        return view('erp::admin.departments.create', compact('parents', 'managers'));
    }

    public function store(StoreDepartmentRequest $request)
    {
        Department::create($request->validated());

        return redirect()->route('erp.departments.index')
            ->with('success', __('Departman eklendi.'));
    }

    public function edit(Department $department)
    {
        Gate::authorize('update', $department);

        $parents  = Department::where('is_active', true)->where('id', '!=', $department->id)->orderBy('name')->get();
        $managers = Employee::where('status', 'active')->orderBy('first_name')->get();

        return view('erp::admin.departments.edit', compact('department', 'parents', 'managers'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());

        return redirect()->route('erp.departments.index')
            ->with('success', __('Departman güncellendi.'));
    }

    public function destroy(Department $department)
    {
        Gate::authorize('delete', $department);

        $department->delete();

        return redirect()->route('erp.departments.index')
            ->with('success', __('Departman silindi.'));
    }
}
