<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Bom;
use App\Erp\Models\BomComponent;
use App\Erp\Models\Product;
use App\Erp\Services\Manufacturing\BomQuery;
use App\Erp\Services\Manufacturing\ManufacturingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BomsController extends Controller
{
    public function __construct(
        private ManufacturingService $service,
        private BomQuery $query,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('erp.manufacturing.view');

        return view('erp::admin.boms.index', [
            'boms'    => $this->query->paginate($request),
            'filters' => $this->query->filters($request),
        ]);
    }

    public function create()
    {
        Gate::authorize('erp.manufacturing.manage');

        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('erp::admin.boms.create', compact('products'));
    }

    public function store(Request $request)
    {
        Gate::authorize('erp.manufacturing.manage');

        $data = $request->validate([
            'product_id'           => ['required', 'exists:erp_products,id'],
            'version'              => ['required', 'string', 'max:10'],
            'quantity'             => ['required', 'numeric', 'min:0.001'],
            'notes'                => ['nullable', 'string', 'max:500'],
            'components'           => ['required', 'array', 'min:1'],
            'components.*.component_id' => ['required', 'exists:erp_products,id', 'different:product_id'],
            'components.*.quantity'     => ['required', 'numeric', 'min:0.001'],
            'components.*.notes'        => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data): void {
            $bom = Bom::create([
                'product_id' => $data['product_id'],
                'version'    => $data['version'],
                'quantity'   => $data['quantity'],
                'notes'      => $data['notes'] ?? null,
                'is_active'  => true,
            ]);

            foreach ($data['components'] as $comp) {
                BomComponent::create([
                    'bom_id'       => $bom->id,
                    'component_id' => $comp['component_id'],
                    'quantity'     => $comp['quantity'],
                    'notes'        => $comp['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('erp.boms.index')->with('erp_status', __('BOM oluşturuldu.'));
    }

    public function show(Bom $bom)
    {
        Gate::authorize('erp.manufacturing.view');

        $bom->loadMissing(['product', 'components.component.unit']);
        $cost = $this->service->calculateBomCost($bom);

        return view('erp::admin.boms.show', compact('bom', 'cost'));
    }

    public function destroy(Bom $bom): RedirectResponse
    {
        Gate::authorize('erp.manufacturing.manage');

        abort_if($bom->workOrders()->whereNotIn('status', ['cancelled'])->exists(), 422, __('Aktif iş emri olan BOM silinemez.'));

        $bom->delete();

        return redirect()->route('erp.boms.index')->with('erp_status', __('BOM silindi.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('erp.manufacturing.manage');

        $validated = $request->validate([
            'record_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:erp_boms,id'],
        ]);

        $deleted  = 0;
        $blocked  = 0;
        Bom::query()
            ->whereKey($validated['record_ids'])
            ->chunkById(200, function ($boms) use (&$deleted, &$blocked): void {
                foreach ($boms as $bom) {
                    if ($bom->workOrders()->whereNotIn('status', ['cancelled'])->exists()) {
                        $blocked++;
                        continue;
                    }
                    $bom->delete();
                    $deleted++;
                }
            });

        $msg = trans_choice('{0} Hiçbiri silinemedi.|{1} :count BOM silindi.|[2,*] :count BOM silindi.', $deleted, ['count' => $deleted]);
        if ($blocked > 0) {
            $msg .= ' '.__(':count tanesi aktif iş emri olduğu için atlandı.', ['count' => $blocked]);
        }

        return back()->with('erp_status', $msg);
    }
}
