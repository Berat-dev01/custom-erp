<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreCustomerRequest;
use App\Erp\Http\Requests\UpdateCustomerRequest;
use App\Erp\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class CustomersController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Customer::class);

        $query = Customer::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('tax_number', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $customers = $query->latest()->paginate(20)->withQueryString();

        return view('erp::admin.customers.index', compact('customers'));
    }

    public function create()
    {
        Gate::authorize('create', Customer::class);

        return view('erp::admin.customers.create');
    }

    public function store(StoreCustomerRequest $request)
    {
        Customer::create($request->validated());

        return redirect()->route('erp.customers.index')
            ->with('success', __('Müşteri eklendi.'));
    }

    public function show(Customer $customer)
    {
        Gate::authorize('view', $customer);

        $orders = $customer->salesOrders()->latest()->limit(10)->get();

        return view('erp::admin.customers.show', compact('customer', 'orders'));
    }

    public function edit(Customer $customer)
    {
        Gate::authorize('update', $customer);

        return view('erp::admin.customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return redirect()->route('erp.customers.index')
            ->with('success', __('Müşteri güncellendi.'));
    }

    public function destroy(Customer $customer)
    {
        Gate::authorize('delete', $customer);

        $customer->delete();

        return redirect()->route('erp.customers.index')
            ->with('success', __('Müşteri silindi.'));
    }
}
