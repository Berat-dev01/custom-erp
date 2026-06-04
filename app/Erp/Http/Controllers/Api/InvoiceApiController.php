<?php

namespace App\Erp\Http\Controllers\Api;

use App\Erp\Http\Resources\InvoiceResource;
use App\Erp\Models\Invoice;
use App\Erp\Services\Finance\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class InvoiceApiController extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('erp.invoices.view');

        $perPage = min((int) $request->get('per_page', config('erp.api.default_per_page', 20)), config('erp.api.max_per_page', 100));

        $query = Invoice::with(['invoiceable'])
            ->when($request->get('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->get('type'),   fn ($q, $v) => $q->where('type', $v))
            ->when($request->get('date_from'), fn ($q, $v) => $q->where('issue_date', '>=', $v))
            ->when($request->get('date_to'),   fn ($q, $v) => $q->where('issue_date', '<=', $v))
            ->latest('issue_date');

        return InvoiceResource::collection($query->paginate($perPage));
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        Gate::authorize('erp.invoices.view');

        $invoice->loadMissing(['items.product', 'payments', 'invoiceable']);

        return new InvoiceResource($invoice);
    }

    public function storePayment(Request $request, Invoice $invoice): JsonResponse
    {
        Gate::authorize('erp.payments.create');

        $data = $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:' . $invoice->remainingAmount()],
            'payment_date' => ['required', 'date'],
            'method'       => ['required', 'in:cash,bank_transfer,credit_card,check,other'],
            'reference'    => ['nullable', 'string', 'max:100'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $payment = $this->invoiceService->recordPayment($invoice, $data);

        return response()->json([
            'message'    => 'Payment recorded.',
            'payment_id' => $payment->id,
            'invoice'    => new InvoiceResource($invoice->fresh()),
        ], 201);
    }
}
