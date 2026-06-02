@extends('erp::layouts.app')

@section('title', __('Satış Siparişleri'))
@section('page-title', __('Satış Siparişleri'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <form method="GET" action="{{ route('erp.sales-orders.index') }}" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::select name="status"
                :options="['' => __('Tüm Durumlar'), 'draft' => __('Taslak'), 'confirmed' => __('Onaylandı'), 'picking' => __('Hazırlanıyor'), 'shipped' => __('Kargoda'), 'delivered' => __('Teslim Edildi'), 'cancelled' => __('İptal')]"
                :selected="request('status')" />
            <x-admin-panel::select name="customer_id"
                :options="$customers->pluck('name','id')->prepend(__('Tüm Müşteriler'), '')->toArray()"
                :selected="request('customer_id')" />
            <x-admin-panel::input name="date_from" type="date" :value="request('date_from')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>
        @can('erp.sales_orders.create')
            <x-admin-panel::button href="{{ route('erp.sales-orders.create') }}" icon="plus" variant="primary">{{ __('Yeni Sipariş') }}</x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('SO No'), __('Müşteri'), __('Depo'), __('Tarih'), __('Toplam'), __('Durum'), '']">
            @forelse($orders as $so)
                <tr>
                    <td><a href="{{ route('erp.sales-orders.show', $so) }}" class="fw-medium font-monospace">{{ $so->so_number }}</a></td>
                    <td>{{ $so->customer?->name }}</td>
                    <td>{{ $so->warehouse?->name }}</td>
                    <td>{{ $erpFormat->date($so->order_date) }}</td>
                    <td>{{ $erpFormat->money($so->total) }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ match($so->status) { 'delivered' => 'success', 'cancelled' => 'danger', 'draft' => 'secondary', 'confirmed' => 'info', default => 'warning' } }}">
                            {{ __($so->status) }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        @if($so->isDraft())
                            @can('erp.sales_orders.delete')
                                <form method="POST" action="{{ route('erp.sales-orders.destroy', $so) }}" style="display:inline">
                                    @csrf @method('DELETE')
                                    <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                        onclick="return confirm('{{ __('Silmek istediğinize emin misiniz?') }}')" />
                                </form>
                            @endcan
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('Sipariş bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $orders->links() }}</div>
@endsection
