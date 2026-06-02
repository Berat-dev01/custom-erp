<?php

namespace App\Erp\Http\Controllers\Api;

use App\Erp\Http\Requests\StoreSalesOrderRequest;
use App\Erp\Http\Resources\SalesOrderResource;
use App\Erp\Models\SalesOrder;
use App\Erp\Services\Sales\SalesOrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class SalesOrderApiController extends Controller
{
    public function __construct(private SalesOrderService $salesOrderService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('erp.sales_orders.view');

        $perPage = min((int) $request->get('per_page', config('erp.api.default_per_page', 20)), config('erp.api.max_per_page', 100));

        $query = SalesOrder::with(['customer', 'warehouse'])
            ->when($request->get('status'),      fn ($q, $v) => $q->where('status', $v))
            ->when($request->get('customer_id'), fn ($q, $v) => $q->where('customer_id', $v))
            ->when($request->get('date_from'),   fn ($q, $v) => $q->where('order_date', '>=', $v))
            ->when($request->get('date_to'),     fn ($q, $v) => $q->where('order_date', '<=', $v))
            ->latest('order_date');

        return SalesOrderResource::collection($query->paginate($perPage));
    }

    public function store(StoreSalesOrderRequest $request): SalesOrderResource
    {
        $order = $this->salesOrderService->createOrder($request->validated(), $request->user());

        $order->loadMissing(['customer', 'warehouse', 'items']);

        return (new SalesOrderResource($order))->additional(['message' => 'Sales order created.']);
    }
}
