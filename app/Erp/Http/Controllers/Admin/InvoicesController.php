<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreInvoiceRequest;
use App\Erp\Http\Requests\StorePaymentRequest;
use App\Erp\Models\Invoice;
use App\Erp\Models\InvoiceItem;
use App\Erp\Models\Product;
use App\Erp\Services\Finance\InvoiceQuery;
use App\Erp\Services\Finance\InvoiceService;
use App\Erp\Support\ErpExportSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InvoicesController extends Controller
{
    public function __construct(
        private readonly InvoiceService $service,
        private readonly InvoiceQuery $query,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Invoice::class);

        return view('erp::admin.invoices.index', [
            'invoices'      => $this->query->paginate($request),
            'filters'       => $this->query->filters($request),
            'exportColumns' => ErpExportSchema::columns('invoices'),
            'exportFormats' => ErpExportSchema::formats('invoices'),
        ]);
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
            ->with('erp_status', __('Fatura oluşturuldu.'));
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
            ->with('erp_status', __('Fatura silindi.'));
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
            ->with('erp_status', __('Fatura gönderildi olarak işaretlendi.'));
    }

    public function storePayment(StorePaymentRequest $request, Invoice $invoice)
    {
        abort_if(in_array($invoice->status, ['paid', 'cancelled']), 422, __('Bu fatura için ödeme yapılamaz.'));

        $this->service->recordPayment($invoice, $request->validated());

        return redirect()->route('erp.invoices.show', $invoice)
            ->with('erp_status', __('Ödeme kaydedildi.'));
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

    public function sendEfatura(Invoice $invoice)
    {
        Gate::authorize('erp.invoices.update');

        $service = app(\App\Erp\Services\EFatura\EFaturaService::class);

        if (! $service->isEnabled()) {
            return redirect()->route('erp.invoices.show', $invoice)
                ->with('erp_status', __('e-Fatura modülü aktif değil. Lütfen .env ayarlarını kontrol edin.'));
        }

        $result = $service->processInvoice($invoice);

        $message = $result ? __('e-Fatura gönderildi.') : __('e-Fatura gönderilemedi. Lütfen tekrar deneyin.');

        return redirect()->route('erp.invoices.show', $invoice)
            ->with('erp_status', $message);
    }

    public function cancelEfatura(Invoice $invoice): RedirectResponse
    {
        Gate::authorize('erp.invoices.update');

        $service = app(\App\Erp\Services\EFatura\EFaturaService::class);

        if (! $service->isEnabled()) {
            return redirect()->route('erp.invoices.show', $invoice)
                ->with('erp_status', __('e-Fatura modülü aktif değil.'));
        }

        $success = $service->cancelInvoice($invoice);

        return redirect()->route('erp.invoices.show', $invoice)
            ->with('erp_status', $success ? __('e-Fatura iptal edildi.') : __('e-Fatura iptal edilemedi.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('erp.invoices.delete');

        $validated = $request->validate([
            'record_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:erp_invoices,id'],
        ]);

        $deleted = 0;
        Invoice::query()
            ->whereKey($validated['record_ids'])
            ->chunkById(200, function ($invoices) use (&$deleted): void {
                foreach ($invoices as $invoice) {
                    $invoice->delete();
                    $deleted++;
                }
            });

        return back()->with('erp_status', trans_choice(
            '{0} Hiçbiri silinemedi.|{1} :count fatura silindi.|[2,*] :count fatura silindi.',
            $deleted, ['count' => $deleted]
        ));
    }
}
