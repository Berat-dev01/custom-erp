<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StorePositionRequest;
use App\Erp\Http\Requests\UpdatePositionRequest;
use App\Erp\Models\Department;
use App\Erp\Models\Position;
use App\Erp\Services\Positions\PositionQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class PositionsController extends Controller
{
    public function __construct(private readonly PositionQuery $query) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Position::class);

        return view('erp::admin.positions.index', [
            'positions'   => $this->query->paginate($request),
            'filters'     => $this->query->filters($request),
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Position::class);

        return view('erp::admin.positions.form', [
            'position'    => new Position,
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(StorePositionRequest $request): RedirectResponse
    {
        Position::create($request->validated());

        return redirect()
            ->route('erp.positions.index')
            ->with('erp_status', __('Pozisyon eklendi.'));
    }

    public function edit(Position $position): View
    {
        Gate::authorize('update', $position);

        return view('erp::admin.positions.form', [
            'position'    => $position,
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(UpdatePositionRequest $request, Position $position): RedirectResponse
    {
        $position->update($request->validated());

        return redirect()
            ->route('erp.positions.index')
            ->with('erp_status', __('Pozisyon güncellendi.'));
    }

    public function destroy(Position $position): RedirectResponse
    {
        Gate::authorize('delete', $position);

        $position->delete();

        return redirect()
            ->route('erp.positions.index')
            ->with('erp_status', __('Pozisyon silindi.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('erp.positions.delete');

        $validated = $request->validate([
            'record_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:erp_positions,id'],
        ]);

        $deleted = 0;
        Position::query()
            ->whereKey($validated['record_ids'])
            ->chunkById(200, function ($positions) use (&$deleted): void {
                foreach ($positions as $position) {
                    $position->delete();
                    $deleted++;
                }
            });

        return back()->with('erp_status', trans_choice(
            '{0} Hiçbiri silinemedi.|{1} :count pozisyon silindi.|[2,*] :count pozisyon silindi.',
            $deleted, ['count' => $deleted]
        ));
    }
}
