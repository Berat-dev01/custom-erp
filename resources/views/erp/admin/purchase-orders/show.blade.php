@extends('erp::layouts.app')

@section('title', $purchaseOrder->po_number)
@section('page-title', $purchaseOrder->po_number)

@section('content')
    @include('erp::admin.partials.status')

    <div class="d-flex gap-2 mb-3 flex-wrap">
        @if($purchaseOrder->isDraft())
            @can('approve', $purchaseOrder)
                <form method="POST" action="{{ route('erp.purchase-orders.approve', $purchaseOrder) }}">
                    @csrf
                    <x-admin-panel::button type="submit" variant="primary" icon="send">{{ __('Gönderildi Olarak İşaretle') }}</x-admin-panel::button>
                </form>
            @endcan
        @endif

        @if(in_array($purchaseOrder->status, ['sent', 'partial']))
            @can('receive', $purchaseOrder)
                <x-admin-panel::button href="{{ route('erp.purchase-orders.receive', $purchaseOrder) }}" variant="outline" icon="package">{{ __('Mal Teslim Al') }}</x-admin-panel::button>
            @endcan
        @endif

        <x-admin-panel::button href="{{ route('erp.purchase-orders.index') }}" variant="ghost">{{ __('← Liste') }}</x-admin-panel::button>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Sipariş Bilgileri') }}</h6>
                <table class="table table-sm">
                    <tr><th>{{ __('PO No') }}</th><td class="font-monospace fw-bold">{{ $purchaseOrder->po_number }}</td></tr>
                    <tr><th>{{ __('Tedarikçi') }}</th><td>{{ $purchaseOrder->supplier?->name }}</td></tr>
                    <tr><th>{{ __('Depo') }}</th><td>{{ $purchaseOrder->warehouse?->name }}</td></tr>
                    <tr><th>{{ __('Sipariş Tarihi') }}</th><td>{{ $erpFormat->date($purchaseOrder->order_date) }}</td></tr>
                    <tr><th>{{ __('Tahmini Teslim') }}</th><td>{{ $erpFormat->date($purchaseOrder->expected_date) }}</td></tr>
                    <tr><th>{{ __('Durum') }}</th><td>
                        <x-admin-panel::badge variant="{{ match($purchaseOrder->status) { 'received' => 'success', 'cancelled' => 'danger', 'draft' => 'secondary', 'partial' => 'warning', default => 'info' } }}">
                            {{ __($purchaseOrder->status) }}
                        </x-admin-panel::badge>
                    </td></tr>
                    <tr><th>{{ __('Ara Toplam') }}</th><td>{{ $erpFormat->money($purchaseOrder->subtotal, $purchaseOrder->currency) }}</td></tr>
                    <tr><th>{{ __('KDV') }}</th><td>{{ $erpFormat->money($purchaseOrder->tax_amount, $purchaseOrder->currency) }}</td></tr>
                    <tr><th class="fw-bold">{{ __('Toplam') }}</th><td class="fw-bold">{{ $erpFormat->money($purchaseOrder->total, $purchaseOrder->currency) }}</td></tr>
                </table>
            </x-admin-panel::card>
        </div>
        <div class="col-md-7">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Sipariş Kalemleri') }}</h6>
                <x-admin-panel::table :headers="[__('Ürün'), __('Miktar'), __('Teslim Alınan'), __('Bekleyen'), __('Birim Fiyat'), __('Toplam')]">
                    @foreach($purchaseOrder->items as $item)
                        <tr>
                            <td>
                                <a href="{{ route('erp.products.show', $item->product_id) }}">{{ $item->product?->name }}</a>
                                <div class="text-muted small font-monospace">{{ $item->product?->sku }}</div>
                            </td>
                            <td>{{ number_format($item->quantity, 3) }}</td>
                            <td>
                                <span class="{{ $item->received_quantity >= $item->quantity ? 'text-success' : '' }}">
                                    {{ number_format($item->received_quantity, 3) }}
                                </span>
                            </td>
                            <td>{{ number_format($item->pendingQuantity(), 3) }}</td>
                            <td>{{ $erpFormat->money($item->unit_price) }}</td>
                            <td>{{ $erpFormat->money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </x-admin-panel::table>
            </x-admin-panel::card>
        </div>

        @if($purchaseOrder->notes)
            <div class="col-12">
                <x-admin-panel::card>
                    <h6 class="fw-semibold">{{ __('Notlar') }}</h6>
                    <p class="mb-0">{{ $purchaseOrder->notes }}</p>
                </x-admin-panel::card>
            </div>
        @endif
    </div>
@endsection
