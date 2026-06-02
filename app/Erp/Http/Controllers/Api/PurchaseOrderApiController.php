<?php

namespace App\Erp\Http\Controllers\Api;

use App\Erp\Http\Resources\PurchaseOrderResource;
use App\Erp\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class PurchaseOrderApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('erp.purchase_orders.view');

        $perPage = min((int) $request->get('per_page', config('erp.api.default_per_page', 20)), config('erp.api.max_per_page', 100));

        $query = PurchaseOrder::with(['supplier', 'warehouse'])
            ->when($request->get('status'),      fn ($q, $v) => $q->where('status', $v))
            ->when($request->get('supplier_id'), fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($request->get('date_from'),   fn ($q, $v) => $q->where('order_date', '>=', $v))
            ->when($request->get('date_to'),     fn ($q, $v) => $q->where('order_date', '<=', $v))
            ->latest('order_date');

        return PurchaseOrderResource::collection($query->paginate($perPage));
    }
}
