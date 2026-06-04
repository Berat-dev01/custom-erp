<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreProjectTaskRequest;
use App\Erp\Models\Employee;
use App\Erp\Models\Project;
use App\Erp\Models\ProjectTask;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ProjectTasksController extends Controller
{
    public function store(StoreProjectTaskRequest $request, Project $project)
    {
        Gate::authorize('update', $project);

        $project->tasks()->create($request->validated());

        return back()->with('success', __('Görev eklendi.'));
    }

    public function update(StoreProjectTaskRequest $request, Project $project, ProjectTask $task)
    {
        Gate::authorize('update', $project);

        $data = $request->validated();

        if ($data['status'] === 'done' && $task->status !== 'done') {
            $task->markDone();
            unset($data['status']);
        }

        $task->update($data);

        return back()->with('success', __('Görev güncellendi.'));
    }

    public function updateStatus(Request $request, Project $project, ProjectTask $task)
    {
        Gate::authorize('update', $project);

        $status = $request->validate(['status' => ['required', 'in:todo,in_progress,review,done']])['status'];

        if ($status === 'done') {
            $task->markDone();
        } else {
            $task->update(['status' => $status, 'completed_at' => null]);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => $task->fresh()->status]);
        }

        return back()->with('success', __('Görev durumu güncellendi.'));
    }

    public function destroy(Project $project, ProjectTask $task)
    {
        Gate::authorize('update', $project);

        $task->delete();

        return back()->with('success', __('Görev silindi.'));
    }
}
