<?php

namespace App\Erp\Services\Manufacturing;

use App\Erp\Models\Bom;
use App\Erp\Models\StockLevel;
use App\Erp\Models\WorkOrder;
use App\Erp\Models\WorkOrderConsumption;
use App\Erp\Services\Inventory\StockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ManufacturingService
{
    public function __construct(private StockService $stockService) {}

    public function generateWoNumber(): string
    {
        $year = now()->year;
        $last = WorkOrder::withTrashed()->where('wo_number', 'like', "WO-{$year}-%")->count();

        return sprintf('WO-%d-%05d', $year, $last + 1);
    }

    /**
     * İş emrini serbest bırak: hammaddeleri rezerve et, consumption kayıtlarını oluştur.
     */
    public function releaseWorkOrder(WorkOrder $wo): void
    {
        abort_if(! $wo->isDraft(), 422, __('Sadece taslak iş emirleri serbest bırakılabilir.'));

        DB::transaction(function () use ($wo): void {
            $wo->load('bom.components');

            $scale = (float) $wo->planned_quantity / max((float) $wo->bom->quantity, 0.001);

            foreach ($wo->bom->components as $component) {
                $needed = round((float) $component->quantity * $scale, 3);

                WorkOrderConsumption::create([
                    'work_order_id'   => $wo->id,
                    'product_id'      => $component->component_id,
                    'planned_quantity'=> $needed,
                    'actual_quantity' => 0,
                ]);

                $level = StockLevel::firstOrCreate(
                    ['product_id' => $component->component_id, 'warehouse_id' => $wo->warehouse_id],
                    ['quantity' => 0, 'reserved_quantity' => 0]
                );
                $level->increment('reserved_quantity', $needed);
            }

            $wo->update([
                'status'       => 'released',
                'actual_start' => Carbon::today(),
            ]);
        });
    }

    /**
     * Üretimi tamamla: hammaddeleri tüket, mamulü stoğa ekle.
     */
    public function completeWorkOrder(WorkOrder $wo, float $producedQuantity): void
    {
        abort_if(! $wo->isActive(), 422, __('Bu iş emri tamamlanamaz.'));
        abort_if($producedQuantity <= 0, 422, __('Üretilen miktar sıfırdan büyük olmalıdır.'));

        DB::transaction(function () use ($wo, $producedQuantity): void {
            $wo->load('consumptions');

            $scale = $producedQuantity / max((float) $wo->planned_quantity, 0.001);

            foreach ($wo->consumptions as $consumption) {
                $actualQty = round((float) $consumption->planned_quantity * $scale, 3);

                $this->stockService->recordMovement([
                    'product_id'     => $consumption->product_id,
                    'warehouse_id'   => $wo->warehouse_id,
                    'type'           => 'out',
                    'quantity'       => $actualQty,
                    'reference_type' => 'erp_work_order',
                    'reference_id'   => $wo->id,
                    'notes'          => "WO: {$wo->wo_number}",
                    'created_by'     => auth()->id(),
                ]);

                $level = StockLevel::where('product_id', $consumption->product_id)
                    ->where('warehouse_id', $wo->warehouse_id)
                    ->first();

                if ($level) {
                    $level->decrement('reserved_quantity', min(
                        (float) $consumption->planned_quantity,
                        (float) $level->reserved_quantity
                    ));
                }

                $consumption->update(['actual_quantity' => $actualQty]);
            }

            // Mamulü stoğa ekle
            $this->stockService->recordMovement([
                'product_id'     => $wo->product_id,
                'warehouse_id'   => $wo->warehouse_id,
                'type'           => 'in',
                'quantity'       => $producedQuantity,
                'reference_type' => 'erp_work_order',
                'reference_id'   => $wo->id,
                'notes'          => "Üretim: {$wo->wo_number}",
                'created_by'     => auth()->id(),
            ]);

            $wo->update([
                'status'            => 'completed',
                'produced_quantity' => $producedQuantity,
                'actual_end'        => Carbon::today(),
            ]);
        });
    }

    /**
     * BOM maliyet hesabı (bileşen satın alma fiyatları toplamı).
     */
    public function calculateBomCost(Bom $bom): float
    {
        $bom->loadMissing('components.component');

        return (float) $bom->components->sum(
            fn ($c) => (float) $c->quantity * (float) ($c->component?->purchase_price ?? 0)
        );
    }
}
