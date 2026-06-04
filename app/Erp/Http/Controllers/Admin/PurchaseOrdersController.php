<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\ReceivePurchaseOrderRequest;
use App\Erp\Http\Requests\StorePurchaseOrderRequest;
use App\Erp\Models\Product;
use App\Erp\Models\PurchaseOrder;
use App\Erp\Models\PurchaseOrderItem;
use App\Erp\Models\Supplier;
use App\Erp\Models\Warehouse;
use App\Erp\Services\Procurement\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PurchaseOrdersController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $service) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', PurchaseOrder::class);

        $query = PurchaseOrder::query()->with(['supplier', 'warehouse']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('order_date', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('order_date', '<=', $to);
        }

        $orders    = $query->latest()->paginate(20)->withQueryString();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        return view('erp::admin.purchase-orders.index', compact('orders', 'suppliers'));
    }

    public function create()
    {
        Gate::authorize('create', PurchaseOrder::class);

        $suppliers  = Supplier::where('status', 'active')->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();

        return view('erp::admin.purchase-orders.create', compact('suppliers', 'warehouses', 'products'));
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        $data = $request->validated();

        $po = DB::transaction(function () use ($data): PurchaseOrder {
            $po = PurchaseOrder::create([
                'po_number'    => $this->service->generatePoNumber(),
                'supplier_id'  => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'order_date'   => $data['order_date'],
                'expected_date'=> $data['expected_date'] ?? null,
                'currency'     => $data['currency'] ?? config('erp.currency', 'TRY'),
                'notes'        => $data['notes'] ?? null,
                'status'       => 'draft',
                'created_by'   => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $base      = (float) $item['unit_price'] * (float) $item['quantity'];
                $discounted= $base * (1 - ((float) ($item['discount_rate'] ?? 0)) / 100);
                $tax       = $discounted * ((float) ($item['tax_rate'] ?? 20)) / 100;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id'        => $item['product_id'],
                    'quantity'          => $item['quantity'],
                    'unit_price'        => $item['unit_price'],
                    'tax_rate'          => $item['tax_rate'] ?? 20,
                    'discount_rate'     => $item['discount_rate'] ?? 0,
                    'line_total'        => $discounted + $tax,
                ]);
            }

            $po->load('items');
            $this->service->recalculateTotals($po);

            return $po;
        });

        return redirect()->route('erp.purchase-orders.show', $po)
            ->with('success', __('Satın alma siparişi oluşturuldu.'));
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier', 'warehouse', 'items.product', 'createdBy']);

        return view('erp::admin.purchase-orders.show', compact('purchaseOrder'));
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('delete', $purchaseOrder);

        $purchaseOrder->delete();

        return redirect()->route('erp.purchase-orders.index')
            ->with('success', __('Sipariş silindi.'));
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('approve', $purchaseOrder);

        $this->service->approvePurchaseOrder($purchaseOrder);

        return redirect()->route('erp.purchase-orders.show', $purchaseOrder)
            ->with('success', __('Sipariş gönderildi olarak işaretlendi.'));
    }

    public function receive(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('receive', $purchaseOrder);

        $purchaseOrder->load(['items.product', 'warehouse']);

        return view('erp::admin.purchase-orders.receive', compact('purchaseOrder'));
    }

    public function storeReceiving(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        $this->service->receiveItems($purchaseOrder, $request->validated()['items'] ?? []);

        return redirect()->route('erp.purchase-orders.show', $purchaseOrder)
            ->with('success', __('Teslimat kaydedildi, stok güncellendi.'));
    }
}
