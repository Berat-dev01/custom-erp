<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreInvoiceRequest;
use App\Erp\Http\Requests\StorePaymentRequest;
use App\Erp\Models\Invoice;
use App\Erp\Models\InvoiceItem;
use App\Erp\Models\Product;
use App\Erp\Services\Finance\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InvoicesController extends Controller
{
    public function __construct(private readonly InvoiceService $service) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Invoice::class);

        $query = Invoice::query()->with('invoiceable');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('issue_date', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('issue_date', '<=', $to);
        }

        $invoices = $query->latest()->paginate(20)->withQueryString();

        return view('erp::admin.invoices.index', compact('invoices'));
    }

    public function create()
    {
        Gate::authorize('create', Invoice::class);

        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('erp::admin.invoices.create', compact('products'));
    }

    public function store(StoreInvoiceRequest $request)
    {
        $data = $request->validated();

        $invoice = DB::transaction(function () use ($data): Invoice {
            $invoice = Invoice::create([
                'invoice_number'   => $this->service->generateInvoiceNumber(),
                'type'             => $data['type'],
                'invoiceable_type' => $data['invoiceable_type'] ?? null,
                'invoiceable_id'   => $data['invoiceable_id'] ?? null,
                'issue_date'       => $data['issue_date'],
                'due_date'         => $data['due_date'],
                'currency'         => $data['currency'] ?? config('erp.currency', 'TRY'),
                'discount_amount'  => $data['discount_amount'] ?? 0,
                'reference'        => $data['reference'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'status'           => 'draft',
                'created_by'       => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $base      = (float) $item['unit_price'] * (float) $item['quantity'];
                $discounted= $base * (1 - ((float) ($item['discount_rate'] ?? 0)) / 100);
                $tax       = $discounted * ((float) ($item['tax_rate'] ?? 20)) / 100;

                InvoiceItem::create([
                    'invoice_id'    => $invoice->id,
                    'product_id'    => $item['product_id'] ?? null,
                    'description'   => $item['description'],
                    'quantity'      => $item['quantity'],
                    'unit_price'    => $item['unit_price'],
                    'tax_rate'      => $item['tax_rate'] ?? 20,
                    'discount_rate' => $item['discount_rate'] ?? 0,
                    'line_total'    => $discounted + $tax,
                ]);
            }

            $invoice->load('items');
            $this->service->recalculateTotals($invoice);

            return $invoice;
        });

        return redirect()->route('erp.invoices.show', $invoice)
            ->with('success', __('Fatura oluşturuldu.'));
    }

    public function show(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['items.product', 'invoiceable', 'payments.createdBy', 'createdBy']);

        return view('erp::admin.invoices.show', compact('invoice'));
    }

    public function destroy(Invoice $invoice)
    {
        Gate::authorize('delete', $invoice);

        $invoice->delete();

        return redirect()->route('erp.invoices.index')
            ->with('success', __('Fatura silindi.'));
    }

    public function send(Invoice $invoice)
    {
        Gate::authorize('send', $invoice);

        abort_if($invoice->status !== 'draft', 422, __('Sadece taslak faturalar gönderilebilir.'));

        $invoice->update(['status' => 'sent']);

        $fresh = $invoice->fresh();
        app(\App\Erp\Services\Accounting\AccountingService::class)->postSaleInvoice($fresh);
        app(\App\Erp\Services\EFatura\EFaturaService::class)->processInvoice($fresh);

        return redirect()->route('erp.invoices.show', $invoice)
            ->with('success', __('Fatura gönderildi olarak işaretlendi.'));
    }

    public function storePayment(StorePaymentRequest $request, Invoice $invoice)
    {
        abort_if(in_array($invoice->status, ['paid', 'cancelled']), 422, __('Bu fatura için ödeme yapılamaz.'));

        $this->service->recordPayment($invoice, $request->validated());

        return redirect()->route('erp.invoices.show', $invoice)
            ->with('success', __('Ödeme kaydedildi.'));
    }

    public function downloadPdf(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        $pdf = $this->service->generatePdf($invoice);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $invoice->invoice_number . '.pdf"',
        ]);
    }
}
