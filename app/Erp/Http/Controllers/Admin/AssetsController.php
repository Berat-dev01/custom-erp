<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreAssetRequest;
use App\Erp\Http\Requests\UpdateAssetRequest;
use App\Erp\Models\Asset;
use App\Erp\Models\AssetCategory;
use App\Erp\Models\Employee;
use App\Erp\Models\Warehouse;
use App\Erp\Services\Assets\DepreciationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class AssetsController extends Controller
{
    public function __construct(private readonly DepreciationService $depreciationService) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Asset::class);

        $query = Asset::query()->with(['category', 'assignedTo']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $assets     = $query->latest()->paginate(20)->withQueryString();
        $categories = AssetCategory::orderBy('name')->get();

        return view('erp::admin.assets.index', compact('assets', 'categories'));
    }

    public function create()
    {
        Gate::authorize('create', Asset::class);

        $categories = AssetCategory::orderBy('name')->get();
        $employees  = Employee::where('status', 'active')->orderBy('first_name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('erp::admin.assets.create', compact('categories', 'employees', 'warehouses'));
    }

    public function store(StoreAssetRequest $request)
    {
        $data = $request->validated();
        $data['current_value'] = $data['current_value'] ?? $data['purchase_price'];

        Asset::create($data);

        return redirect()->route('erp.assets.index')
            ->with('success', __('Varlık eklendi.'));
    }

    public function show(Asset $asset)
    {
        Gate::authorize('view', $asset);

        $asset->load(['category', 'assignedTo', 'location']);

        $depreciationHistory = $asset->depreciationEntries()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('erp::admin.assets.show', compact('asset', 'depreciationHistory'));
    }

    public function edit(Asset $asset)
    {
        Gate::authorize('update', $asset);

        $categories = AssetCategory::orderBy('name')->get();
        $employees  = Employee::where('status', 'active')->orderBy('first_name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('erp::admin.assets.edit', compact('asset', 'categories', 'employees', 'warehouses'));
    }

    public function update(UpdateAssetRequest $request, Asset $asset)
    {
        $asset->update($request->validated());

        return redirect()->route('erp.assets.show', $asset)
            ->with('success', __('Varlık güncellendi.'));
    }

    public function destroy(Asset $asset)
    {
        Gate::authorize('delete', $asset);

        $asset->delete();

        return redirect()->route('erp.assets.index')
            ->with('success', __('Varlık silindi.'));
    }

    public function depreciate(Asset $asset)
    {
        Gate::authorize('update', $asset);

        $entry = $this->depreciationService->depreciateAsset($asset, now()->year, now()->month);

        if (! $entry) {
            return back()->with('error', __('Bu varlık için amortisman hesaplanamadı (defter değeri 0 veya zaten işlendi).'));
        }

        return back()->with('success', __('Amortisman kaydedildi: :amount', ['amount' => number_format($entry->amount, 2) . ' TL']));
    }
}
