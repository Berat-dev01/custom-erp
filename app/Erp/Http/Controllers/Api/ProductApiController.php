<?php

namespace App\Erp\Http\Controllers\Api;

use App\Erp\Http\Resources\ProductResource;
use App\Erp\Http\Resources\StockLevelResource;
use App\Erp\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ProductApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('erp.products.view');

        $perPage = min((int) $request->get('per_page', config('erp.api.default_per_page', 20)), config('erp.api.max_per_page', 100));

        $query = Product::with(['category', 'unit', 'stockLevels'])
            ->when($request->get('category_id'), fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->get('type'),        fn ($q, $v) => $q->where('type', $v))
            ->when(! $request->boolean('include_inactive'), fn ($q) => $q->where('is_active', true))
            ->when($request->get('search'), fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('name', 'like', "%{$v}%")
                  ->orWhere('sku', 'like', "%{$v}%")
                  ->orWhere('barcode', 'like', "%{$v}%");
            }))
            ->orderBy('name');

        return ProductResource::collection($query->paginate($perPage));
    }

    public function show(Product $product): ProductResource
    {
        Gate::authorize('erp.products.view');

        $product->loadMissing(['category', 'unit', 'stockLevels']);

        return new ProductResource($product);
    }

    public function stock(Product $product): AnonymousResourceCollection
    {
        Gate::authorize('erp.products.view');
        Gate::authorize('erp.stock_movements.view');

        $product->loadMissing(['stockLevels.warehouse']);

        return StockLevelResource::collection($product->stockLevels);
    }
}
