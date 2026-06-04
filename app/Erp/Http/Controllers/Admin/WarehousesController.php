<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreWarehouseRequest;
use App\Erp\Http\Requests\UpdateWarehouseRequest;
use App\Erp\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class WarehousesController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Warehouse::class);

        $warehouses = Warehouse::withCount('stockLevels')->orderBy('name')->paginate(20);

        return view('erp::admin.warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        Gate::authorize('create', Warehouse::class);

        return view('erp::admin.warehouses.create');
    }

    public function store(StoreWarehouseRequest $request)
    {
        $data = $request->validated();

        if (! empty($data['is_default'])) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        Warehouse::create($data);

        return redirect()->route('erp.warehouses.index')
            ->with('success', __('Depo eklendi.'));
    }

    public function edit(Warehouse $warehouse)
    {
        Gate::authorize('update', $warehouse);

        return view('erp::admin.warehouses.edit', compact('warehouse'));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        $data = $request->validated();

        if (! empty($data['is_default'])) {
            Warehouse::where('is_default', true)->where('id', '!=', $warehouse->id)->update(['is_default' => false]);
        }

        $warehouse->update($data);

        return redirect()->route('erp.warehouses.index')
            ->with('success', __('Depo güncellendi.'));
    }

    public function destroy(Warehouse $warehouse)
    {
        Gate::authorize('delete', $warehouse);

        $warehouse->delete();

        return redirect()->route('erp.warehouses.index')
            ->with('success', __('Depo silindi.'));
    }
}
