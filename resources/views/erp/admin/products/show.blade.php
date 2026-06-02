@extends('erp::layouts.app')

@section('title', $product->name)
@section('page-title', $product->name)

@section('content')
    <div class="d-flex gap-2 mb-3">
        @can('erp.products.update')
            <x-admin-panel::button href="{{ route('erp.products.edit', $product) }}" icon="pencil" variant="outline">{{ __('Düzenle') }}</x-admin-panel::button>
        @endcan
        @can('erp.stock_movements.create')
            <x-admin-panel::button href="{{ route('erp.stock-movements.create') }}?product_id={{ $product->id }}" icon="plus-circle" variant="outline">{{ __('Stok Hareketi Ekle') }}</x-admin-panel::button>
        @endcan
        <x-admin-panel::button href="{{ route('erp.products.index') }}" variant="ghost">{{ __('← Listeye Dön') }}</x-admin-panel::button>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Ürün Bilgileri') }}</h6>
                <table class="table table-sm">
                    <tr><th>SKU</th><td class="font-monospace">{{ $product->sku }}</td></tr>
                    <tr><th>{{ __('Barkod') }}</th><td>{{ $product->barcode ?? '-' }}</td></tr>
                    <tr><th>{{ __('Kategori') }}</th><td>{{ $product->category?->name ?? '-' }}</td></tr>
                    <tr><th>{{ __('Birim') }}</th><td>{{ $product->unit?->name }} ({{ $product->unit?->abbreviation }})</td></tr>
                    <tr><th>{{ __('Tip') }}</th><td>{{ __($product->type) }}</td></tr>
                    <tr><th>{{ __('Alış Fiyatı') }}</th><td>{{ $erpFormat->money($product->purchase_price) }}</td></tr>
                    <tr><th>{{ __('Satış Fiyatı') }}</th><td>{{ $erpFormat->money($product->sale_price) }}</td></tr>
                    <tr><th>{{ __('KDV') }}</th><td>%{{ $product->tax_rate }}</td></tr>
                    <tr><th>{{ __('Yeniden Sipariş') }}</th><td>{{ $product->reorder_point }}</td></tr>
                </table>
            </x-admin-panel::card>
        </div>
        <div class="col-md-6">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Depo Bazlı Stok') }}</h6>
                @if($product->stockLevels->isNotEmpty())
                    <x-admin-panel::table :headers="[__('Depo'), __('Miktar'), __('Rezerve')]">
                        @foreach($product->stockLevels as $level)
                            <tr>
                                <td>{{ $level->warehouse?->name }}</td>
                                <td>{{ number_format($level->quantity, 3) }}</td>
                                <td>{{ number_format($level->reserved_quantity, 3) }}</td>
                            </tr>
                        @endforeach
                    </x-admin-panel::table>
                @else
                    <p class="text-muted">{{ __('Henüz stok hareketi yok.') }}</p>
                @endif
            </x-admin-panel::card>
        </div>

        @if($recentMovements->isNotEmpty())
            <div class="col-12">
                <x-admin-panel::card>
                    <h6 class="fw-semibold mb-3">{{ __('Son Stok Hareketleri') }}</h6>
                    <x-admin-panel::table :headers="[__('Tarih'), __('Tip'), __('Depo'), __('Miktar'), __('Notlar'), __('Kaydeden')]">
                        @foreach($recentMovements as $mv)
                            <tr>
                                <td>{{ $erpFormat->datetime($mv->created_at) }}</td>
                                <td>
                                    <x-admin-panel::badge variant="{{ $mv->type === 'in' ? 'success' : ($mv->type === 'out' ? 'danger' : 'warning') }}">
                                        {{ __($mv->type) }}
                                    </x-admin-panel::badge>
                                </td>
                                <td>{{ $mv->warehouse?->name }}</td>
                                <td>{{ number_format($mv->quantity, 3) }}</td>
                                <td>{{ $mv->notes ?? '-' }}</td>
                                <td>{{ $mv->createdBy?->name }}</td>
                            </tr>
                        @endforeach
                    </x-admin-panel::table>
                </x-admin-panel::card>
            </div>
        @endif
    </div>
@endsection
