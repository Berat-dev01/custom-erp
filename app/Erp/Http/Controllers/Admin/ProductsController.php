<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreProductRequest;
use App\Erp\Http\Requests\UpdateProductRequest;
use App\Erp\Models\Product;
use App\Erp\Models\ProductCategory;
use App\Erp\Models\Unit;
use App\Erp\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Product::class);

        $query = Product::query()->with(['category', 'unit']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($request->input('low_stock')) {
            $query->where('track_stock', true)
                  ->where('reorder_point', '>', 0)
                  ->whereHas('stockLevels', fn ($q) => $q->whereColumn('quantity', '<=', 'erp_products.reorder_point'));
        }

        $products   = $query->latest()->paginate(20)->withQueryString();
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        return view('erp::admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        Gate::authorize('create', Product::class);

        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();

        return view('erp::admin.products.create', compact('categories', 'units'));
    }

    public function store(StoreProductRequest $request)
    {
        Product::create($request->validated());

        return redirect()->route('erp.products.index')
            ->with('success', __('Ürün eklendi.'));
    }

    public function show(Product $product)
    {
        Gate::authorize('view', $product);

        $product->load(['category', 'unit', 'stockLevels.warehouse']);
        $recentMovements = $product->movements()
            ->with(['warehouse', 'createdBy'])
            ->latest()
            ->limit(20)
            ->get();

        return view('erp::admin.products.show', compact('product', 'recentMovements'));
    }

    public function edit(Product $product)
    {
        Gate::authorize('update', $product);

        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();

        return view('erp::admin.products.edit', compact('product', 'categories', 'units'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return redirect()->route('erp.products.show', $product)
            ->with('success', __('Ürün güncellendi.'));
    }

    public function destroy(Product $product)
    {
        Gate::authorize('delete', $product);

        $product->delete();

        return redirect()->route('erp.products.index')
            ->with('success', __('Ürün silindi.'));
    }
}
