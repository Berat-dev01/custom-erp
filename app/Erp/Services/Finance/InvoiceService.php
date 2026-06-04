<?php

namespace App\Erp\Services\Finance;

use App\Erp\Models\Invoice;
use App\Erp\Models\Payment;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class InvoiceService
{
    public function generateInvoiceNumber(?string $prefix = null): string
    {
        $prefix = $prefix ?? config('erp.invoice_prefix', 'INV');
        $year   = now()->year;
        $last   = Invoice::withTrashed()
            ->where('invoice_number', 'like', "{$prefix}-{$year}-%")
            ->count();

        return sprintf('%s-%d-%05d', $prefix, $year, $last + 1);
    }

    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data): Payment {
            $payment = Payment::create([
                'invoice_id'   => $invoice->id,
                'amount'       => $data['amount'],
                'payment_date' => $data['payment_date'],
                'method'       => $data['method'],
                'reference'    => $data['reference'] ?? null,
                'notes'        => $data['notes'] ?? null,
                'created_by'   => auth()->id(),
            ]);

            $newPaid = (float) $invoice->paid_amount + (float) $data['amount'];
            $total   = (float) $invoice->total;

            $status = match (true) {
                $newPaid >= $total                   => 'paid',
                $newPaid > 0 && $newPaid < $total    => 'partial',
                default                              => $invoice->status,
            };

            $invoice->update([
                'paid_amount' => $newPaid,
                'status'      => $status,
            ]);

            app(\App\Erp\Services\Accounting\AccountingService::class)
                ->postPaymentReceived($payment->load('invoice'));

            return $payment;
        });
    }

    public function markOverdueInvoices(): int
    {
        return Invoice::where('status', 'sent')
            ->where('due_date', '<', Carbon::today())
            ->update(['status' => 'overdue']);
    }

    public function recalculateTotals(Invoice $invoice): void
    {
        $subtotal  = 0.0;
        $taxAmount = 0.0;

        foreach ($invoice->items as $item) {
            $base       = (float) $item->unit_price * (float) $item->quantity;
            $discounted = $base * (1 - (float) $item->discount_rate / 100);
            $tax        = $discounted * (float) $item->tax_rate / 100;
            $lineTotal  = $discounted + $tax;

            $item->update(['line_total' => $lineTotal]);

            $subtotal  += $discounted;
            $taxAmount += $tax;
        }

        $discount = (float) $invoice->discount_amount;

        $invoice->update([
            'subtotal'   => $subtotal,
            'tax_amount' => $taxAmount,
            'total'      => $subtotal - $discount + $taxAmount,
        ]);
    }

    public function generatePdf(Invoice $invoice): string
    {
        try {
            $invoice->loadMissing(['items.product', 'invoiceable', 'createdBy']);

            $html = View::make('erp::admin.invoices.pdf', compact('invoice'))->render();

            $options = new Options();
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return $dompdf->output();
        } catch (\Throwable $e) {
            Log::error('Invoice PDF generation failed', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function revenueThisMonth(): float
    {
        return (float) Invoice::where('type', 'sale')
            ->whereIn('status', ['paid', 'partial', 'sent', 'overdue'])
            ->whereMonth('issue_date', now()->month)
            ->whereYear('issue_date', now()->year)
            ->sum('total');
    }

    public function revenueLastMonth(): float
    {
        return (float) Invoice::where('type', 'sale')
            ->whereIn('status', ['paid', 'partial', 'sent', 'overdue'])
            ->whereMonth('issue_date', now()->subMonth()->month)
            ->whereYear('issue_date', now()->subMonth()->year)
            ->sum('total');
    }

    public function outstandingTotal(): float
    {
        return (float) Invoice::whereIn('status', ['sent', 'partial', 'overdue'])
            ->selectRaw('SUM(total - paid_amount) as outstanding')
            ->value('outstanding') ?? 0;
    }

    public function overdueTotal(): float
    {
        return (float) Invoice::where('status', 'overdue')
            ->selectRaw('SUM(total - paid_amount) as outstanding')
            ->value('outstanding') ?? 0;
    }
}
