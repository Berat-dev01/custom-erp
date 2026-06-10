<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreCustomerRequest;
use App\Erp\Http\Requests\UpdateCustomerRequest;
use App\Erp\Models\Customer;
use App\Erp\Services\Customers\CustomerQuery;
use App\Erp\Support\ErpExportSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\View\View;

class CustomersController extends Controller
{
    public function __construct(private readonly CustomerQuery $customers) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Customer::class);

        return view('erp::admin.customers.index', [
            'customers'     => $this->customers->paginate($request),
            'filters'       => $this->customers->filters($request),
            'exportColumns' => ErpExportSchema::columns('customers'),
            'exportFormats' => ErpExportSchema::formats('customers'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Customer::class);

        return view('erp::admin.customers.form', ['customer' => new Customer]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        return redirect()
            ->route('erp.customers.show', $customer)
            ->with('erp_status', __('Müşteri eklendi.'));
    }

    public function show(Customer $customer): View
    {
        Gate::authorize('view', $customer);

        $orders = $customer->salesOrders()->latest()->limit(10)->get();

        return view('erp::admin.customers.show', compact('customer', 'orders'));
    }

    public function edit(Customer $customer): View
    {
        Gate::authorize('update', $customer);

        return view('erp::admin.customers.form', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()
            ->route('erp.customers.show', $customer)
            ->with('erp_status', __('Müşteri güncellendi.'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        Gate::authorize('delete', $customer);

        $customer->delete();

        return redirect()
            ->route('erp.customers.index')
            ->with('erp_status', __('Müşteri silindi.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('erp.customers.delete');

        $validated = $request->validate([
            'record_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:erp_customers,id'],
        ]);

        $deleted = 0;
        Customer::query()
            ->whereKey($validated['record_ids'])
            ->chunkById(200, function ($customers) use (&$deleted): void {
                foreach ($customers as $customer) {
                    $customer->delete();
                    $deleted++;
                }
            });

        return back()->with('erp_status', trans_choice(
            '{0} Hiçbiri silinemedi.|{1} :count müşteri silindi.|[2,*] :count müşteri silindi.',
            $deleted, ['count' => $deleted]
        ));
    }
}
