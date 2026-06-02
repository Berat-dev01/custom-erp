<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreSalesOrderRequest;
use App\Erp\Models\Customer;
use App\Erp\Models\Product;
use App\Erp\Models\SalesOrder;
use App\Erp\Models\SalesOrderItem;
use App\Erp\Models\Warehouse;
use App\Erp\Services\Sales\SalesOrderService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SalesOrdersController extends Controller
{
    public function __construct(private readonly SalesOrderService $service) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', SalesOrder::class);

        $query = SalesOrder::query()->with(['customer', 'warehouse']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($customerId = $request->input('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('order_date', '>=', $from);
        }

        $orders    = $query->latest()->paginate(20)->withQueryString();
        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('erp::admin.sales-orders.index', compact('orders', 'customers'));
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
            ->with('success', __('Satış siparişi oluşturuldu.'));
    }

    public function show(SalesOrder $salesOrder)
    {
        Gate::authorize('view', $salesOrder);

        $salesOrder->load(['customer', 'warehouse', 'items.product', 'createdBy']);

        return view('erp::admin.sales-orders.show', compact('salesOrder'));
    }

    public function destroy(SalesOrder $salesOrder)
    {
        Gate::authorize('delete', $salesOrder);

        $salesOrder->delete();

        return redirect()->route('erp.sales-orders.index')
            ->with('success', __('Sipariş silindi.'));
    }

    public function confirm(SalesOrder $salesOrder)
    {
        Gate::authorize('confirm', $salesOrder);

        $this->service->confirmOrder($salesOrder);

        return redirect()->route('erp.sales-orders.show', $salesOrder)
            ->with('success', __('Sipariş onaylandı, stok rezerve edildi.'));
    }

    public function deliver(SalesOrder $salesOrder)
    {
        Gate::authorize('deliver', $salesOrder);

        $this->service->deliverOrder($salesOrder);

        return redirect()->route('erp.sales-orders.show', $salesOrder)
            ->with('success', __('Sipariş teslim edildi, stok güncellendi.'));
    }

    public function cancel(SalesOrder $salesOrder)
    {
        Gate::authorize('cancel', $salesOrder);

        $this->service->cancelOrder($salesOrder);

        return redirect()->route('erp.sales-orders.show', $salesOrder)
            ->with('success', __('Sipariş iptal edildi.'));
    }

    public function createInvoice(SalesOrder $salesOrder)
    {
        Gate::authorize('createInvoice', $salesOrder);

        abort_if($salesOrder->status !== 'delivered', 422, __('Sadece teslim edilmiş siparişler için fatura oluşturulabilir.'));

        $invoice = $this->service->createInvoice($salesOrder);

        return redirect()->route('erp.invoices.show', $invoice)
            ->with('success', __('Fatura oluşturuldu.'));
    }
}
