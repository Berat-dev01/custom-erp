<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\ReceivePurchaseOrderRequest;
use App\Erp\Http\Requests\StorePurchaseOrderRequest;
use App\Erp\Models\Product;
use App\Erp\Models\PurchaseOrder;
use App\Erp\Models\PurchaseOrderItem;
use App\Erp\Models\Supplier;
use App\Erp\Models\Warehouse;
use App\Erp\Services\Procurement\PurchaseOrderQuery;
use App\Erp\Services\Procurement\PurchaseOrderService;
use App\Erp\Support\ErpExportSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PurchaseOrdersController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderService $service,
        private readonly PurchaseOrderQuery $query,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', PurchaseOrder::class);

        return view('erp::admin.purchase-orders.index', [
            'orders'        => $this->query->paginate($request),
            'filters'       => $this->query->filters($request),
            'suppliers'     => Supplier::query()->where('status', 'active')->orderBy('name')->pluck('name', 'id'),
            'exportColumns' => ErpExportSchema::columns('purchase-orders'),
            'exportFormats' => ErpExportSchema::formats('purchase-orders'),
        ]);
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
            ->with('erp_status', __('Satın alma siparişi oluşturuldu.'));
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
            ->with('erp_status', __('Sipariş silindi.'));
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('approve', $purchaseOrder);

        $this->service->approvePurchaseOrder($purchaseOrder);

        return redirect()->route('erp.purchase-orders.show', $purchaseOrder)
            ->with('erp_status', __('Sipariş gönderildi olarak işaretlendi.'));
    }

    public function receive(PurchaseOrder $purchaseOrder)
    {
        Gate::authorize('receive', $purchaseOrder);

        $purchaseOrder->load(['items.product', 'warehouse']);

        return view('erp::admin.purchase-orders.receive', compact('purchaseOrder'));
    }

    public function storeReceiving(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->receiveItems($purchaseOrder, $request->validated()['items'] ?? []);

        return redirect()->route('erp.purchase-orders.show', $purchaseOrder)
            ->with('erp_status', __('Teslimat kaydedildi, stok güncellendi.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('erp.purchase-orders.delete');

        $validated = $request->validate([
            'record_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:erp_purchase_orders,id'],
        ]);

        $deleted = 0;
        PurchaseOrder::query()
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
}
