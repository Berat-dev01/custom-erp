@extends('erp::layouts.app')

@section('title', $customer->name)
@section('page-title', $customer->name)

@section('content')
    <div class="d-flex gap-2 mb-3">
        @can('erp.customers.update')
            <x-admin-panel::button href="{{ route('erp.customers.edit', $customer) }}" icon="pencil" variant="outline">{{ __('Düzenle') }}</x-admin-panel::button>
        @endcan
        @can('erp.sales_orders.create')
            <x-admin-panel::button href="{{ route('erp.sales-orders.create') }}?customer_id={{ $customer->id }}" icon="plus" variant="outline">{{ __('Yeni Sipariş') }}</x-admin-panel::button>
        @endcan
        <x-admin-panel::button href="{{ route('erp.customers.index') }}" variant="ghost">{{ __('← Liste') }}</x-admin-panel::button>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Müşteri Bilgileri') }}</h6>
                <table class="table table-sm">
                    <tr><th>{{ __('E-posta') }}</th><td>{{ $customer->email ?? '-' }}</td></tr>
                    <tr><th>{{ __('Telefon') }}</th><td>{{ $customer->phone ?? '-' }}</td></tr>
                    <tr><th>{{ __('Vergi No') }}</th><td>{{ $customer->tax_number ?? '-' }}</td></tr>
                    <tr><th>{{ __('İletişim Kişisi') }}</th><td>{{ $customer->contact_person ?? '-' }}</td></tr>
                    <tr><th>{{ __('Adres') }}</th><td>{{ $customer->address ?? '-' }}</td></tr>
                    <tr><th>{{ __('Ödeme Vadesi') }}</th><td>{{ $customer->payment_terms_days }} {{ __('gün') }}</td></tr>
                    <tr><th>{{ __('Kredi Limiti') }}</th><td>{{ $erpFormat->money($customer->credit_limit) }}</td></tr>
                </table>
            </x-admin-panel::card>
        </div>
        <div class="col-md-7">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Son Siparişler') }}</h6>
                @forelse($orders as $order)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <a href="{{ route('erp.sales-orders.show', $order) }}" class="fw-medium">{{ $order->so_number }}</a>
                            <div class="text-muted small">{{ $erpFormat->date($order->order_date) }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <x-admin-panel::badge variant="{{ match($order->status) { 'delivered' => 'success', 'cancelled' => 'danger', 'draft' => 'secondary', 'confirmed' => 'info', default => 'warning' } }}">
                                {{ __($order->status) }}
                            </x-admin-panel::badge>
                            <span>{{ $erpFormat->money($order->total) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">{{ __('Henüz sipariş yok.') }}</p>
                @endforelse
            </x-admin-panel::card>
        </div>
    </div>
@endsection
