<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Bom;
use App\Erp\Models\BomComponent;
use App\Erp\Models\Product;
use App\Erp\Services\Manufacturing\ManufacturingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BomsController extends Controller
{
    public function __construct(private ManufacturingService $service) {}

    public function index()
    {
        Gate::authorize('erp.manufacturing.view');

        $boms = Bom::with('product')->latest()->paginate(20);

        return view('erp::admin.boms.index', compact('boms'));
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

        return redirect()->route('erp.boms.index')->with('success', __('BOM oluşturuldu.'));
    }

    public function show(Bom $bom)
    {
        Gate::authorize('erp.manufacturing.view');

        $bom->loadMissing(['product', 'components.component.unit']);
        $cost = $this->service->calculateBomCost($bom);

        return view('erp::admin.boms.show', compact('bom', 'cost'));
    }

    public function destroy(Bom $bom)
    {
        Gate::authorize('erp.manufacturing.manage');

        abort_if($bom->workOrders()->whereNotIn('status', ['cancelled'])->exists(), 422, __('Aktif iş emri olan BOM silinemez.'));

        $bom->delete();

        return redirect()->route('erp.boms.index')->with('success', __('BOM silindi.'));
    }
}
