<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreWarehouseRequest;
use App\Erp\Http\Requests\UpdateWarehouseRequest;
use App\Erp\Models\Warehouse;
use App\Erp\Services\Warehouses\WarehouseQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class WarehousesController extends Controller
{
    public function __construct(private readonly WarehouseQuery $query) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Warehouse::class);

        return view('erp::admin.warehouses.index', [
            'warehouses' => $this->query->paginate($request),
            'filters'    => $this->query->filters($request),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Warehouse::class);

        return view('erp::admin.warehouses.form', ['warehouse' => new Warehouse]);
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['is_default'])) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        $warehouse = Warehouse::create($data);

        return redirect()
            ->route('erp.warehouses.show', $warehouse)
            ->with('erp_status', __('Depo eklendi.'));
    }

    public function show(Warehouse $warehouse): View
    {
        Gate::authorize('view', $warehouse);

        $warehouse->load(['stockLevels.product']);

        return view('erp::admin.warehouses.show', compact('warehouse'));
    }

    public function edit(Warehouse $warehouse): View
    {
        Gate::authorize('update', $warehouse);

        return view('erp::admin.warehouses.form', compact('warehouse'));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['is_default'])) {
            Warehouse::where('is_default', true)->where('id', '!=', $warehouse->id)->update(['is_default' => false]);
        }

        $warehouse->update($data);

        return redirect()
            ->route('erp.warehouses.show', $warehouse)
            ->with('erp_status', __('Depo güncellendi.'));
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        Gate::authorize('delete', $warehouse);

        $warehouse->delete();

        return redirect()
            ->route('erp.warehouses.index')
            ->with('erp_status', __('Depo silindi.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('erp.warehouses.delete');

        $validated = $request->validate([
            'record_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:erp_warehouses,id'],
        ]);

        $deleted = 0;
        Warehouse::query()
            ->whereKey($validated['record_ids'])
            ->chunkById(200, function ($warehouses) use (&$deleted): void {
                foreach ($warehouses as $warehouse) {
                    $warehouse->delete();
                    $deleted++;
                }
            });

        return back()->with('erp_status', trans_choice(
            '{0} Hiçbiri silinemedi.|{1} :count depo silindi.|[2,*] :count depo silindi.',
            $deleted, ['count' => $deleted]
        ));
    }
}
