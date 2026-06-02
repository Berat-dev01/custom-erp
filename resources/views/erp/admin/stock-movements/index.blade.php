@extends('erp::layouts.app')

@section('title', __('Stok Hareketleri'))
@section('page-title', __('Stok Hareketleri'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <form method="GET" action="{{ route('erp.stock-movements.index') }}" class="d-flex gap-2 flex-wrap">
            <x-admin-panel::select name="product_id"
                :options="$products->pluck('name','id')->prepend(__('Tüm Ürünler'), '')->toArray()"
                :selected="request('product_id')" />
            <x-admin-panel::select name="warehouse_id"
                :options="$warehouses->pluck('name','id')->prepend(__('Tüm Depolar'), '')->toArray()"
                :selected="request('warehouse_id')" />
            <x-admin-panel::select name="type"
                :options="['' => __('Tüm Tipler'), 'in' => __('Giriş'), 'out' => __('Çıkış'), 'adjustment' => __('Düzeltme')]"
                :selected="request('type')" />
            <x-admin-panel::input name="date_from" type="date" :value="request('date_from')" />
            <x-admin-panel::input name="date_to" type="date" :value="request('date_to')" />
            <x-admin-panel::button type="submit" variant="outline" icon="search">{{ __('Filtrele') }}</x-admin-panel::button>
        </form>

        @can('erp.stock_movements.create')
            <x-admin-panel::button href="{{ route('erp.stock-movements.create') }}" icon="plus" variant="primary">
                {{ __('Manuel Hareket') }}
            </x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Tarih'), __('Ürün'), __('Depo'), __('Tip'), __('Miktar'), __('Birim Maliyet'), __('Notlar'), __('Kaydeden')]">
            @forelse($movements as $mv)
                <tr>
                    <td class="text-nowrap">{{ $erpFormat->datetime($mv->created_at) }}</td>
                    <td>
                        <a href="{{ route('erp.products.show', $mv->product_id) }}">{{ $mv->product?->name }}</a>
                        <div class="text-muted small font-monospace">{{ $mv->product?->sku }}</div>
                    </td>
                    <td>{{ $mv->warehouse?->name }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ $mv->type === 'in' ? 'success' : ($mv->type === 'out' ? 'danger' : 'warning') }}">
                            {{ __($mv->type) }}
                        </x-admin-panel::badge>
                    </td>
                    <td>{{ number_format($mv->quantity, 3) }}</td>
                    <td>{{ $mv->unit_cost ? $erpFormat->money($mv->unit_cost) : '-' }}</td>
                    <td>{{ $mv->notes ?? '-' }}</td>
                    <td>{{ $mv->createdBy?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">{{ __('Stok hareketi bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $movements->links() }}</div>
@endsection
