<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreProjectRequest;
use App\Erp\Http\Requests\StoreTimeEntryRequest;
use App\Erp\Http\Requests\UpdateProjectRequest;
use App\Erp\Models\Customer;
use App\Erp\Models\Employee;
use App\Erp\Models\Project;
use App\Erp\Services\Projects\ProjectQuery;
use App\Erp\Support\ErpExportSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ProjectsController extends Controller
{
    public function __construct(private readonly ProjectQuery $query) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Project::class);

        return view('erp::admin.projects.index', [
            'projects'      => $this->query->paginate($request),
            'filters'       => $this->query->filters($request),
            'customers'     => Customer::query()->where('status', 'active')->orderBy('name')->pluck('name', 'id'),
            'exportColumns' => ErpExportSchema::columns('projects'),
            'exportFormats' => ErpExportSchema::formats('projects'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Project::class);

        return view('erp::admin.projects.form', $this->formData(new Project));
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::create($request->validated());

        return redirect()
            ->route('erp.projects.show', $project)
            ->with('erp_status', __('Proje oluşturuldu.'));
    }

    public function show(Project $project): View
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

    public function edit(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('erp::admin.projects.form', $this->formData($project));
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()
            ->route('erp.projects.show', $project)
            ->with('erp_status', __('Proje güncellendi.'));
    }

    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return redirect()
            ->route('erp.projects.index')
            ->with('erp_status', __('Proje silindi.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('erp.projects.delete');

        $validated = $request->validate([
            'record_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:erp_projects,id'],
        ]);

        $deleted = 0;
        Project::query()
            ->whereKey($validated['record_ids'])
            ->chunkById(200, function ($projects) use (&$deleted): void {
                foreach ($projects as $project) {
                    $project->delete();
                    $deleted++;
                }
            });

        return back()->with('erp_status', trans_choice(
            '{0} Hiçbiri silinemedi.|{1} :count proje silindi.|[2,*] :count proje silindi.',
            $deleted, ['count' => $deleted]
        ));
    }

    public function storeTimeEntry(StoreTimeEntryRequest $request, Project $project): RedirectResponse
    {
        $project->timeEntries()->create($request->validated());

        return back()->with('erp_status', __('Zaman girişi eklendi.'));
    }

    /** @return array<string, mixed> */
    private function formData(Project $project): array
    {
        return [
            'project'   => $project,
            'customers' => Customer::query()->where('status', 'active')->orderBy('name')->pluck('name', 'id'),
            'managers'  => Employee::query()->where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ];
    }
}
