<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StorePositionRequest;
use App\Erp\Http\Requests\UpdatePositionRequest;
use App\Erp\Models\Department;
use App\Erp\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class PositionsController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Position::class);

        $positions = Position::with('department')
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('erp::admin.positions.index', compact('positions'));
    }

    public function create()
    {
        Gate::authorize('create', Position::class);

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('erp::admin.positions.create', compact('departments'));
    }

    public function store(StorePositionRequest $request)
    {
        Position::create($request->validated());

        return redirect()->route('erp.positions.index')
            ->with('success', __('Pozisyon eklendi.'));
    }

    public function edit(Position $position)
    {
        Gate::authorize('update', $position);

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('erp::admin.positions.edit', compact('position', 'departments'));
    }

    public function update(UpdatePositionRequest $request, Position $position)
    {
        $position->update($request->validated());

        return redirect()->route('erp.positions.index')
            ->with('success', __('Pozisyon güncellendi.'));
    }

    public function destroy(Position $position)
    {
        Gate::authorize('delete', $position);

        $position->delete();

        return redirect()->route('erp.positions.index')
            ->with('success', __('Pozisyon silindi.'));
    }
}
