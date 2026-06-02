<?php

namespace App\Erp\Services\Procurement;

use App\Erp\Models\PurchaseOrder;
use App\Erp\Services\Inventory\StockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(private readonly StockService $stockService) {}

    public function generatePoNumber(): string
    {
        $year = now()->year;
        $last = PurchaseOrder::withTrashed()
            ->where('po_number', 'like', "PO-{$year}-%")
            ->count();

        return sprintf('PO-%d-%05d', $year, $last + 1);
    }

    public function approvePurchaseOrder(PurchaseOrder $po): void
    {
        abort_if(! $po->isDraft(), 422, __('Sadece taslak siparişler gönderilebilir.'));

        $po->update(['status' => 'sent']);
    }

    public function receiveItems(PurchaseOrder $po, array $receivedItems): void
    {
        abort_if($po->status === 'cancelled', 422, __('İptal edilmiş sipariş için teslimat yapılamaz.'));
        abort_if($po->status === 'received', 422, __('Bu sipariş zaten teslim alındı.'));

        DB::transaction(function () use ($po, $receivedItems): void {
            foreach ($receivedItems as $itemId => $receivedQty) {
                $receivedQty = (float) $receivedQty;

                if ($receivedQty <= 0) {
                    continue;
                }

                $item = $po->items()->findOrFail($itemId);
                $canReceive = (float) $item->quantity - (float) $item->received_quantity;

                if ($receivedQty > $canReceive) {
                    $receivedQty = $canReceive;
                }

                if ($receivedQty <= 0) {
                    continue;
                }

                $item->increment('received_quantity', $receivedQty);

                $this->stockService->recordMovement([
                    'product_id'     => $item->product_id,
                    'warehouse_id'   => $po->warehouse_id,
                    'type'           => 'in',
                    'quantity'       => $receivedQty,
                    'unit_cost'      => $item->unit_price,
                    'reference_type' => 'erp_purchase_order',
                    'reference_id'   => $po->id,
                    'notes'          => "PO: {$po->po_number}",
                    'created_by'     => auth()->id(),
                ]);
            }

            $po->load('items');

            $newStatus = $po->isFullyReceived() ? 'received' : 'partial';

            $po->update([
                'status'        => $newStatus,
                'received_date' => $newStatus === 'received' ? Carbon::today() : null,
            ]);

            if ($newStatus === 'received') {
                app(\App\Erp\Services\Accounting\AccountingService::class)->postPurchaseInvoice($po);
            }
        });
    }

    public function recalculateTotals(PurchaseOrder $po): void
    {
        $subtotal  = 0;
        $taxAmount = 0;

        foreach ($po->items as $item) {
            $base       = (float) $item->unit_price * (float) $item->quantity;
            $discounted = $base * (1 - (float) $item->discount_rate / 100);
            $tax        = $discounted * ((float) $item->tax_rate / 100);

            $lineTotal  = $discounted + $tax;

            $item->update(['line_total' => $lineTotal]);

            $subtotal  += $discounted;
            $taxAmount += $tax;
        }

        $po->update([
            'subtotal'   => $subtotal,
            'tax_amount' => $taxAmount,
            'total'      => $subtotal + $taxAmount,
        ]);
    }
}
