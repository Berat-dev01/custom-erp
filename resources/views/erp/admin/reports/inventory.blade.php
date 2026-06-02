@extends('erp::layouts.app')

@section('title', __('Stok Değeri Raporu'))
@section('page-title', __('Stok Değeri Raporu'))

@section('content')
    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.reports.index') }}" variant="ghost" icon="arrow-left" size="sm">
            {{ __('Raporlara Dön') }}
        </x-admin-panel::button>
    </div>

    {{-- Özet --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <x-admin-panel::stat-card
                :label="__('Toplam Stok Değeri')"
                :value="$erpFormat->money($total_stock_value)"
                icon="package"
            />
        </div>
        <div class="col-md-6">
            <x-admin-panel::stat-card
                :label="__('Düşük Stok Ürün')"
                :value="(string) $low_stock_count"
                icon="alert-triangle"
                :trend="$low_stock_count > 0 ? __('Dikkat gerekiyor') : __('Sorun yok')"
                :trend-direction="$low_stock_count > 0 ? 'down' : 'up'"
            />
        </div>
    </div>

    {{-- Depo Bazlı --}}
    <x-admin-panel::card class="mb-4">
        <h6 class="fw-semibold mb-3">{{ __('Depo Bazlı Stok Değeri') }}</h6>
        <x-admin-panel::table :headers="[__('Depo'), __('Ürün Sayısı'), __('Stok Değeri')]">
            @forelse($stock_by_warehouse as $row)
                <tr>
                    <td class="fw-medium">{{ $row->warehouse_name }}</td>
                    <td>{{ $row->product_count }}</td>
                    <td>{{ $erpFormat->money($row->total_value) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-4">{{ __('Stok verisi bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>

    {{-- Düşük Stok --}}
    <x-admin-panel::card>
        <h6 class="fw-semibold mb-3 text-danger">
            <i data-lucide="alert-triangle" style="width:16px;height:16px;vertical-align:middle;" class="me-1"></i>
            {{ __('Düşük Stok Ürünler') }}
        </h6>
        <x-admin-panel::table :headers="[__('SKU'), __('Ürün'), __('Birim'), __('Reorder Noktası'), __('Mevcut Stok'), '']">
            @forelse($low_stock_products as $product)
                @php
                    $totalQty = $product->stockLevels->sum('quantity');
                @endphp
                <tr>
                    <td class="font-monospace small">{{ $product->sku }}</td>
                    <td>
                        @can('erp.products.view')
                            <a href="{{ route('erp.products.show', $product) }}" class="fw-medium">{{ $product->name }}</a>
                        @else
                            <span class="fw-medium">{{ $product->name }}</span>
                        @endcan
                    </td>
                    <td>{{ $product->unit?->abbreviation }}</td>
                    <td>{{ number_format($product->reorder_point, 2, ',', '.') }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ $totalQty <= 0 ? 'danger' : 'warning' }}">
                            {{ number_format($totalQty, 2, ',', '.') }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        @can('erp.stock_movements.create')
                            <x-admin-panel::button href="{{ route('erp.stock-movements.create', ['product_id' => $product->id]) }}" size="sm" variant="outline" icon="plus">
                                {{ __('Stok Gir') }}
                            </x-admin-panel::button>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Düşük stoklu ürün yok.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
@endsection
