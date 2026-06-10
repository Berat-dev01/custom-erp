<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreProductRequest;
use App\Erp\Http\Requests\UpdateProductRequest;
use App\Erp\Models\Product;
use App\Erp\Models\ProductCategory;
use App\Erp\Models\Unit;
use App\Erp\Models\Warehouse;
use App\Erp\Services\Products\ProductQuery;
use App\Erp\Support\ErpExportSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ProductsController extends Controller
{
    public function __construct(private readonly ProductQuery $products) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        return view('erp::admin.products.index', [
            'products'      => $this->products->paginate($request),
            'filters'       => $this->products->filters($request),
            'categories'    => ProductCategory::query()->orderBy('name')->pluck('name', 'id'),
            'exportColumns' => ErpExportSchema::columns('products'),
            'exportFormats' => ErpExportSchema::formats('products'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Product::class);

        return view('erp::admin.products.form', $this->formData(new Product));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        return redirect()
            ->route('erp.products.show', $product)
            ->with('erp_status', __('Ürün eklendi.'));
    }

    public function show(Product $product): View
    {
        Gate::authorize('view', $product);

        $product->load(['category', 'unit', 'stockLevels.warehouse']);

        return view('erp::admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        return view('erp::admin.products.form', $this->formData($product));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('erp.products.show', $product)
            ->with('erp_status', __('Ürün güncellendi.'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $product->delete();

        return redirect()
            ->route('erp.products.index')
            ->with('erp_status', __('Ürün silindi.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('erp.products.delete');

        $validated = $request->validate([
            'record_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:erp_products,id'],
        ]);

        $deleted = 0;
        Product::query()
            ->whereKey($validated['record_ids'])
            ->chunkById(200, function ($products) use (&$deleted): void {
                foreach ($products as $product) {
                    $product->delete();
                    $deleted++;
                }
            });

        return back()->with('erp_status', trans_choice(
            '{0} Hiçbiri silinemedi.|{1} :count ürün silindi.|[2,*] :count ürün silindi.',
            $deleted, ['count' => $deleted]
        ));
    }

    /** @return array<string, mixed> */
    private function formData(Product $product): array
    {
        return [
            'product'    => $product,
            'categories' => ProductCategory::query()->orderBy('name')->pluck('name', 'id'),
            'units'      => Unit::query()->orderBy('name')->pluck('abbreviation', 'id'),
            'warehouses' => Warehouse::query()->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
