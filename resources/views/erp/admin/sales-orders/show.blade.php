@extends('erp::layouts.app')

@section('title', $salesOrder->so_number)
@section('page-title', $salesOrder->so_number)

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex gap-2 mb-3 flex-wrap">
        @if($salesOrder->isDraft())
            @can('confirm', $salesOrder)
                <form method="POST" action="{{ route('erp.sales-orders.confirm', $salesOrder) }}">
                    @csrf
                    <x-admin-panel::button type="submit" variant="primary" icon="check-circle">{{ __('Onayla ve Rezerve Et') }}</x-admin-panel::button>
                </form>
            @endcan
        @endif

        @if($salesOrder->canBeDelivered())
            @can('deliver', $salesOrder)
                <form method="POST" action="{{ route('erp.sales-orders.deliver', $salesOrder) }}">
                    @csrf
                    <x-admin-panel::button type="submit" variant="primary" icon="truck">{{ __('Teslim Edildi') }}</x-admin-panel::button>
                </form>
            @endcan
        @endif

        @if($salesOrder->status === 'delivered')
            @can('createInvoice', $salesOrder)
                <form method="POST" action="{{ route('erp.sales-orders.create-invoice', $salesOrder) }}">
                    @csrf
                    <x-admin-panel::button type="submit" variant="outline" icon="file-text">{{ __('Fatura Oluştur') }}</x-admin-panel::button>
                </form>
            @endcan
        @endif

        @if($salesOrder->canBeCancelled())
            @can('cancel', $salesOrder)
                <form method="POST" action="{{ route('erp.sales-orders.cancel', $salesOrder) }}">
                    @csrf
                    <x-admin-panel::button type="submit" variant="danger" icon="x-circle"
                        onclick="return confirm('{{ __('Siparişi iptal etmek istediğinize emin misiniz?') }}')">{{ __('İptal Et') }}</x-admin-panel::button>
                </form>
            @endcan
        @endif

        <x-admin-panel::button href="{{ route('erp.sales-orders.index') }}" variant="ghost">{{ __('← Liste') }}</x-admin-panel::button>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Sipariş Bilgileri') }}</h6>
                <table class="table table-sm">
                    <tr><th>{{ __('SO No') }}</th><td class="font-monospace fw-bold">{{ $salesOrder->so_number }}</td></tr>
                    <tr><th>{{ __('Müşteri') }}</th><td><a href="{{ route('erp.customers.show', $salesOrder->customer_id) }}">{{ $salesOrder->customer?->name }}</a></td></tr>
                    <tr><th>{{ __('Depo') }}</th><td>{{ $salesOrder->warehouse?->name }}</td></tr>
                    <tr><th>{{ __('Sipariş Tarihi') }}</th><td>{{ $erpFormat->date($salesOrder->order_date) }}</td></tr>
                    <tr><th>{{ __('İstenen Teslimat') }}</th><td>{{ $erpFormat->date($salesOrder->requested_delivery_date) }}</td></tr>
                    @if($salesOrder->actual_delivery_date)
                        <tr><th>{{ __('Gerçekleşen Teslimat') }}</th><td class="text-success">{{ $erpFormat->date($salesOrder->actual_delivery_date) }}</td></tr>
                    @endif
                    <tr><th>{{ __('Durum') }}</th><td>
                        <x-admin-panel::badge variant="{{ match($salesOrder->status) { 'delivered' => 'success', 'cancelled' => 'danger', 'draft' => 'secondary', 'confirmed' => 'info', default => 'warning' } }}">
                            {{ __($salesOrder->status) }}
                        </x-admin-panel::badge>
                    </td></tr>
                    <tr><th>{{ __('Ara Toplam') }}</th><td>{{ $erpFormat->money($salesOrder->subtotal) }}</td></tr>
                    @if($salesOrder->discount_amount > 0)
                        <tr><th>{{ __('İndirim') }}</th><td>-{{ $erpFormat->money($salesOrder->discount_amount) }}</td></tr>
                    @endif
                    <tr><th>{{ __('KDV') }}</th><td>{{ $erpFormat->money($salesOrder->tax_amount) }}</td></tr>
                    <tr><th class="fw-bold">{{ __('Toplam') }}</th><td class="fw-bold">{{ $erpFormat->money($salesOrder->total) }}</td></tr>
                </table>
            </x-admin-panel::card>
        </div>

        <div class="col-md-8">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Sipariş Kalemleri') }}</h6>
                <x-admin-panel::table :headers="[__('Ürün'), __('Miktar'), __('Birim Fiyat'), __('İndirim'), __('KDV'), __('Toplam')]">
                    @foreach($salesOrder->items as $item)
                        <tr>
                            <td>
                                <a href="{{ route('erp.products.show', $item->product_id) }}">{{ $item->product?->name }}</a>
                                <div class="text-muted small font-monospace">{{ $item->product?->sku }}</div>
                            </td>
                            <td>{{ number_format($item->quantity, 3) }}</td>
                            <td>{{ $erpFormat->money($item->unit_price) }}</td>
                            <td>%{{ $item->discount_rate }}</td>
                            <td>%{{ $item->tax_rate }}</td>
                            <td class="fw-medium">{{ $erpFormat->money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </x-admin-panel::table>
            </x-admin-panel::card>
        </div>
    </div>
@endsection
