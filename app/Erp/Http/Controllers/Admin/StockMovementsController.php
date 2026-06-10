<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreStockMovementRequest;
use App\Erp\Models\Product;
use App\Erp\Models\StockMovement;
use App\Erp\Models\Warehouse;
use App\Erp\Services\Inventory\StockService;
use App\Erp\Services\Manufacturing\StockMovementQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class StockMovementsController extends Controller
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly StockMovementQuery $query,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('erp.stock_movements.view');

        return view('erp::admin.stock-movements.index', [
            'movements'  => $this->query->paginate($request),
            'filters'    => $this->query->filters($request),
            'products'   => Product::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'warehouses' => Warehouse::query()->orderBy('name')->pluck('name', 'id'),
        ]);
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
            ->with('erp_status', __('Stok hareketi kaydedildi.'));
    }
}
