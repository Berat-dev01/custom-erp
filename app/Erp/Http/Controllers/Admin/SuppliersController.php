<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreSupplierRequest;
use App\Erp\Http\Requests\UpdateSupplierRequest;
use App\Erp\Models\Supplier;
use App\Erp\Services\Suppliers\SupplierQuery;
use App\Erp\Support\ErpExportSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class SuppliersController extends Controller
{
    public function __construct(private readonly SupplierQuery $suppliers) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Supplier::class);

        return view('erp::admin.suppliers.index', [
            'suppliers'     => $this->suppliers->paginate($request),
            'filters'       => $this->suppliers->filters($request),
            'exportColumns' => ErpExportSchema::columns('suppliers'),
            'exportFormats' => ErpExportSchema::formats('suppliers'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Supplier::class);

        return view('erp::admin.suppliers.form', ['supplier' => new Supplier]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        return redirect()
            ->route('erp.suppliers.show', $supplier)
            ->with('erp_status', __('Tedarikçi eklendi.'));
    }

    public function show(Supplier $supplier): View
    {
        Gate::authorize('view', $supplier);

        $orders = $supplier->purchaseOrders()->latest()->limit(10)->get();

        return view('erp::admin.suppliers.show', compact('supplier', 'orders'));
    }

    public function edit(Supplier $supplier): View
    {
        Gate::authorize('update', $supplier);

        return view('erp::admin.suppliers.form', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('erp.suppliers.show', $supplier)
            ->with('erp_status', __('Tedarikçi güncellendi.'));
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        Gate::authorize('delete', $supplier);

        $supplier->delete();

        return redirect()
            ->route('erp.suppliers.index')
            ->with('erp_status', __('Tedarikçi silindi.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('erp.suppliers.delete');

        $validated = $request->validate([
            'record_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:erp_suppliers,id'],
        ]);

        $deleted = 0;
        Supplier::query()
            ->whereKey($validated['record_ids'])
            ->chunkById(200, function ($suppliers) use (&$deleted): void {
                foreach ($suppliers as $supplier) {
                    $supplier->delete();
                    $deleted++;
                }
            });

        return back()->with('erp_status', trans_choice(
            '{0} Hiçbiri silinemedi.|{1} :count tedarikçi silindi.|[2,*] :count tedarikçi silindi.',
            $deleted, ['count' => $deleted]
        ));
    }
}
