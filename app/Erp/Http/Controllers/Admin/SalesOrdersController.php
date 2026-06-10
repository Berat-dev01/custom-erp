<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreSalesOrderRequest;
use App\Erp\Models\Customer;
use App\Erp\Models\Product;
use App\Erp\Models\SalesOrder;
use App\Erp\Models\SalesOrderItem;
use App\Erp\Models\Warehouse;
use App\Erp\Services\Sales\SalesOrderQuery;
use App\Erp\Services\Sales\SalesOrderService;
use App\Erp\Support\ErpExportSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SalesOrdersController extends Controller
{
    public function __construct(
        private readonly SalesOrderService $service,
        private readonly SalesOrderQuery $query,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SalesOrder::class);

        return view('erp::admin.sales-orders.index', [
            'orders'        => $this->query->paginate($request),
            'filters'       => $this->query->filters($request),
            'customers'     => Customer::query()->where('status', 'active')->orderBy('name')->pluck('name', 'id'),
            'exportColumns' => ErpExportSchema::columns('sales-orders'),
            'exportFormats' => ErpExportSchema::formats('sales-orders'),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', SalesOrder::class);

        $customers  = Customer::where('status', 'active')->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();

        return view('erp::admin.sales-orders.create', compact('customers', 'warehouses', 'products'));
    }

    public function store(StoreSalesOrderRequest $request)
    {
        $data = $request->validated();

        $order = DB::transaction(function () use ($data): SalesOrder {
            $order = SalesOrder::create([
                'so_number'               => $this->service->generateSoNumber(),
                'customer_id'             => $data['customer_id'],
                'warehouse_id'            => $data['warehouse_id'],
                'order_date'              => $data['order_date'],
                'requested_delivery_date' => $data['requested_delivery_date'] ?? null,
                'discount_amount'         => $data['discount_amount'] ?? 0,
                'notes'                   => $data['notes'] ?? null,
                'status'                  => 'draft',
                'created_by'              => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $base      = (float) $item['unit_price'] * (float) $item['quantity'];
                $discounted= $base * (1 - ((float) ($item['discount_rate'] ?? 0)) / 100);
                $tax       = $discounted * ((float) ($item['tax_rate'] ?? 20)) / 100;

                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id'     => $item['product_id'],
                    'quantity'       => $item['quantity'],
                    'unit_price'     => $item['unit_price'],
                    'tax_rate'       => $item['tax_rate'] ?? 20,
                    'discount_rate'  => $item['discount_rate'] ?? 0,
                    'line_total'     => $discounted + $tax,
                ]);
            }

            $order->load('items');
            $this->service->recalculateTotals($order);

            return $order;
        });

        return redirect()->route('erp.sales-orders.show', $order)
            ->with('erp_status', __('Satış siparişi oluşturuldu.'));
    }

    public function show(SalesOrder $salesOrder)
    {
        Gate::authorize('view', $salesOrder);

        $salesOrder->load(['customer', 'warehouse', 'items.product', 'createdBy']);

        return view('erp::admin.sales-orders.show', compact('salesOrder'));
    }

    public function destroy(SalesOrder $salesOrder): RedirectResponse
    {
        Gate::authorize('delete', $salesOrder);

        $salesOrder->delete();

        return redirect()->route('erp.sales-orders.index')
            ->with('erp_status', __('Sipariş silindi.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('erp.sales-orders.delete');

        $validated = $request->validate([
            'record_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:erp_sales_orders,id'],
        ]);

        $deleted = 0;
        SalesOrder::query()
            ->whereKey($validated['record_ids'])
            ->chunkById(200, function ($orders) use (&$deleted): void {
                foreach ($orders as $order) {
                    $order->delete();
                    $deleted++;
                }
            });

        return back()->with('erp_status', trans_choice(
            '{0} Hiçbiri silinemedi.|{1} :count sipariş silindi.|[2,*] :count sipariş silindi.',
            $deleted, ['count' => $deleted]
        ));
    }

    public function confirm(SalesOrder $salesOrder)
    {
        Gate::authorize('confirm', $salesOrder);

        $this->service->confirmOrder($salesOrder);

        return redirect()->route('erp.sales-orders.show', $salesOrder)
            ->with('erp_status', __('Sipariş onaylandı, stok rezerve edildi.'));
    }

    public function deliver(SalesOrder $salesOrder)
    {
        Gate::authorize('deliver', $salesOrder);

        $this->service->deliverOrder($salesOrder);

        return redirect()->route('erp.sales-orders.show', $salesOrder)
            ->with('erp_status', __('Sipariş teslim edildi, stok güncellendi.'));
    }

    public function cancel(SalesOrder $salesOrder)
    {
        Gate::authorize('cancel', $salesOrder);

        $this->service->cancelOrder($salesOrder);

        return redirect()->route('erp.sales-orders.show', $salesOrder)
            ->with('erp_status', __('Sipariş iptal edildi.'));
    }

    public function createInvoice(SalesOrder $salesOrder)
    {
        Gate::authorize('createInvoice', $salesOrder);

        abort_if($salesOrder->status !== 'delivered', 422, __('Sadece teslim edilmiş siparişler için fatura oluşturulabilir.'));

        $invoice = $this->service->createInvoice($salesOrder);

        return redirect()->route('erp.invoices.show', $invoice)
            ->with('erp_status', __('Fatura oluşturuldu.'));
    }
}
