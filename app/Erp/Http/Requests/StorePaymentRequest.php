<?php

namespace App\Erp\Http\Requests;

use App\Erp\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice
            ? Gate::allows('recordPayment', $invoice)
            : Gate::allows('erp.payments.create');
    }

    public function rules(): array
    {
        $invoice    = $this->route('invoice');
        $remaining  = $invoice instanceof Invoice ? $invoice->remainingAmount() : null;

        return [
            'amount'       => ['required', 'numeric', 'min:0.01', ...($remaining ? ["max:{$remaining}"] : [])],
            'payment_date' => ['required', 'date'],
            'method'       => ['required', 'in:cash,bank_transfer,credit_card,check,other'],
            'reference'    => ['nullable', 'string', 'max:100'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ];
    }
}
