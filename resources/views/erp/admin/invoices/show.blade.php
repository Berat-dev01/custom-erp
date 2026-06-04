@extends('erp::layouts.app')

@section('title', $invoice->invoice_number)
@section('page-title', $invoice->invoice_number)

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex gap-2 mb-3 flex-wrap">
        @if($invoice->status === 'draft')
            @can('send', $invoice)
                <form method="POST" action="{{ route('erp.invoices.send', $invoice) }}">
                    @csrf
                    <x-admin-panel::button type="submit" variant="primary" icon="send">{{ __('Gönderildi Olarak İşaretle') }}</x-admin-panel::button>
                </form>
            @endcan
        @endif
        <x-admin-panel::button href="{{ route('erp.invoices.pdf', $invoice) }}" variant="outline" icon="download" target="_blank">{{ __('PDF İndir') }}</x-admin-panel::button>
        <x-admin-panel::button href="{{ route('erp.invoices.index') }}" variant="ghost">{{ __('← Liste') }}</x-admin-panel::button>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Fatura Bilgileri') }}</h6>
                <table class="table table-sm">
                    <tr><th>{{ __('Fatura No') }}</th><td class="font-monospace fw-bold">{{ $invoice->invoice_number }}</td></tr>
                    <tr><th>{{ __('Tip') }}</th><td>{{ __($invoice->type) }}</td></tr>
                    <tr><th>{{ __('Düzenleme Tarihi') }}</th><td>{{ $erpFormat->date($invoice->issue_date) }}</td></tr>
                    <tr><th>{{ __('Vade Tarihi') }}</th><td class="{{ $invoice->isOverdue() ? 'text-danger fw-medium' : '' }}">{{ $erpFormat->date($invoice->due_date) }}</td></tr>
                    <tr><th>{{ __('Durum') }}</th><td>
                        <x-admin-panel::badge variant="{{ match($invoice->status) { 'paid' => 'success', 'overdue' => 'danger', 'draft' => 'secondary', 'partial' => 'warning', default => 'info' } }}">
                            {{ __($invoice->status) }}
                        </x-admin-panel::badge>
                    </td></tr>
                    <tr><th>{{ __('Ara Toplam') }}</th><td>{{ $erpFormat->money($invoice->subtotal, $invoice->currency) }}</td></tr>
                    @if($invoice->discount_amount > 0)
                        <tr><th>{{ __('İndirim') }}</th><td>-{{ $erpFormat->money($invoice->discount_amount, $invoice->currency) }}</td></tr>
                    @endif
                    <tr><th>{{ __('KDV') }}</th><td>{{ $erpFormat->money($invoice->tax_amount, $invoice->currency) }}</td></tr>
                    <tr><th class="fw-bold">{{ __('Toplam') }}</th><td class="fw-bold">{{ $erpFormat->money($invoice->total, $invoice->currency) }}</td></tr>
                    <tr><th>{{ __('Ödenen') }}</th><td class="text-success">{{ $erpFormat->money($invoice->paid_amount, $invoice->currency) }}</td></tr>
                    <tr><th>{{ __('Kalan') }}</th><td class="text-danger fw-bold">{{ $erpFormat->money($invoice->remainingAmount(), $invoice->currency) }}</td></tr>
                </table>
            </x-admin-panel::card>

            {{-- Ödeme Ekle --}}
            @if(!in_array($invoice->status, ['paid', 'cancelled', 'draft']))
                @can('recordPayment', $invoice)
                    <x-admin-panel::card class="mt-3">
                        <h6 class="fw-semibold mb-3">{{ __('Ödeme Kaydet') }}</h6>
                        <form method="POST" action="{{ route('erp.invoices.payments.store', $invoice) }}">
                            @csrf
                            <div class="row g-2">
                                <div class="col-6"><x-admin-panel::input name="amount" type="number" step="0.01" :label="__('Tutar')" :value="old('amount', $invoice->remainingAmount())" required /></div>
                                <div class="col-6"><x-admin-panel::input name="payment_date" type="date" :label="__('Tarih')" :value="old('payment_date', date('Y-m-d'))" required /></div>
                                <div class="col-12">
                                    <x-admin-panel::select name="method" :label="__('Yöntem')" required
                                        :options="['cash' => __('Nakit'), 'bank_transfer' => __('Banka Transferi'), 'credit_card' => __('Kredi Kartı'), 'check' => __('Çek'), 'other' => __('Diğer')]"
                                        :selected="old('method', 'bank_transfer')" />
                                </div>
                                <div class="col-12"><x-admin-panel::input name="reference" :label="__('Referans')" :value="old('reference')" /></div>
                            </div>
                            <div class="mt-3">
                                <x-admin-panel::button type="submit" variant="primary" icon="check">{{ __('Ödemeyi Kaydet') }}</x-admin-panel::button>
                            </div>
                        </form>
                    </x-admin-panel::card>
                @endcan
            @endif
        </div>

        <div class="col-md-7">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Fatura Kalemleri') }}</h6>
                <x-admin-panel::table :headers="[__('Açıklama'), __('Ürün'), __('Miktar'), __('Birim Fiyat'), __('KDV'), __('Toplam')]">
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->product?->name ?? '-' }}</td>
                            <td>{{ number_format($item->quantity, 3) }}</td>
                            <td>{{ $erpFormat->money($item->unit_price) }}</td>
                            <td>%{{ $item->tax_rate }}</td>
                            <td>{{ $erpFormat->money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </x-admin-panel::table>
            </x-admin-panel::card>

            @if($invoice->payments->isNotEmpty())
                <x-admin-panel::card class="mt-3">
                    <h6 class="fw-semibold mb-3">{{ __('Ödeme Geçmişi') }}</h6>
                    <x-admin-panel::table :headers="[__('Tarih'), __('Yöntem'), __('Referans'), __('Tutar')]">
                        @foreach($invoice->payments as $payment)
                            <tr>
                                <td>{{ $erpFormat->date($payment->payment_date) }}</td>
                                <td>{{ __($payment->method) }}</td>
                                <td>{{ $payment->reference ?? '-' }}</td>
                                <td class="text-success fw-medium">{{ $erpFormat->money($payment->amount, $invoice->currency) }}</td>
                            </tr>
                        @endforeach
                    </x-admin-panel::table>
                </x-admin-panel::card>
            @endif
        </div>
    </div>
@endsection
