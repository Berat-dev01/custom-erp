<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreProjectRequest;
use App\Erp\Http\Requests\StoreTimeEntryRequest;
use App\Erp\Http\Requests\UpdateProjectRequest;
use App\Erp\Models\Customer;
use App\Erp\Models\Employee;
use App\Erp\Models\Project;
use App\Erp\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ProjectsController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Project::class);

        $query = Project::query()->with(['customer', 'manager'])
            ->withCount('tasks');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $projects = $query->latest()->paginate(20)->withQueryString();

        return view('erp::admin.projects.index', compact('projects'));
    }

    public function create()
    {
        Gate::authorize('create', Project::class);

        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $managers  = Employee::where('status', 'active')->orderBy('first_name')->get();

        return view('erp::admin.projects.create', compact('customers', 'managers'));
    }

    public function store(StoreProjectRequest $request)
    {
        Project::create($request->validated());

        return redirect()->route('erp.projects.index')
            ->with('success', __('Proje oluşturuldu.'));
    }

    public function show(Project $project)
    {
        Gate::authorize('view', $project);

        $project->load(['customer', 'manager', 'tasks.assignee']);

        $tasksByStatus = $project->tasks->groupBy('status');
        $totalHours    = $project->totalHours();
        $completion    = $project->completionRate();

        $recentEntries = $project->timeEntries()
            ->with(['employee', 'task'])
            ->latest('date')
            ->limit(10)
            ->get();

        return view('erp::admin.projects.show', compact(
            'project', 'tasksByStatus', 'totalHours', 'completion', 'recentEntries'
        ));
    }

    public function edit(Project $project)
    {
        Gate::authorize('update', $project);

        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $managers  = Employee::where('status', 'active')->orderBy('first_name')->get();

        return view('erp::admin.projects.edit', compact('project', 'customers', 'managers'));
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $project->update($request->validated());

        return redirect()->route('erp.projects.show', $project)
            ->with('success', __('Proje güncellendi.'));
    }

    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return redirect()->route('erp.projects.index')
            ->with('success', __('Proje silindi.'));
    }

    public function storeTimeEntry(StoreTimeEntryRequest $request, Project $project)
    {
        $project->timeEntries()->create($request->validated());

        return back()->with('success', __('Zaman girişi eklendi.'));
    }
}
