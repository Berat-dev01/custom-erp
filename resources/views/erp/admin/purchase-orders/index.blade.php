@extends('erp::layouts.app')

@section('title', __('Satın Alma Siparişleri'))
@section('page-title', __('Satın Alma Siparişleri'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <form method="GET" action="{{ route('erp.purchase-orders.index') }}" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::select name="status"
                :options="['' => __('Tüm Durumlar'), 'draft' => __('Taslak'), 'sent' => __('Gönderildi'), 'partial' => __('Kısmi Teslim'), 'received' => __('Teslim Alındı'), 'cancelled' => __('İptal')]"
                :selected="request('status')" />
            <x-admin-panel::select name="supplier_id"
                :options="$suppliers->pluck('name','id')->prepend(__('Tüm Tedarikçiler'), '')->toArray()"
                :selected="request('supplier_id')" />
            <x-admin-panel::input name="date_from" type="date" :value="request('date_from')" />
            <x-admin-panel::input name="date_to" type="date" :value="request('date_to')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>
        @can('erp.purchase_orders.create')
            <x-admin-panel::button href="{{ route('erp.purchase-orders.create') }}" icon="plus" variant="primary">{{ __('Yeni Sipariş') }}</x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('PO No'), __('Tedarikçi'), __('Depo'), __('Tarih'), __('Toplam'), __('Durum'), '']">
            @forelse($orders as $po)
                <tr>
                    <td><a href="{{ route('erp.purchase-orders.show', $po) }}" class="fw-medium font-monospace">{{ $po->po_number }}</a></td>
                    <td>{{ $po->supplier?->name }}</td>
                    <td>{{ $po->warehouse?->name }}</td>
                    <td>{{ $erpFormat->date($po->order_date) }}</td>
                    <td>{{ $erpFormat->money($po->total, $po->currency) }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ match($po->status) { 'received' => 'success', 'cancelled' => 'danger', 'draft' => 'secondary', 'partial' => 'warning', default => 'info' } }}">
                            {{ __($po->status) }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        @can('erp.purchase_orders.delete')
                            @if($po->isDraft())
                                <form method="POST" action="{{ route('erp.purchase-orders.destroy', $po) }}" style="display:inline">
                                    @csrf @method('DELETE')
                                    <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2"
                                        onclick="return confirm('{{ __('Silmek istediğinize emin misiniz?') }}')" />
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('Sipariş bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $orders->links() }}</div>
@endsection
