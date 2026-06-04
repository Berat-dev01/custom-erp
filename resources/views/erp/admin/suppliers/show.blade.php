@extends('erp::layouts.app')

@section('title', $supplier->name)
@section('page-title', $supplier->name)

@section('content')
    <div class="d-flex gap-2 mb-3">
        @can('erp.suppliers.update')
            <x-admin-panel::button href="{{ route('erp.suppliers.edit', $supplier) }}" icon="pencil" variant="outline">{{ __('Düzenle') }}</x-admin-panel::button>
        @endcan
        <x-admin-panel::button href="{{ route('erp.suppliers.index') }}" variant="ghost">{{ __('← Liste') }}</x-admin-panel::button>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Tedarikçi Bilgileri') }}</h6>
                <table class="table table-sm">
                    <tr><th>{{ __('Kod') }}</th><td>{{ $supplier->code ?? '-' }}</td></tr>
                    <tr><th>{{ __('E-posta') }}</th><td>{{ $supplier->email ?? '-' }}</td></tr>
                    <tr><th>{{ __('Telefon') }}</th><td>{{ $supplier->phone ?? '-' }}</td></tr>
                    <tr><th>{{ __('Vergi No') }}</th><td>{{ $supplier->tax_number ?? '-' }}</td></tr>
                    <tr><th>{{ __('İletişim Kişisi') }}</th><td>{{ $supplier->contact_person ?? '-' }}</td></tr>
                    <tr><th>{{ __('Adres') }}</th><td>{{ $supplier->address ?? '-' }}</td></tr>
                    <tr><th>{{ __('Ödeme Vadesi') }}</th><td>{{ $supplier->payment_terms_days }} {{ __('gün') }}</td></tr>
                    <tr><th>{{ __('Kredi Limiti') }}</th><td>{{ $erpFormat->money($supplier->credit_limit) }}</td></tr>
                </table>
            </x-admin-panel::card>
        </div>
        <div class="col-md-6">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Son Siparişler') }}</h6>
                @forelse($orders as $order)
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <a href="{{ route('erp.purchase-orders.show', $order) }}">{{ $order->po_number }}</a>
                        <div>
                            <x-admin-panel::badge variant="{{ match($order->status) { 'received' => 'success', 'cancelled' => 'danger', 'draft' => 'secondary', default => 'warning' } }}">
                                {{ __($order->status) }}
                            </x-admin-panel::badge>
                            <span class="ms-2">{{ $erpFormat->money($order->total) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">{{ __('Henüz sipariş yok.') }}</p>
                @endforelse
            </x-admin-panel::card>
        </div>
    </div>
@endsection
