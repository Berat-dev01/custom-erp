<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreSupplierRequest;
use App\Erp\Http\Requests\UpdateSupplierRequest;
use App\Erp\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class SuppliersController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Supplier::class);

        $query = Supplier::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $suppliers = $query->latest()->paginate(20)->withQueryString();

        return view('erp::admin.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        Gate::authorize('create', Supplier::class);

        return view('erp::admin.suppliers.create');
    }

    public function store(StoreSupplierRequest $request)
    {
        Supplier::create($request->validated());

        return redirect()->route('erp.suppliers.index')
            ->with('success', __('Tedarikçi eklendi.'));
    }

    public function show(Supplier $supplier)
    {
        Gate::authorize('view', $supplier);

        $orders = $supplier->purchaseOrders()->latest()->limit(10)->get();

        return view('erp::admin.suppliers.show', compact('supplier', 'orders'));
    }

    public function edit(Supplier $supplier)
    {
        Gate::authorize('update', $supplier);

        return view('erp::admin.suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $supplier->update($request->validated());

        return redirect()->route('erp.suppliers.index')
            ->with('success', __('Tedarikçi güncellendi.'));
    }

    public function destroy(Supplier $supplier)
    {
        Gate::authorize('delete', $supplier);

        $supplier->delete();

        return redirect()->route('erp.suppliers.index')
            ->with('success', __('Tedarikçi silindi.'));
    }
}
