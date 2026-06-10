<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreAssetRequest;
use App\Erp\Http\Requests\UpdateAssetRequest;
use App\Erp\Models\Asset;
use App\Erp\Models\AssetCategory;
use App\Erp\Models\Employee;
use App\Erp\Models\Warehouse;
use App\Erp\Services\Assets\AssetQuery;
use App\Erp\Services\Assets\DepreciationService;
use App\Erp\Support\ErpExportSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class AssetsController extends Controller
{
    public function __construct(
        private readonly AssetQuery $query,
        private readonly DepreciationService $depreciationService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Asset::class);

        return view('erp::admin.assets.index', [
            'assets'        => $this->query->paginate($request),
            'filters'       => $this->query->filters($request),
            'categories'    => AssetCategory::query()->orderBy('name')->pluck('name', 'id'),
            'exportColumns' => ErpExportSchema::columns('assets'),
            'exportFormats' => ErpExportSchema::formats('assets'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Asset::class);

        return view('erp::admin.assets.form', $this->formData(new Asset));
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['current_value'] = $data['current_value'] ?? $data['purchase_price'];

        $asset = Asset::create($data);

        return redirect()
            ->route('erp.assets.show', $asset)
            ->with('erp_status', __('Varlık eklendi.'));
    }

    public function show(Asset $asset): View
    {
        Gate::authorize('view', $asset);

        $asset->load(['category', 'assignedTo', 'location']);

        $depreciationHistory = $asset->depreciationEntries()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('erp::admin.assets.show', compact('asset', 'depreciationHistory'));
    }

    public function edit(Asset $asset): View
    {
        Gate::authorize('update', $asset);

        return view('erp::admin.assets.form', $this->formData($asset));
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $asset->update($request->validated());

        return redirect()
            ->route('erp.assets.show', $asset)
            ->with('erp_status', __('Varlık güncellendi.'));
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        Gate::authorize('delete', $asset);

        $asset->delete();

        return redirect()
            ->route('erp.assets.index')
            ->with('erp_status', __('Varlık silindi.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('erp.assets.delete');

        $validated = $request->validate([
            'record_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:erp_assets,id'],
        ]);

        $deleted = 0;
        Asset::query()
            ->whereKey($validated['record_ids'])
            ->chunkById(200, function ($assets) use (&$deleted): void {
                foreach ($assets as $asset) {
                    $asset->delete();
                    $deleted++;
                }
            });

        return back()->with('erp_status', trans_choice(
            '{0} Hiçbiri silinemedi.|{1} :count varlık silindi.|[2,*] :count varlık silindi.',
            $deleted, ['count' => $deleted]
        ));
    }

    public function depreciate(Asset $asset): RedirectResponse
    {
        Gate::authorize('update', $asset);

        $entry = $this->depreciationService->depreciateAsset($asset, now()->year, now()->month);

        if (! $entry) {
            return back()->with('erp_status', __('Bu varlık için amortisman hesaplanamadı (defter değeri 0 veya zaten işlendi).'));
        }

        return back()->with('erp_status', __('Amortisman kaydedildi: :amount', ['amount' => number_format($entry->amount, 2).' TL']));
    }

    /** @return array<string, mixed> */
    private function formData(Asset $asset): array
    {
        return [
            'asset'      => $asset,
            'categories' => AssetCategory::query()->orderBy('name')->pluck('name', 'id'),
            'employees'  => Employee::query()->where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'warehouses' => Warehouse::query()->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
