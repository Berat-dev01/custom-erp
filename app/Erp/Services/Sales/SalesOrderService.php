<?php

namespace App\Erp\Services\Sales;

use App\Erp\Models\Invoice;
use App\Erp\Models\InvoiceItem;
use App\Erp\Models\SalesOrder;
use App\Erp\Models\StockLevel;
use App\Erp\Services\Finance\InvoiceService;
use App\Erp\Services\Inventory\StockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly InvoiceService $invoiceService,
    ) {}

    public function generateSoNumber(): string
    {
        $year = now()->year;
        $last = SalesOrder::withTrashed()
            ->where('so_number', 'like', "SO-{$year}-%")
            ->count();

        return sprintf('SO-%d-%05d', $year, $last + 1);
    }

    public function confirmOrder(SalesOrder $order): void
    {
        abort_if(! $order->isDraft(), 422, __('Sadece taslak siparişler onaylanabilir.'));

        DB::transaction(function () use ($order): void {
            $order->load('items');

            foreach ($order->items as $item) {
                $level = StockLevel::firstOrCreate(
                    ['product_id' => $item->product_id, 'warehouse_id' => $order->warehouse_id],
                    ['quantity' => 0, 'reserved_quantity' => 0]
                );

                $level->increment('reserved_quantity', (float) $item->quantity);
            }

            $order->update(['status' => 'confirmed']);
        });
    }

    public function deliverOrder(SalesOrder $order): void
    {
        abort_if(! $order->canBeDelivered(), 422, __('Bu sipariş teslim edilebilir durumda değil.'));

        DB::transaction(function () use ($order): void {
            $order->load('items');

            foreach ($order->items as $item) {
                $this->stockService->recordMovement([
                    'product_id'     => $item->product_id,
                    'warehouse_id'   => $order->warehouse_id,
                    'type'           => 'out',
                    'quantity'       => (float) $item->quantity,
                    'reference_type' => 'erp_sales_order',
                    'reference_id'   => $order->id,
                    'notes'          => "SO: {$order->so_number}",
                    'created_by'     => auth()->id(),
                ]);

                $level = StockLevel::where('product_id', $item->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->first();

                if ($level) {
                    $level->decrement('reserved_quantity', min(
                        (float) $item->quantity,
                        (float) $level->reserved_quantity
                    ));
                }
            }

            $order->update([
                'status'               => 'delivered',
                'actual_delivery_date' => Carbon::today(),
            ]);
        });
    }

    public function cancelOrder(SalesOrder $order): void
    {
        abort_if(! $order->canBeCancelled(), 422, __('Bu sipariş iptal edilemez.'));

        DB::transaction(function () use ($order): void {
            if ($order->status === 'confirmed') {
                $order->load('items');

                foreach ($order->items as $item) {
                    $level = StockLevel::where('product_id', $item->product_id)
                        ->where('warehouse_id', $order->warehouse_id)
                        ->first();

                    if ($level) {
                        $level->decrement('reserved_quantity', min(
                            (float) $item->quantity,
                            (float) $level->reserved_quantity
                        ));
                    }
                }
            }

            $order->update(['status' => 'cancelled']);
        });
    }

    public function createInvoice(SalesOrder $order): Invoice
    {
        $order->load('items.product', 'customer');

        return DB::transaction(function () use ($order): Invoice {
            $invoice = Invoice::create([
                'invoice_number'   => $this->invoiceService->generateInvoiceNumber(),
                'type'             => 'sale',
                'invoiceable_type' => 'erp_customer',
                'invoiceable_id'   => $order->customer_id,
                'issue_date'       => Carbon::today(),
                'due_date'         => Carbon::today()->addDays(
                    $order->customer->payment_terms_days ?? 30
                ),
                'reference'        => $order->so_number,
                'status'           => 'draft',
                'created_by'       => auth()->id(),
            ]);

            foreach ($order->items as $item) {
                $base      = (float) $item->unit_price * (float) $item->quantity;
                $discounted= $base * (1 - (float) $item->discount_rate / 100);
                $tax       = $discounted * (float) $item->tax_rate / 100;

                InvoiceItem::create([
                    'invoice_id'    => $invoice->id,
                    'product_id'    => $item->product_id,
                    'description'   => $item->product?->name ?? __('Ürün'),
                    'quantity'      => $item->quantity,
                    'unit_price'    => $item->unit_price,
                    'tax_rate'      => $item->tax_rate,
                    'discount_rate' => $item->discount_rate,
                    'line_total'    => $discounted + $tax,
                ]);
            }

            $invoice->load('items');
            $this->invoiceService->recalculateTotals($invoice);

            return $invoice;
        });
    }

    public function recalculateTotals(SalesOrder $order): void
    {
        $subtotal  = 0.0;
        $taxAmount = 0.0;

        foreach ($order->items as $item) {
            $base      = (float) $item->unit_price * (float) $item->quantity;
            $discounted= $base * (1 - (float) $item->discount_rate / 100);
            $tax       = $discounted * (float) $item->tax_rate / 100;
            $lineTotal = $discounted + $tax;

            $item->update(['line_total' => $lineTotal]);

            $subtotal  += $discounted;
            $taxAmount += $tax;
        }

        $discount = (float) $order->discount_amount;

        $order->update([
            'subtotal'   => $subtotal,
            'tax_amount' => $taxAmount,
            'total'      => $subtotal - $discount + $taxAmount,
        ]);
    }
}
