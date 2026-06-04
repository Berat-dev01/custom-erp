<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreStockMovementRequest;
use App\Erp\Models\Product;
use App\Erp\Models\StockMovement;
use App\Erp\Models\Warehouse;
use App\Erp\Services\Inventory\StockService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class StockMovementsController extends Controller
{
    public function __construct(private readonly StockService $stockService) {}

    public function index(Request $request)
    {
        Gate::authorize('erp.stock_movements.view');

        $query = StockMovement::query()->with(['product', 'warehouse', 'createdBy']);

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $movements  = $query->latest()->paginate(20)->withQueryString();
        $products   = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('erp::admin.stock-movements.index', compact('movements', 'products', 'warehouses'));
    }

    public function create()
    {
        Gate::authorize('erp.stock_movements.create');

        $products   = Product::where('is_active', true)->where('track_stock', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('erp::admin.stock-movements.create', compact('products', 'warehouses'));
    }

    public function store(StoreStockMovementRequest $request)
    {
        $data               = $request->validated();
        $data['created_by'] = auth()->id();

        $this->stockService->recordMovement($data);

        return redirect()->route('erp.stock-movements.index')
            ->with('success', __('Stok hareketi kaydedildi.'));
    }
}
