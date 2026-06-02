<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Bom;
use App\Erp\Models\Warehouse;
use App\Erp\Models\WorkOrder;
use App\Erp\Services\Manufacturing\ManufacturingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class WorkOrdersController extends Controller
{
    public function __construct(private ManufacturingService $service) {}

    public function index(Request $request)
    {
        Gate::authorize('erp.manufacturing.view');

        $query = WorkOrder::with(['product', 'warehouse', 'bom'])
            ->when($request->get('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest();

        $orders = $query->paginate(20)->withQueryString();

        return view('erp::admin.work-orders.index', compact('orders'));
    }

    public function create()
    {
        Gate::authorize('erp.manufacturing.manage');

        $boms       = Bom::where('is_active', true)->with('product')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('erp::admin.work-orders.create', compact('boms', 'warehouses'));
    }

    public function store(Request $request)
    {
        Gate::authorize('erp.manufacturing.manage');

        $data = $request->validate([
            'bom_id'           => ['required', 'exists:erp_boms,id'],
            'warehouse_id'     => ['required', 'exists:erp_warehouses,id'],
            'planned_quantity' => ['required', 'numeric', 'min:0.001'],
            'planned_start'    => ['required', 'date'],
            'planned_end'      => ['required', 'date', 'after_or_equal:planned_start'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $bom = Bom::findOrFail($data['bom_id']);

        WorkOrder::create([
            ...$data,
            'wo_number'  => $this->service->generateWoNumber(),
            'product_id' => $bom->product_id,
            'status'     => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('erp.work-orders.index')->with('success', __('İş emri oluşturuldu.'));
    }

    public function show(WorkOrder $workOrder)
    {
        Gate::authorize('erp.manufacturing.view');

        $workOrder->loadMissing(['bom.components.component.unit', 'product', 'warehouse', 'consumptions.product']);

        return view('erp::admin.work-orders.show', compact('workOrder'));
    }

    public function release(WorkOrder $workOrder)
    {
        Gate::authorize('erp.manufacturing.manage');

        $this->service->releaseWorkOrder($workOrder);

        return back()->with('success', __('İş emri serbest bırakıldı, hammaddeler rezerve edildi.'));
    }

    public function complete(Request $request, WorkOrder $workOrder)
    {
        Gate::authorize('erp.manufacturing.manage');

        $data = $request->validate([
            'produced_quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        $this->service->completeWorkOrder($workOrder, (float) $data['produced_quantity']);

        return back()->with('success', __('İş emri tamamlandı, stok güncellendi.'));
    }

    public function cancel(WorkOrder $workOrder)
    {
        Gate::authorize('erp.manufacturing.manage');

        abort_if($workOrder->status === 'completed', 422, __('Tamamlanmış iş emri iptal edilemez.'));

        $workOrder->update(['status' => 'cancelled']);

        return back()->with('success', __('İş emri iptal edildi.'));
    }

    public function destroy(WorkOrder $workOrder)
    {
        Gate::authorize('erp.manufacturing.manage');

        abort_if(! $workOrder->isDraft(), 422, __('Sadece taslak iş emirleri silinebilir.'));

        $workOrder->delete();

        return redirect()->route('erp.work-orders.index')->with('success', __('İş emri silindi.'));
    }
}
